<script setup>
import { computed } from 'vue';
const props = defineProps({ window: Object });
const date = value => new Intl.DateTimeFormat('en-GB', { timeZone: 'Asia/Jakarta', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(value));
const label = computed(() => props.window?.startsAt && props.window?.endsAt ? `${date(props.window.startsAt)} – ${date(props.window.endsAt)} WIB` : '');
</script>
<template><div v-if="label" class="rounded-2xl border border-stone-200 bg-stone-50 p-4 text-sm"><p class="font-semibold">Estimated delivery</p><p class="mt-2 text-stone-600">{{ label }}</p></div></template>
