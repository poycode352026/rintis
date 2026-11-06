<script setup>
import { computed, ref, watch } from 'vue';
import { money } from '../Support/cart.js';
const props = defineProps({ summary: Object, filters: Object });
const metric = ref('received');
const selected = ref(null);
watch(() => props.summary, () => { selected.value = null; });
const days = computed(() => {
    const rows = [];
    const end = Date.parse(`${props.filters.to}T00:00:00Z`);
    for (let time = Date.parse(`${props.filters.from}T00:00:00Z`); time <= end && rows.length < 367; time += 86400000) {
        const key = new Date(time).toISOString().slice(0, 10);
        rows.push({ date: key, received: props.summary.trend?.[key]?.received || 0, sales: props.summary.trend?.[key]?.sales || 0 });
    }
    return rows;
});
const highest = computed(() => Math.max(0, ...days.value.map(day => day[metric.value])));
const max = computed(() => Math.max(1, highest.value));
const point = (day, index) => ({ x: days.value.length === 1 ? 360 : 58 + index / Math.max(1, days.value.length - 1) * 640, y: 195 - day[metric.value] / max.value * 150 });
const points = computed(() => days.value.map(point));
const path = computed(() => points.value.map((p, i) => `${i ? 'L' : 'M'}${p.x},${p.y}`).join(' '));
const area = computed(() => points.value.length ? `${path.value} L${points.value.at(-1).x},195 L${points.value[0].x},195 Z` : '');
const current = computed(() => days.value[selected.value ?? days.value.length - 1]);
const currentPoint = computed(() => current.value ? point(current.value, selected.value ?? days.value.length - 1) : null);
const date = key => new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', timeZone: 'UTC' }).format(new Date(`${key}T00:00:00Z`));
const short = value => new Intl.NumberFormat('en-GB', { notation: 'compact', maximumFractionDigits: 1 }).format(value);
const hover = event => {
    const rect = event.currentTarget.getBoundingClientRect();
    const x = (event.clientX - rect.left) / rect.width * 720;
    selected.value = Math.max(0, Math.min(days.value.length - 1, Math.round((x - 58) / 640 * (days.value.length - 1))));
};
const move = delta => { selected.value = Math.max(0, Math.min(days.value.length - 1, (selected.value ?? days.value.length - 1) + delta)); };
const channels = computed(() => {
    const list = [{ name: 'Cash', value: props.summary.cash, color: '#788b64' }, { name: 'QRIS', value: props.summary.qris, color: '#5e87ac' }, { name: 'Bank transfer', value: props.summary.bank_transfer, color: '#c99e64' }];
    let offset = 0;
    return list.map(channel => { const percent = props.summary.received ? channel.value / props.summary.received * 100 : 0; const result = { ...channel, percent, offset }; offset += percent; return result; });
});
const tickDays = computed(() => [...new Set([0, Math.floor((days.value.length - 1) / 2), days.value.length - 1])].filter(i => i >= 0));
</script>
<template>
<div class="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1.7fr)_minmax(280px,1fr)]">
<section class="min-w-0 rounded-3xl border border-stone-200 bg-white p-5 sm:p-6"><div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-semibold">Income trend</h2><p class="mt-1 text-xs text-stone-500">Grouped by order date · WIB</p></div><div class="flex rounded-full bg-stone-100 p-1"><button v-for="(label,key) in { received: 'Payments', sales: 'Completed sales' }" :key="key" type="button" :aria-pressed="metric === key" class="rounded-full px-3 py-2 text-xs font-medium transition-colors" :class="metric === key ? 'bg-white text-stone-800 shadow-sm' : 'text-stone-500'" @click="metric = key">{{ label }}</button></div></div><div class="mt-6 flex flex-wrap items-baseline justify-between gap-2"><p class="text-xs text-stone-500">{{ current ? date(current.date) : 'No data' }}</p><p class="text-xl font-semibold tabular-nums">{{ money(current?.[metric] || 0) }}</p></div>
<svg viewBox="0 0 720 240" role="img" tabindex="0" :aria-label="`${metric === 'received' ? 'Payments received' : 'Completed product sales and delivery income'} by order date. Use left and right arrows to explore dates.`" class="mt-2 w-full rounded-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-400" @pointermove="hover" @pointerdown="hover" @keydown.left.prevent="move(-1)" @keydown.right.prevent="move(1)"><defs><linearGradient id="income-area" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#788b64" stop-opacity=".25"/><stop offset="100%" stop-color="#788b64" stop-opacity=".02"/></linearGradient></defs><g v-for="fraction in [0,0.5,1]" :key="fraction"><line x1="58" x2="698" :y1="195-fraction*150" :y2="195-fraction*150" stroke="#eeece7" stroke-dasharray="4 5"/><text x="48" :y="199-fraction*150" text-anchor="end" font-size="11" fill="#8c877f">{{ short(highest*fraction) }}</text></g><path :d="area" fill="url(#income-area)"/><path :d="path" fill="none" stroke="#788b64" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><template v-if="currentPoint"><line :x1="currentPoint.x" :x2="currentPoint.x" y1="30" y2="195" stroke="#b5c0a8" stroke-dasharray="4 4"/><circle :cx="currentPoint.x" :cy="currentPoint.y" r="6" fill="#788b64" stroke="white" stroke-width="3"/></template><text v-for="index in tickDays" :key="index" :x="point(days[index],index).x" y="225" text-anchor="middle" font-size="11" fill="#8c877f">{{ date(days[index].date) }}</text></svg><p class="text-xs text-stone-400">{{ metric === 'received' ? 'Verified payments, including paid orders still in progress.' : 'Completed, paid orders: products and delivery combined.' }}</p>
</section>
<section class="rounded-3xl border border-stone-200 bg-white p-5 sm:p-6"><h2 class="text-lg font-semibold">Payment mix</h2><p class="mt-1 text-xs text-stone-500">Share of payments received</p><div class="relative mx-auto my-5 size-44"><svg viewBox="0 0 120 120" class="size-full -rotate-90" role="img" aria-label="Payment breakdown by cash, QRIS and bank transfer"><circle cx="60" cy="60" r="46" fill="none" stroke="#f0eeea" stroke-width="12"/><circle v-for="channel in channels" :key="channel.name" cx="60" cy="60" r="46" pathLength="100" fill="none" :stroke="channel.color" stroke-width="12" :stroke-dasharray="`${channel.percent} ${100-channel.percent}`" :stroke-dashoffset="-channel.offset"><title>{{ channel.name }}: {{ money(channel.value) }}</title></circle></svg><div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center px-7 text-center"><span class="text-xs text-stone-400">Received</span><strong class="mt-1 break-words text-sm tabular-nums">{{ money(summary.received) }}</strong></div></div><dl class="space-y-3"><div v-for="channel in channels" :key="channel.name" class="flex items-center justify-between gap-3 text-sm"><dt class="flex items-center gap-2"><span class="size-2.5 rounded-full" :style="{ backgroundColor: channel.color }"/>{{ channel.name }}</dt><dd class="text-right"><span class="font-medium tabular-nums">{{ money(channel.value) }}</span><span class="ml-2 text-xs text-stone-400">{{ channel.percent.toFixed(1) }}%</span></dd></div></dl><p v-if="!summary.received" class="mt-4 text-xs text-stone-400">No payments received in this period.</p></section>
</div>
</template>
