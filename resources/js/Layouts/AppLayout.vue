<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SellerNotifications from '../Components/SellerNotifications.vue';
const page = usePage();
const accountMenu = ref(null);
const fulfillmentMenu = ref(null);
const header = ref(null);
const mobileOpen = ref(false);
const closeDropdowns = () => { if (accountMenu.value) accountMenu.value.open = false; if (fulfillmentMenu.value) fulfillmentMenu.value.open = false; };
const closeMenu = () => { closeDropdowns(); mobileOpen.value = false; };
const closeOutside = event => {
    if (accountMenu.value && !accountMenu.value.contains(event.target)) accountMenu.value.open = false;
    if (fulfillmentMenu.value && !fulfillmentMenu.value.contains(event.target)) fulfillmentMenu.value.open = false;
    if (header.value && !header.value.contains(event.target)) mobileOpen.value = false;
};
onMounted(() => document.addEventListener('pointerdown', closeOutside));
onBeforeUnmount(() => document.removeEventListener('pointerdown', closeOutside));
watch(() => page.url, closeMenu);
const isWorkspace = computed(() => page.component.startsWith('Admin/'));
const active = computed(() => {
    const name = page.component;
    if (name.endsWith('/Income')) return 'income';
    if (name.includes('/OpenOrders/')) return 'orders';
    if (name.endsWith('/Deliveries') || name.endsWith('/Pickup')) return 'fulfillment';
    if (name.endsWith('/Catalog') || name.endsWith('/ProductForm')) return 'catalog';
    if (name.endsWith('/StoreForm')) return 'settings';
    return 'store';
});
const mainLinks = computed(() => [
    { key: 'store', label: 'My store', url: page.props.navigation.admin },
    { key: 'catalog', label: 'Catalog', url: page.props.catalogUrl },
    { key: 'orders', label: 'Open orders', url: page.props.navigation.openOrders },
    { key: 'income', label: 'Income', url: page.props.navigation.income },
    { key: 'settings', label: 'Settings', url: page.props.settingsUrl },
].filter(link => link.url));
const navStyle = key => active.value === key ? 'bg-[#ece9e1] text-[#38352f] font-semibold' : 'text-stone-500 hover:bg-stone-100 hover:text-stone-800';
</script>

