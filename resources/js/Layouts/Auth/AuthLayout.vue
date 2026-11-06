<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ homeUrl: String });
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-[#f5f2ec] px-5 py-10 text-[#504941]">
        <Link :href="homeUrl" class="mb-8 rounded-lg focus-visible:outline-2 focus-visible:outline-blue-700"><span class="text-3xl font-semibold">Rintis.</span></Link>
        <main class="w-full max-w-md rounded-3xl border border-stone-200 bg-[#fffdf9] p-7 shadow-sm sm:p-9"><slot /></main>
        <p class="mt-7 text-xs text-stone-500">Rintis — by Oneclick</p>
    </div>
</template>
