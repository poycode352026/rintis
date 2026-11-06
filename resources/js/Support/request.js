export async function postJson(url, payload) {
    const token = document.cookie.split('; ').find(value => value.startsWith('XSRF-TOKEN='))?.slice(11);
    const response = await fetch(url, { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-XSRF-TOKEN': decodeURIComponent(token || '') }, body: JSON.stringify(payload) });
    let data;
    try { data = await response.json(); } catch { throw new Error('The request could not be completed. Refresh and sign in again if needed.'); }
    if (!response.ok) { const error = new Error(response.status === 404 ? 'Pickup code not found for your store.' : Object.values(data.errors || {}).flat()[0] || data.message || 'Request failed. Please retry.'); error.fields = data.errors || {}; throw error; }
    return data;
}
