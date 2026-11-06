const units = { g: ['mass', 1n], kg: ['mass', 1000n], ml: ['volume', 1n], l: ['volume', 1000n], pcs: ['count', 1n] };
const scaled = (value, precision) => {
    const text = String(value ?? '');
    if (!(precision === 0 ? /^\d+$/ : new RegExp(`^\\d+(?:\\.\\d{1,${precision}})?$`)).test(text)) throw new Error('Enter valid quantities and amounts to see your estimate.');
    const [whole, decimal = ''] = text.split('.');
    return BigInt(whole) * 10n ** BigInt(precision) + BigInt(decimal.padEnd(precision, '0'));
};
const divide = (n, d) => n / d + (n % d >= (d + 1n) / 2n ? 1n : 0n);
export const compatibleCostUnits = (unit) => Object.keys(units).filter((key) => units[key][0] === units[unit]?.[0]);
export const calculateIngredientCost = (row) => {
    const bought = units[row.purchase_unit]; const used = units[row.used_unit];
    if (!bought || !used || bought[0] !== used[0]) throw new Error('Purchase and usage units must measure the same thing (mass, volume or pieces).');
    const pack = scaled(row.purchase_quantity, 3); const amount = scaled(row.used_quantity, 3);
    const price = scaled(row.purchase_cost, 0);
    if (pack < 1n || amount < 1n || pack > 1000000n || amount > 1000000n || price > 999999999n) throw new Error('Quantities must be between 0.001 and 1,000. Check the purchase price.');
    const cost = divide(price * amount * used[1], pack * bought[1]);
    if (cost > 999999999999n) throw new Error('Ingredient cost is too large. Check purchase quantity and usage.');
    return Number(cost);
};
export const previewProductCost = (costing) => {
    const rows = (costing?.ingredients ?? []).map((row) => {
        try { return { value: calculateIngredientCost(row), error: '' }; }
        catch (error) { return { value: null, error: error.message }; }
    });
    const firstInvalid = rows.findIndex((row) => row.value === null);
    if (firstInvalid !== -1) return { rows, value: null, error: `Complete item ${firstInvalid + 1} to see the recipe total.`, suggestionError: '' };
    try {
        const totals = calculateProductCost({ ...costing, target_margin: null, target_markup: null });
        try { return { rows, value: { ...totals, ...calculateProductCost(costing) }, error: '', suggestionError: '' }; }
        catch (error) { return { rows, value: totals, error: '', suggestionError: error.message }; }
    } catch (error) { return { rows, value: null, error: error.message, suggestionError: '' }; }
};
export const calculateProductCost = (costing) => {
    if (!costing?.ingredients?.length) throw new Error('Add at least one ingredient or packaging item.');
    if (costing.ingredients.length > 50) throw new Error('Use no more than 50 items.');
    const costs = costing.ingredients.map((row) => BigInt(calculateIngredientCost(row)));
    const yieldCount = scaled(costing.batch_yield, 0); const additional = scaled(costing.additional_cost, 0);
    if (yieldCount < 1n || yieldCount > 1000000n || additional > 999999999n) throw new Error('Enter a positive whole batch yield and valid additional cost.');
    const batch = costs.reduce((total, cost) => total + cost, additional);
    const unit = divide(batch, yieldCount);
    let suggested = null; let priceLimitExceeded = false;
    if (Object.hasOwn(costing, 'target_markup')) {
        if (costing.target_markup !== '' && costing.target_markup != null) {
            const markup = scaled(costing.target_markup, 2);
            if (markup > 100000000n) throw new Error('Target profit must be between 0 and 1,000,000%.');
            const target = unit + (unit * markup + 9999n) / 10000n;
            priceLimitExceeded = target > 999999999n;
            if (!priceLimitExceeded) suggested = Number(target);
        }
    } else if (costing.target_margin !== '' && costing.target_margin != null) {
        const margin = scaled(costing.target_margin, 2);
        if (margin > 9999n) throw new Error('Target margin must be between 0 and 99.99%.');
        const denominator = 10000n - margin;
        const target = (unit * 10000n + denominator - 1n) / denominator;
        priceLimitExceeded = target > 999999999n;
        if (!priceLimitExceeded) suggested = Number(target);
    }
    return { ingredientCosts: costs.map(Number), batchCost: Number(batch), unitCost: Number(unit), suggestedPrice: suggested, priceLimitExceeded };
};

export const costingWithMarkup = (costing) => {
    if (!costing) return null;
    const result = JSON.parse(JSON.stringify(costing));
    if (!Object.hasOwn(result, 'target_markup')) {
        const margin = result.target_margin;
        result.target_markup = margin === '' || margin == null ? '' : Math.round(Number(margin) / (100 - Number(margin)) * 10000) / 100;
    }
    delete result.target_margin;
    return result;
};
