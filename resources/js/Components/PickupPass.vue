<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
const props = defineProps({ code: String, reference: String });
const canvas = ref(null); const error = ref(''); let disposed = false;
const draw = async () => {
    try { const { default: QRCode } = await import('qrcode'); if (!disposed && canvas.value) await QRCode.toCanvas(canvas.value, `RINTIS-PICKUP:${props.code}`, { width: 240, margin: 4, errorCorrectionLevel: 'M', color: { dark: '#000000', light: '#ffffff' } }); }
    catch { error.value = 'QR could not load. Show the pickup code below instead.'; }
};
onMounted(draw); watch(() => props.code, draw); onBeforeUnmount(() => { disposed = true; });
</script>
<template><section class="mt-6 rounded-3xl border border-stone-200 bg-white p-5 text-center"><h2 class="text-lg font-semibold">Your pickup QR</h2><p class="mt-2 text-sm leading-6 text-stone-500">Show this QR to the seller when collecting your order.</p><canvas ref="canvas" role="img" :aria-label="`Pickup QR for ${reference}`" class="mx-auto mt-4 max-w-full"/><p v-if="error" role="status" class="mt-3 text-xs text-amber-800">{{ error }}</p><p class="mt-3 font-mono text-lg font-semibold tracking-wider">{{ code.match(/.{1,4}/g).join(' ') }}</p><p class="mt-3 text-xs text-stone-500">One pickup only. Keep this code private.</p></section></template>
