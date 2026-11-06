import test from 'node:test';
import assert from 'node:assert/strict';
import { optionPreview } from '../../resources/js/Support/productOptions.js';
const groups = [
    { id: 'size', name: 'Size', selection: 'single', required: true, choices: [{ id: 'medium', price_adjustment: 0, cost_adjustment: 0 }, { id: 'large', price_adjustment: 3000, cost_adjustment: 1000 }] },
    { id: 'strength', name: 'Strength', selection: 'single', required: false, choices: [{ id: 'strong', price_adjustment: 2000, cost_adjustment: 500 }] },
];
test('selected options add independent price and HPP adjustments', () => {
    assert.deepEqual(optionPreview(17000, 7100, groups, { size: ['large'], strength: ['strong'] }), { extraPrice: 5000, extraCost: 1500, price: 22000, cost: 8600, grossProfit: 13400, missing: [] });
    assert.equal(groups[0].choices[1].price_adjustment, 3000);
});
test('required choices and incomplete base costing do not claim a completed cost estimate', () => {
    const preview = optionPreview(17000, null, groups, {});
    assert.deepEqual(preview.missing, ['Size']);
    assert.equal(preview.cost, null);
    assert.equal(preview.grossProfit, null);
    assert.throws(() => optionPreview(17000, 7000, groups, { size: ['large', 'medium'] }));
});
