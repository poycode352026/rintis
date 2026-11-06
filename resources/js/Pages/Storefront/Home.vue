<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
const page = usePage();
const props = defineProps({ store: Object, isPreview: Boolean, directoryUrl: String, openOrderUrl: String, products: Object });
</script>

<template>
    <Head :title="store.name" />
    <div class="min-h-screen bg-[#f5f2ec] text-[#504941]">
        <div v-if="isPreview" role="status" class="bg-amber-50 px-5 py-3 text-center text-sm text-amber-900">Private preview — your store is not publicly listed.</div>
        <header class="border-b border-stone-200 bg-[#fffdf9]"><div class="mx-auto flex min-h-20 max-w-6xl items-center gap-3 px-5 sm:px-8"><span aria-hidden="true" class="flex size-11 items-center justify-center rounded-full bg-[#ece5da] font-semibold">{{ store.name.slice(0, 1) }}</span><span class="text-xl font-medium">{{ store.name }}</span><Link :href="page.props.navigation.myOrders" class="ml-auto rounded-full border border-stone-300 px-4 py-2 text-sm font-medium">My orders</Link></div></header>
        <main class="mx-auto max-w-6xl px-5 py-14 sm:px-8">
            <section class="rounded-3xl border border-stone-200 bg-[#fffdf9] p-8 sm:p-16">
                <p class="text-sm font-medium tracking-widest">{{ store.name }}</p>
                <h1 class="mt-7 max-w-2xl text-4xl leading-tight font-semibold tracking-tight sm:text-6xl">{{ store.tagline || 'Something good is on its way.' }}</h1>
                <p v-if="store.category || store.city" class="mt-5 text-sm text-stone-500">{{ [store.category, store.city, store.province].filter(Boolean).join(' · ') }}</p>
                <p class="mt-7 max-w-lg leading-7 text-stone-500">Explore our catalog and check the open-order menu for scheduled sales.</p>
            </section>
            <nav class="mt-8 flex gap-3 text-sm"><span class="rounded-full bg-[#504941] px-5 py-3 text-white">Our Products</span><Link :href="openOrderUrl" class="rounded-full border border-stone-300 px-5 py-3">Open Order</Link></nav>
            <section class="mt-12">
                <h2 class="text-3xl font-semibold tracking-tight">Our Products</h2>
                <div v-if="products.data.length" class="mt-7 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <article v-for="product in products.data" :key="product.id" class="overflow-hidden rounded-3xl border border-stone-200 bg-[#fffdf9]">
                        <img v-if="product.imageUrl" :src="product.imageUrl" :alt="product.name" loading="lazy" class="aspect-[4/3] w-full object-cover">
                        <div v-else aria-hidden="true" class="flex aspect-[4/3] items-center justify-center bg-[#ece5da]"><span class="text-6xl font-semibold text-[#a69c8d]">{{ product.name.slice(0, 1) }}</span></div>
                        <div class="p-6"><h3 class="text-xl font-semibold">{{ product.name }}</h3><p v-if="product.ratingCount" class="mt-2 text-sm text-amber-700">★ {{ product.rating }} <span class="text-stone-500">({{ product.ratingCount }})</span></p><p v-if="product.description" class="mt-3 whitespace-pre-line text-sm leading-7 text-stone-500">{{ product.description }}</p></div>
                    </article>
                </div>
                <p v-else class="mt-6 rounded-2xl border border-dashed border-stone-300 px-6 py-12 text-center text-sm text-stone-500">Our catalog is being prepared. Please check back soon.</p>
                <nav v-if="products.prev_page_url || products.next_page_url" aria-label="Product pagination" class="mt-6 flex justify-between text-sm"><Link v-if="products.prev_page_url" :href="products.prev_page_url" class="underline">← Previous</Link><span v-else /><Link v-if="products.next_page_url" :href="products.next_page_url" class="underline">Next →</Link></nav>
            </section>
        </main>
        <footer class="mx-auto max-w-6xl px-5 py-8 text-sm text-stone-500 sm:px-8">Powered by <Link :href="directoryUrl" class="font-medium underline underline-offset-4">Rintis — by Oneclick</Link></footer>
    </div>
</template>
