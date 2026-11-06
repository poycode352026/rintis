<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
defineOptions({ layout: AppLayout });
defineProps({ stores: Object });
</script>

<template>
    <Head title="Explore Rintis" />
    <section class="rounded-3xl border border-stone-200 bg-[#fffdf9] px-7 py-12 sm:px-12 sm:py-16">
        <p class="text-xs font-semibold tracking-widest text-stone-500 uppercase">Local stores. New possibilities.</p>
        <h1 class="mt-5 max-w-2xl text-4xl leading-tight font-semibold tracking-tight sm:text-6xl">Your next favorite.<br>One discovery away.</h1>
        <p class="mt-6 max-w-xl leading-7 text-stone-500">A place for independent stores and the people who love them. Discover the stores building their home on Rintis.</p>
        <a href="#stores" class="mt-7 inline-flex rounded-full bg-[#504941] px-6 py-3 text-sm font-medium text-white hover:bg-stone-700">Browse stores <span aria-hidden="true" class="ml-5">↓</span></a>
        <p class="mt-4 text-sm text-stone-500">Explore catalogs without creating an account. Ordering will be available during open-order periods.</p>
    </section>
    <section id="stores" class="mt-12 scroll-mt-28">
        <h2 class="text-2xl font-semibold">Explore stores</h2>
        <div v-if="stores.data.length" class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="store in stores.data" :key="store.url" :href="store.url" class="rounded-2xl border border-stone-200 bg-[#fffdf9] p-7 transition-colors hover:border-stone-500">
                <span aria-hidden="true" class="flex size-14 items-center justify-center rounded-2xl bg-[#ece5da] text-2xl font-semibold">{{ store.name.slice(0, 1) }}</span>
                <h3 class="mt-5 text-xl font-semibold">{{ store.name }}</h3>
                <p v-if="store.category || store.city" class="mt-2 text-xs text-stone-500">{{ [store.category, store.city, store.province].filter(Boolean).join(' · ') }}</p>
                <p v-if="store.tagline" class="mt-2 text-sm leading-6 text-stone-500">{{ store.tagline }}</p>
                <span class="mt-5 inline-block text-sm font-medium">Visit store →</span>
            </Link>
        </div>
        <div v-else class="mt-6 rounded-2xl border border-dashed border-stone-300 p-10 text-center">
            <h3 class="text-lg font-medium">The first stores are getting ready.</h3>
            <p class="mt-2 text-sm text-stone-500">Published stores will appear here. Check back soon.</p>
        </div>
        <nav v-if="stores.prev_page_url || stores.next_page_url" aria-label="Store pagination" class="mt-6 flex justify-between gap-4 text-sm">
            <Link v-if="stores.prev_page_url" :href="stores.prev_page_url" class="underline">← Previous</Link><span v-else />
            <Link v-if="stores.next_page_url" :href="stores.next_page_url" class="underline">Next →</Link>
        </nav>
    </section>
</template>
