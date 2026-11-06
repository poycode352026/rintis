export const optionPreview = (basePrice, baseCost, groups, selected) => {
    let extraPrice = 0; let extraCost = 0; const missing = [];
    for (const group of groups) {
        const ids = new Set(selected[group.id] ?? []);
        const choices = group.choices.filter(choice => ids.has(choice.id));
        if (group.required && choices.length === 0) missing.push(group.name || 'Unnamed group');
        if (group.selection === 'single' && choices.length > 1) throw new Error('Choose only one item in a single-choice group.');
        for (const choice of choices) {
            for (const key of ['price_adjustment', 'cost_adjustment']) {
                if (choice[key] === '' || !Number.isInteger(Number(choice[key])) || Number(choice[key]) < 0 || Number(choice[key]) > 999999999) throw new Error('Enter whole non-negative option prices and costs.');
            }
            extraPrice += Number(choice.price_adjustment); extraCost += Number(choice.cost_adjustment);
        }
    }
    const price = basePrice === '' || basePrice == null ? null : Number(basePrice) + extraPrice;
    const cost = baseCost == null ? null : Number(baseCost) + extraCost;
    return { extraPrice, extraCost, price, cost, grossProfit: price != null && cost != null ? price - cost : null, missing };
};
