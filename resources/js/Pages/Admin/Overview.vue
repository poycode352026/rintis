<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
defineOptions({ layout: AppLayout });
const props = defineProps({ store: Object, settingsUrl: String, storefrontUrl: String, catalogUrl: String, setup: Object, setupCompleted: Boolean, products: Object, createProductUrl: String });
const page = usePage();
const statusMessage = ref('');
let statusTimer;
watch(() => page.props, (props) => {
    clearTimeout(statusTimer);
    statusMessage.value = props.status ?? '';
    if (statusMessage.value) statusTimer = setTimeout(() => { statusMessage.value = ''; }, 5000);
}, { immediate: true });
onBeforeUnmount(() => clearTimeout(statusTimer));
const steps = computed(() => [
    { title: 'Store settings', text: 'Identity, location and pickup / delivery', done: props.setup.settingsReady, url: props.settingsUrl, icon: '01' },
    { title: 'Catalog', text: `${props.setup.categories} categories · ${props.setup.products} products · ${props.setup.visibleProducts} visible`, done: props.setup.categories > 0 && props.setup.visibleProducts > 0, url: props.setup.categories ? props.catalogUrl : `${props.catalogUrl}?tab=categories`, icon: '02' },
]);
const complete = computed(() => steps.value.filter((step) => step.done).length);
const next = computed(() => steps.value.find((step) => !step.done));
</script>

