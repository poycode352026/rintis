export function parseRupiah(value) {
    const text = String(value).trim().replace(/^Rp\s*/i, '').replace(/\s/g, '');
    if (!/^\d*(?:\.\d*)*(?:,\d*)?$/.test(text)) return { valid: false };
    const [whole, fraction] = text.split(',');
    if (fraction !== undefined && fraction !== '' && Number(fraction) !== 0) return { valid: false };
    const digits = whole.replace(/\./g, '');
    if (digits === '') return { valid: true, value: '' };
    const amount = Number(digits);
    return Number.isSafeInteger(amount) ? { valid: true, value: amount } : { valid: false };
}

export function formatRupiah(value) {
    return value === '' || value === null || value === undefined ? '' : new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value));
}
