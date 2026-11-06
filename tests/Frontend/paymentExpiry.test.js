import test from 'node:test';
import assert from 'node:assert/strict';
import { paymentExpiryTimestamp, paymentCountdown } from '../../resources/js/Support/paymentExpiry.js';
test('provider dates without offsets are interpreted in WIB, while explicit offsets are respected', () => {
    assert.equal(paymentExpiryTimestamp('2026-11-03 13:51:59'), Date.parse('2026-11-03T06:51:59Z'));
    assert.equal(paymentExpiryTimestamp('2026-11-03T06:51:59Z'), Date.parse('2026-11-03T06:51:59Z'));
    assert.equal(paymentExpiryTimestamp('invalid'), null);
});
test('countdown ticks every second, crosses hours and never becomes negative', () => {
    const expiry = '2026-11-03 13:51:59';
    assert.equal(paymentCountdown(expiry, Date.parse('2026-11-03T05:51:58Z')).label, '01:00:01');
    assert.equal(paymentCountdown(expiry, Date.parse('2026-11-03T05:51:59Z')).label, '01:00:00');
    assert.deepEqual(paymentCountdown(expiry, Date.parse('2026-11-03T06:52:00Z')), { expired: true, seconds: 0, label: '00:00:00' });
    assert.equal(paymentCountdown(null, Date.now()), null);
});
