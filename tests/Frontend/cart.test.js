import test from 'node:test';
import assert from 'node:assert/strict';
import { cartLine, choicesValid, readCart } from '../../resources/js/Support/cart.js';
const item = { id: 1, price: 18000, options: [{ name: 'Size', required: true, selection: 'single', choices: [{ id: 'large', name: 'Large', price_adjustment: 3000 }, { id: 'small', name: 'Small', price_adjustment: 0 }] }, { name: 'Extras', required: false, selection: 'multiple', choices: [{ id: 'shot', name: 'Shot', price_adjustment: 2000 }] }] };
test('cart totals include selected option prices and quantity', () => { const line = cartLine({ id: 1, quantity: 2, choices: ['large','shot'] }, [item]); assert.equal(line.price, 23000); assert.equal(line.total, 46000); assert.equal(cartLine({ id: 7 }, [item]), null); });
test('required and single choices reject incomplete, duplicate and unknown selections', () => { for (const choices of [[], ['large','small'], ['large','large'], ['unknown']]) assert.equal(choicesValid(item, choices), false); assert.equal(choicesValid(item, ['large','shot']), true); assert.equal(choicesValid(item, ['small']), true); });
test('corrupt or unavailable browser storage does not break the menu', () => { assert.deepEqual(readCart(1), []); globalThis.sessionStorage = { getItem: () => '{bad json' }; assert.deepEqual(readCart(1), []); delete globalThis.sessionStorage; });
