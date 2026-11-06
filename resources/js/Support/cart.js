export const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
export const cartKey = id => `rintis.cart.${id}`;
export function readCart(id) {
    try { const value = JSON.parse(sessionStorage.getItem(cartKey(id)) || '[]'); return Array.isArray(value) ? value.slice(0,50).filter(line => Number.isInteger(line.id) && Number.isInteger(line.quantity) && line.quantity > 0 && line.quantity <= 99 && Array.isArray(line.choices)) : []; } catch { return []; }
}
export function cartLine(line, items) {
    const item = items.find(item => item.id === line.id);
    if (!item) return null;
    const choices = item.options.flatMap(group => group.choices.filter(choice => line.choices.includes(choice.id)).map(choice => ({ ...choice, group: group.name })));
    const price = item.price + choices.reduce((sum, choice) => sum + Number(choice.price_adjustment), 0);
    return { ...line, item, selected: choices, price, total: price * line.quantity };
}
export function choicesValid(item, selected) {
    const available = item.options.flatMap(group => group.choices.map(choice => choice.id));
    return new Set(selected).size === selected.length && selected.every(id => available.includes(id)) && item.options.every(group => { const count = group.choices.filter(choice => selected.includes(choice.id)).length; return (!group.required || count > 0) && (group.selection !== 'single' || count <= 1); });
}
