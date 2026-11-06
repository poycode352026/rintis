export const normalizeStoreSlug = (value, trimEnd = true) => {
    const slug = String(value ?? '').normalize('NFKD').replace(/[\u0300-\u036f]/g, '')
        .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+/, '');
    return trimEnd ? slug.replace(/-+$/, '') : slug;
};