<template>
    <Head :title="`${store.name} — Overview`" />
    <div class="flex flex-wrap items-end justify-between gap-5"><div><p class="text-xs font-medium tracking-widest text-stone-400 uppercase">Your Rintis workspace</p><h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Welcome back, {{ page.props.auth.user.name }}.</h1><p class="mt-3 text-sm leading-7 text-stone-500">Small steps today. A store that feels like you.</p></div><Link :href="settingsUrl" class="rounded-full border border-stone-300 bg-[#fffdf9] px-5 py-3 text-sm font-medium hover:bg-stone-100">Store settings</Link></div>
    <section class="mt-8 grid gap-8 overflow-hidden rounded-[2rem] border border-[#e3ded4] bg-[#eee8dd] p-7 sm:p-10 lg:grid-cols-[1fr_auto] lg:items-center"><div><span class="rounded-full bg-white/70 px-3 py-1.5 text-xs font-medium text-stone-600">{{ store.is_listed ? '● Store page available' : '○ Store page preparing' }}</span><h2 class="mt-6 text-4xl font-semibold tracking-tight">{{ store.name }}</h2><p v-if="store.tagline" class="mt-3 text-sm text-stone-500">{{ store.tagline }}</p><p v-if="store.city" class="mt-3 text-xs text-stone-500">{{ [store.city, store.province].filter(Boolean).join(' · ') }}</p><div class="mt-6 flex flex-wrap gap-3"><Link :href="catalogUrl" class="rounded-full bg-[#504941] px-6 py-3 text-sm font-medium text-white hover:bg-stone-700">Manage catalog →</Link><Link :href="storefrontUrl" class="rounded-full border border-stone-400/30 bg-white/50 px-6 py-3 text-sm font-medium hover:bg-white">{{ store.is_listed ? 'Visit store' : 'Preview store' }} ↗</Link></div></div><div class="flex gap-6 border-t border-stone-400/20 pt-6 lg:border-t-0 lg:border-l lg:pl-10 lg:pt-0"><div><p class="text-4xl font-semibold tabular-nums">{{ setup.products }}</p><p class="mt-2 text-xs text-stone-500">Products</p></div><div><p class="text-4xl font-semibold tabular-nums">{{ setup.visibleProducts }}</p><p class="mt-2 text-xs text-stone-500">Visible in catalog</p></div></div></section>
    <div v-if="statusMessage" class="mt-6 flex items-center justify-between gap-4 rounded-xl border border-green-200 bg-green-50 px-5 py-3 text-sm text-green-800"><p role="status">{{ statusMessage }}</p><button type="button" aria-label="Dismiss notification" class="rounded-lg px-2 py-1 hover:bg-green-100" @click="statusMessage = ''">×</button></div>
    <section class="mt-8" aria-labelledby="store-menu-heading"><div class="mb-5 flex flex-wrap items-center justify-between gap-4"><div><h2 id="store-menu-heading" class="text-2xl font-semibold">Your menu</h2><p class="mt-2 text-sm text-stone-500">{{ products.total }} products in your store</p></div><Link :href="createProductUrl" class="rounded-full bg-[#504941] px-5 py-3 text-sm font-medium text-white hover:bg-stone-700">+ Add product</Link></div><div v-if="products.data.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3"><article v-for="product in products.data" :key="product.id" class="flex flex-col overflow-hidden rounded-3xl border border-stone-200 bg-[#fffdf9]"><img v-if="product.imageUrl" :src="product.imageUrl" :alt="product.name" loading="lazy" class="aspect-[4/3] w-full object-cover"/><div v-else class="flex aspect-[4/3] items-center justify-center bg-[#eee8dd] text-sm text-stone-500">No product photo</div><div class="flex flex-1 flex-col gap-3 p-5"><h3 class="text-lg font-semibold">{{ product.name }}</h3><p v-if="product.description" class="line-clamp-3 text-sm leading-6 text-stone-500">{{ product.description }}</p><Link :href="product.editUrl" class="mt-auto self-start rounded-full border border-stone-300 px-4 py-2 text-sm font-medium hover:bg-stone-100">Edit product →</Link></div></article></div><div v-else class="rounded-3xl border border-dashed border-stone-300 bg-[#fffdf9] p-8 text-center text-sm text-stone-500">Your menu is empty. Add your first product to display it here.</div><nav v-if="products.prev_page_url || products.next_page_url" aria-label="Menu pages" class="mt-5 flex items-center justify-between text-sm"><Link v-if="products.prev_page_url" :href="products.prev_page_url" preserve-scroll class="rounded-full border border-stone-300 px-4 py-2">← Previous</Link><span v-else/><span class="text-xs text-stone-500">Page {{ products.current_page }} of {{ products.last_page }}</span><Link v-if="products.next_page_url" :href="products.next_page_url" preserve-scroll class="rounded-full border border-stone-300 px-4 py-2">Next →</Link><span v-else/></nav></section>
    <section v-if="!setupCompleted" class="mt-8 overflow-hidden rounded-3xl border border-stone-200 bg-[#fffdf9]">
        <div class="border-b border-stone-200 p-6 sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-xl font-semibold">Set up your store</h2><span class="text-sm font-medium tabular-nums">{{ complete }} / {{ steps.length }} complete</span></div>
            <div role="progressbar" :aria-valuenow="complete" :aria-valuemax="steps.length" aria-valuemin="0" aria-label="Store setup progress" class="mt-5 h-2 overflow-hidden rounded-full bg-stone-100"><div :style="{ width: `${complete / steps.length * 100}%` }" class="h-full rounded-full bg-[#7b8967] transition-[width] duration-500 motion-reduce:transition-none" /></div>
            <p class="mt-3 text-xs leading-6 text-stone-500">First configure your store, then prepare your catalog. This guide disappears automatically when both stages are ready.</p>
        </div>
        <ol class="divide-y divide-stone-100">
            <li v-for="step in steps" :key="step.icon"><Link :href="step.url" class="flex items-center gap-4 px-6 py-4 transition-colors hover:bg-stone-50 sm:px-8"><span :class="step.done ? 'bg-[#edf2e7] text-[#60734c]' : 'bg-stone-100 text-stone-500'" class="flex size-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold" aria-hidden="true">{{ step.done ? '✓' : step.icon }}</span><div class="min-w-0 flex-1"><h3 class="text-sm font-medium">{{ step.title }}</h3><p class="mt-1 text-xs leading-5 text-stone-500">{{ step.text }}</p></div><span class="shrink-0 text-xs" :class="step.done ? 'text-[#60734c]' : 'text-stone-500'">{{ step.done ? 'Done' : 'Continue →' }}</span></Link></li>
        </ol>
        <div class="flex flex-wrap items-center justify-between gap-4 border-t border-stone-200 bg-stone-50 px-6 py-5 sm:px-8"><span class="text-sm text-stone-500">{{ next ? 'Continue when you are ready.' : 'Your store is ready.' }}</span><Link v-if="next" :href="next.url" class="rounded-full bg-[#504941] px-5 py-3 text-sm font-medium text-white">Next step →</Link></div>
    </section>
    <p class="mt-8 text-xs leading-7 text-stone-500">Rintis uses open order: your catalog can be viewed at any time. Manage sales sessions in Open orders. Customers can order during an active session.</p>
</template>
