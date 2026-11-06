<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { money } from '../Support/cart.js';
const props = defineProps({ feedUrl: String, readUrl: String });
const container = ref(null); const open = ref(false); const unread = ref(0); const items = ref([]); const error = ref(''); const announcement = ref('');
let timer; let controller; let fetching = false; let previousIds = null; let disposed = false;
const load = async () => {
    if (fetching || disposed || document.hidden) return;
    fetching = true; controller = new AbortController();
    try {
        const response = await fetch(props.feedUrl, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal });
        if (!response.ok) throw new Error();
        const data = await response.json();
        if (disposed) return;
        const newItems = previousIds ? data.items.filter(item => !previousIds.has(item.id) && !item.read) : [];
        if (newItems.length) announcement.value = newItems.map(item => `${item.title} · ${item.reference}`).join(' · ');
        previousIds = new Set(data.items.map(item => item.id)); items.value = data.items; unread.value = data.unread; error.value = '';
    } catch (exception) { if (!disposed && exception.name !== 'AbortError') error.value = 'Notifications could not load. Please retry.'; }
    finally { fetching = false; }
};
const markRead = async id => {
    const xsrf = document.cookie.split('; ').find(value => value.startsWith('XSRF-TOKEN='))?.slice(11);
    const response = await fetch(props.readUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': decodeURIComponent(xsrf || '') }, body: JSON.stringify(id ? { id } : {}) });
    if (!response.ok) throw new Error();
    items.value = items.value.map(item => !id || item.id === id ? { ...item, read: true } : item);
    unread.value = id ? Math.max(0, unread.value - 1) : 0;
};
const readAll = async () => { try { await markRead(); error.value = ''; } catch { error.value = 'Could not mark notifications as read. Please retry.'; } };
const visit = async item => {
    try { if (!item.read) await markRead(item.id); } catch { error.value = 'Read status could not be saved.'; }
    if (item.url) { open.value = false; announcement.value = ''; router.visit(item.url); }
};
const toggle = () => { open.value = !open.value; if (open.value) { announcement.value = ''; load(); } };
const outside = event => { if (container.value && !container.value.contains(event.target)) open.value = false; };
const time = value => new Intl.DateTimeFormat('en-GB', { timeZone: 'Asia/Jakarta', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(value));
onMounted(() => { load(); timer = setInterval(load, 15000); document.addEventListener('visibilitychange', load); document.addEventListener('pointerdown', outside); window.addEventListener('focus', load); });
onBeforeUnmount(() => { disposed = true; controller?.abort(); clearInterval(timer); document.removeEventListener('visibilitychange', load); document.removeEventListener('pointerdown', outside); window.removeEventListener('focus', load); });
</script>
<template><div ref="container" class="relative" @keydown.esc.stop="open = false"><button type="button" :aria-expanded="open" aria-controls="seller-notifications" :aria-label="`Notifications, ${unread} unread`" class="relative flex size-11 items-center justify-center rounded-full border border-stone-200 bg-white text-stone-600 hover:bg-stone-50 focus-visible:outline-2 focus-visible:outline-offset-2" @click="toggle"><svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><span v-if="unread" class="absolute -right-1 -top-1 flex min-w-5 items-center justify-center rounded-full bg-[#504941] px-1 py-0.5 text-[10px] font-semibold text-white">{{ unread > 99 ? '99+' : unread }}</span></button>
<div v-if="announcement && !open" role="status" aria-live="polite" class="absolute left-0 top-14 z-50 w-72 rounded-2xl border border-green-200 bg-green-50 p-4 text-xs leading-6 text-green-900 shadow-lg xl:left-auto xl:right-0"><p>{{ announcement }}</p><button type="button" class="mt-2 underline" @click="toggle">View notifications</button><button type="button" aria-label="Dismiss notification" class="float-right px-2" @click="announcement = ''">×</button></div>
<section v-if="open" id="seller-notifications" aria-label="Seller notifications" class="absolute left-0 top-14 z-50 w-[min(22rem,calc(100vw-3rem))] overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-xl xl:left-auto xl:right-0"><div class="flex items-center justify-between gap-3 border-b border-stone-100 p-4"><h2 class="font-semibold">Notifications</h2><button v-if="unread" type="button" class="text-xs underline" @click="readAll">Mark all read</button></div><p v-if="error" role="alert" class="p-4 text-xs text-amber-800">{{ error }} <button type="button" class="underline" @click="load">Retry</button></p><div class="max-h-96 overflow-y-auto"><button v-for="item in items" :key="item.id" type="button" class="block w-full border-b border-stone-100 p-4 text-left hover:bg-stone-50" :class="item.read ? 'bg-white' : 'bg-[#f5f2ec]'" @click="visit(item)"><span class="flex items-start gap-3"><span class="flex size-10 shrink-0 items-center justify-center rounded-xl" :class="item.kind === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'"><svg v-if="item.kind === 'paid'" viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="3"/><circle cx="12" cy="12" r="3"/><path d="M6 9h.01M18 15h.01"/></svg><svg v-else viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 7h14l1 13H4L5 7Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg></span><span class="min-w-0 flex-1"><span class="flex items-center justify-between gap-3 text-sm font-medium"><span>{{ item.title }}</span><span v-if="!item.read" class="size-2 shrink-0 rounded-full bg-blue-600"/></span><span class="mt-2 block break-words font-mono text-xs text-stone-500">{{ item.reference }} · {{ money(item.total) }}</span><span class="mt-2 block text-xs text-stone-400">{{ time(item.at) }} WIB</span></span></span></button><p v-if="!items.length && !error" class="p-8 text-center text-sm text-stone-500">You're all caught up.</p></div></section></div></template>
