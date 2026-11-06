<template>
    <div  class="mb-6 rounded-2xl bg-[#f0f3eb] p-4" aria-label="Delivery in progress">
        <div class="relative mx-auto h-14 max-w-xs overflow-hidden"><div class="absolute inset-x-0 bottom-2 border-b-2 border-dashed border-stone-300"/><svg class="delivery-vehicle absolute bottom-2 left-1/2 h-12 w-16 text-[#60734c]" viewBox="0 0 64 48" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M5 10h32v25H5zM37 20h12l10 10v5H37z" fill="#f0f3eb"/><circle cx="17" cy="37" r="6" fill="white"/><circle cx="48" cy="37" r="6" fill="white"/><path d="M1 19h13M0 26h9"/></svg></div>
        <p class="mt-2 text-center text-sm font-medium text-[#60734c]">On the way to your address</p>
    </div>
</template>
<style scoped>
@keyframes driving { 0%,100% { transform: translateX(-55%) translateY(0); } 50% { transform: translateX(-35%) translateY(-2px); } }
@media (prefers-reduced-motion: no-preference) { .delivery-vehicle { animation: driving 2s ease-in-out infinite; } }
</style>
