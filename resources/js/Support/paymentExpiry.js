export const paymentExpiryTimestamp = value => {
    if (typeof value !== 'string' || !value.trim()) return null;
    const normalized = value.trim().replace(' ', 'T');
    const timestamp = Date.parse(/(?:Z|[+-]\d{2}:?\d{2})$/i.test(normalized) ? normalized : `${normalized}+07:00`);
    return Number.isFinite(timestamp) ? timestamp : null;
};
export const paymentCountdown = (expiry, now) => {
    const end = paymentExpiryTimestamp(expiry);
    if (end === null) return null;
    const seconds = Math.max(0, Math.ceil((end - now) / 1000));
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor(seconds % 3600 / 60);
    return { expired: seconds === 0, seconds, label: `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}` };
};
