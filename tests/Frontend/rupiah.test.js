import test from 'node:test';
import assert from 'node:assert/strict';
import { parseRupiah, formatRupiah } from '../../resources/js/Support/rupiah.js';

test('formats whole rupiah while preserving zero and blank input', () => {
    assert.equal(formatRupiah(17000), '17.000');
    assert.equal(formatRupiah(1700000), '1.700.000');
    assert.equal(formatRupiah(0), '0');
    assert.equal(formatRupiah(''), '');
});

test('formatted amounts and pasted currency return the original integer', () => {
    for (const input of ['17000', '17.000', 'Rp 17.000', '17.000,00']) {
        assert.deepEqual(parseRupiah(input), { valid: true, value: 17000 });
    }
    assert.deepEqual(parseRupiah(''), { valid: true, value: '' });
    assert.deepEqual(parseRupiah('0'), { valid: true, value: 0 });
});

test('negative amounts and nonzero decimal fractions are not silently converted', () => {
    for (const input of ['-17000', '17.000,50', 'abc', '1e5', '999999999999999999999']) {
        assert.deepEqual(parseRupiah(input), { valid: false });
    }
});
