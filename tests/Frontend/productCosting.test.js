import test from 'node:test';
import assert from 'node:assert/strict';
import { calculateProductCost } from '../../resources/js/Support/productCosting.js';
const costing = () => ({ batch_yield: 2, additional_cost: 1000, target_margin: '40', ingredients: [
    { name: 'Coffee', purchase_quantity: 1, purchase_unit: 'kg', purchase_cost: 200000, used_quantity: 36, used_unit: 'g' },
    { name: 'Milk', purchase_quantity: 1, purchase_unit: 'l', purchase_cost: 20000, used_quantity: 200, used_unit: 'ml' },
    { name: 'Cups', purchase_quantity: 50, purchase_unit: 'pcs', purchase_cost: 50000, used_quantity: 2, used_unit: 'pcs' },
] });
test('costs convert mass, volume and pieces and divide batch costs before margin pricing', () => {
    assert.deepEqual(calculateProductCost(costing()), { ingredientCosts: [7200, 4000, 2000], batchCost: 14200, unitCost: 7100, suggestedPrice: 11834, priceLimitExceeded: false });
});
test('incomplete quantities, incompatible units and invalid margin cannot produce a suggestion', () => {
    const input = costing(); input.ingredients[0].purchase_quantity = '';
    assert.throws(() => calculateProductCost(input));
    input.ingredients[0].purchase_quantity = 0; assert.throws(() => calculateProductCost(input));
    input.ingredients[0].purchase_quantity = 1; input.ingredients[0].used_unit = 'ml'; assert.throws(() => calculateProductCost(input));
    input.ingredients[0].used_unit = 'g'; input.target_margin = 100; assert.throws(() => calculateProductCost(input));
});
test('zero is preserved and an empty optional margin does not guess a price', () => {
    const input = costing(); input.target_margin = '';
    assert.equal(calculateProductCost(input).suggestedPrice, null);
    input.ingredients.forEach(row => row.purchase_cost = 0); input.additional_cost = 0; input.target_margin = '0';
    assert.equal(calculateProductCost(input).suggestedPrice, 0);
});
test('rounding is half-up in whole rupiah and high suggestions cannot be applied as base price', () => {
    const input = { batch_yield: 1, additional_cost: 0, target_margin: null, ingredients: [{ name: 'Item', purchase_quantity: 2, purchase_unit: 'pcs', purchase_cost: 1, used_quantity: 1, used_unit: 'pcs' }] };
    assert.equal(calculateProductCost(input).unitCost, 1);
    input.ingredients[0].purchase_cost = 999999999; input.target_margin = '99.99';
    assert.equal(calculateProductCost(input).priceLimitExceeded, true);
    assert.equal(calculateProductCost(input).suggestedPrice, null);
});

test('adding an incomplete item preserves independent existing ingredient costs', async () => {
    const { previewProductCost } = await import('../../resources/js/Support/productCosting.js');
    const input = costing();
    input.ingredients.push({ name: 'Bag', purchase_quantity: '', purchase_unit: 'pcs', purchase_cost: '', used_quantity: '', used_unit: 'pcs' });
    const preview = previewProductCost(input);
    assert.deepEqual(preview.rows.slice(0, 3).map(row => row.value), [7200, 4000, 2000]);
    assert.equal(preview.rows[3].value, null);
    assert.equal(preview.value, null);
    assert.match(preview.error, /item 4/);
    assert.equal(input.ingredients[0].purchase_cost, 200000);
});

test('an invalid margin or one incompatible item never hides other row costs', async () => {
    const { previewProductCost, compatibleCostUnits } = await import('../../resources/js/Support/productCosting.js');
    const input = costing(); input.target_margin = 100;
    const preview = previewProductCost(input);
    assert.equal(preview.value.unitCost, 7100);
    assert.equal(preview.value.suggestedPrice, null);
    assert.ok(preview.suggestionError);
    input.ingredients[2].used_unit = 'g';
    assert.equal(previewProductCost(input).rows[0].value, 7200);
    assert.deepEqual(compatibleCostUnits('pcs'), ['pcs']);
    assert.deepEqual(compatibleCostUnits('kg'), ['g', 'kg']);
});

test('profit target adds a percentage of HPP including 100 percent and above', () => {
    const input = { batch_yield: 1, additional_cost: 0, target_markup: 100, ingredients: [{ name: 'Coffee', purchase_quantity: 1, purchase_unit: 'pcs', purchase_cost: 8000, used_quantity: 1, used_unit: 'pcs' }] };
    assert.equal(calculateProductCost(input).suggestedPrice, 16000);
    input.target_markup = 50; assert.equal(calculateProductCost(input).suggestedPrice, 12000);
    input.target_markup = 200; assert.equal(calculateProductCost(input).suggestedPrice, 24000);
    input.target_markup = 0; assert.equal(calculateProductCost(input).suggestedPrice, 8000);
    input.target_markup = ''; assert.equal(calculateProductCost(input).suggestedPrice, null);
    input.target_markup = 1000001; assert.throws(() => calculateProductCost(input));
});

test('saved margin is converted for editing without mutating historical data', async () => {
    const { costingWithMarkup } = await import('../../resources/js/Support/productCosting.js');
    const input = costing(); input.target_margin = 50;
    const converted = costingWithMarkup(input);
    assert.equal(converted.target_markup, 100);
    assert.equal(Object.hasOwn(converted, 'target_margin'), false);
    assert.equal(input.target_margin, 50);
    assert.equal(calculateProductCost(converted).suggestedPrice, calculateProductCost(input).suggestedPrice);
    input.target_margin = 99.99; assert.equal(costingWithMarkup(input).target_markup, 999900);
    input.target_margin = null; assert.equal(costingWithMarkup(input).target_markup, '');
});

test('markup suggestion errors preserve HPP and supported-price limits', async () => {
    const { previewProductCost } = await import('../../resources/js/Support/productCosting.js');
    const input = costing(); delete input.target_margin; input.target_markup = -1;
    assert.equal(previewProductCost(input).value.unitCost, 7100);
    assert.ok(previewProductCost(input).suggestionError);
    input.target_markup = 1000000; input.additional_cost = 999999999;
    assert.equal(calculateProductCost(input).priceLimitExceeded, true);
    assert.equal(calculateProductCost(input).suggestedPrice, null);
});
