import test from 'node:test';
import assert from 'node:assert/strict';
import { normalizeStoreSlug } from '../../resources/js/Support/storeIdentity.js';

test('store URLs use lowercase and hyphens, including pasted names', () => {
    assert.equal(normalizeStoreSlug(' Wemeet   Coffee '), 'wemeet-coffee');
    assert.equal(normalizeStoreSlug('Café & COFFEE'), 'cafe-coffee');
    assert.equal(normalizeStoreSlug('../ADMIN'), 'admin');
});

test('typing preserves a trailing separator until the URL is finalized', () => {
    assert.equal(normalizeStoreSlug('Wemeet ', false), 'wemeet-');
    assert.equal(normalizeStoreSlug('wemeet-', true), 'wemeet');
});