<template>
<div class="min-h-screen bg-[#f5f2ec] text-[#504941]">
    <header ref="header" class="sticky top-0 z-40 border-b border-stone-200 bg-[#fffdf9]/95 backdrop-blur-md" @keydown.esc="closeMenu">
        <div class="mx-auto flex min-h-20 max-w-6xl flex-wrap items-center justify-between gap-x-5 px-5 py-4 sm:px-8">
            <Link :href="isWorkspace ? page.props.navigation.admin : page.props.navigation.directory" class="flex shrink-0 items-center gap-3" @click="closeMenu"><span class="text-2xl font-semibold tracking-tight">Rintis<span class="text-[#7c8b66]">.</span></span><span class="border-l border-stone-200 pl-3 text-xs text-stone-500">{{ isWorkspace ? 'Seller workspace' : 'by Oneclick' }}</span></Link>
            <button type="button" :aria-expanded="mobileOpen" aria-controls="rintis-navigation" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-stone-200 bg-white px-4 py-2 text-sm font-medium xl:hidden" @click="mobileOpen = !mobileOpen"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path v-if="mobileOpen" d="m6 6 12 12M6 18 18 6"/><path v-else d="M4 6h16M4 12h16M4 18h16"/></svg>{{ mobileOpen ? 'Close' : 'Menu' }}</button>
            <nav id="rintis-navigation" aria-label="Rintis navigation" :class="mobileOpen ? 'flex' : 'hidden'" class="mt-4 w-full flex-col gap-2 border-t border-stone-200 pt-4 text-sm xl:mt-0 xl:flex xl:w-auto xl:flex-row xl:items-center xl:gap-1 xl:border-0 xl:pt-0">
                <template v-if="isWorkspace">
                    <Link v-for="link in mainLinks" :key="link.key" :href="link.url" :aria-current="active === link.key ? 'page' : undefined" :class="navStyle(link.key)" class="rounded-xl px-4 py-3 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-500">{{ link.label }}</Link>
                    <details ref="fulfillmentMenu" class="relative" @keydown.esc.stop.prevent="closeDropdowns">
                        <summary :class="navStyle('fulfillment')" class="flex cursor-pointer list-none items-center justify-between gap-2 rounded-xl px-4 py-3 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-500 [&::-webkit-details-marker]:hidden">Delivery &amp; pickup<span aria-hidden="true" class="text-xs text-stone-400">⌄</span></summary>
                        <div class="mt-2 rounded-2xl border border-stone-200 bg-white p-2 xl:absolute xl:right-0 xl:z-50 xl:w-60 xl:shadow-lg">
                            <Link :href="page.props.navigation.deliveries" :aria-current="page.component.endsWith('/Deliveries') ? 'page' : undefined" :class="page.component.endsWith('/Deliveries') ? 'bg-stone-100' : 'hover:bg-stone-50'" class="block rounded-xl px-4 py-3"><span class="block font-medium">Deliveries</span><span class="mt-1 block text-xs text-stone-500">Plan routes &amp; manage drop-offs</span></Link>
                            <Link :href="page.props.navigation.pickup" :aria-current="page.component.endsWith('/Pickup') ? 'page' : undefined" :class="page.component.endsWith('/Pickup') ? 'bg-stone-100' : 'hover:bg-stone-50'" class="block rounded-xl px-4 py-3"><span class="block font-medium">Scan pickup</span><span class="mt-1 block text-xs text-stone-500">Scan codes &amp; hand over orders</span></Link>
                        </div>
                    </details>
                    <SellerNotifications :feed-url="page.props.navigation.notifications" :read-url="page.props.navigation.readNotifications"/><details ref="accountMenu" class="relative xl:ml-2" @keydown.esc.prevent="closeMenu">
                            <summary class="flex cursor-pointer list-none items-center gap-2 rounded-full border border-stone-200 bg-white px-4 py-2.5 text-sm font-medium hover:bg-stone-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-600 [&::-webkit-details-marker]:hidden"><span class="max-w-32 truncate">{{ page.props.auth.user.name }}</span><span aria-hidden="true" class="text-stone-400">⌄</span></summary>
                            <div class="absolute left-0 z-50 xl:left-auto xl:right-0 mt-3 w-64 max-w-[calc(100vw_-_2rem)] overflow-hidden rounded-2xl border border-stone-200 bg-[#fffdf9] shadow-lg">
                                <div class="border-b border-stone-100 px-4 py-3"><p class="truncate text-sm font-semibold">{{ page.props.auth.user.name }}</p><p class="mt-1 truncate text-xs text-stone-500">{{ page.props.auth.user.email }}</p></div>
                                <div class="p-2">
                                    <Link :href="page.props.navigation.directory" class="block rounded-xl px-3 py-3 text-sm hover:bg-stone-100" @click="closeMenu">Shop as customer ↗</Link>
                                    <Link :href="page.props.navigation.myOrders" class="block rounded-xl px-3 py-3 text-sm hover:bg-stone-100" @click="closeMenu">My orders</Link>

                                    <Link :href="page.props.navigation.logout" method="post" as="button" class="block w-full rounded-xl px-3 py-3 text-left text-sm text-red-700 hover:bg-red-50" @click="closeMenu">Sign out</Link>
                                </div>
                            </div>
                        </details>
                </template>
                <template v-else>
                    <Link :href="page.props.navigation.directory" class="rounded-xl px-4 py-3 hover:bg-stone-100">Explore stores</Link>
                    <Link :href="page.props.navigation.myOrders" class="rounded-xl px-4 py-3 hover:bg-stone-100">My orders</Link>
                    <template v-if="!page.props.auth.user">
                        <Link :href="page.props.navigation.login" class="rounded-xl px-4 py-3 hover:bg-stone-100">Sign in</Link>
                        <Link :href="page.props.navigation.register" class="rounded-xl bg-stone-100 px-4 py-3 font-medium">Create your store</Link>
                    </template>
                    <Link v-if="page.props.auth.user" :href="page.props.navigation.admin" class="rounded-xl bg-stone-100 px-4 py-3 font-medium">Back to my store</Link>
                </template>
            </nav>
        </div>
    </header>
    <main class="mx-auto max-w-6xl px-5 py-10 sm:px-8 sm:py-14"><slot /></main>
    <footer class="mx-auto max-w-6xl px-5 py-8 text-xs text-stone-500 sm:px-8">Rintis — by <Link :href="page.props.navigation.home" class="underline underline-offset-4">Oneclick</Link></footer>
</div>
</template>
