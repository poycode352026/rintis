<script setup>
import { nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';
const props = defineProps({ open: Boolean, title: String, busy: Boolean });
const emit = defineEmits(['close']);
const dialog = ref(null);
const titleId = useId();
let oldOverflow = null;
const restore = () => { if (oldOverflow !== null) document.body.style.overflow = oldOverflow; oldOverflow = null; };
watch(() => props.open, async (open) => {
    await nextTick();
    if (!dialog.value) return;
    if (open && !dialog.value.open) { oldOverflow = document.body.style.overflow; document.body.style.overflow = 'hidden'; dialog.value.showModal(); }
    else if (!open && dialog.value.open) { dialog.value.close(); restore(); }
}, { immediate: true });
const close = () => { if (!props.busy) emit('close'); };
const backdrop = (event) => { if (event.target !== dialog.value) return; const box = dialog.value.getBoundingClientRect(); if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) close(); };
onBeforeUnmount(() => { dialog.value?.close(); restore(); });
</script>

<template><dialog ref="dialog" :aria-labelledby="titleId" class="rintis-dialog fixed inset-0 m-auto max-h-[85dvh] w-[calc(100%_-_2rem)] max-w-lg overflow-y-auto rounded-3xl border border-stone-200 bg-white p-0 text-stone-800 shadow-2xl backdrop:bg-slate-950/35 backdrop:backdrop-blur-sm" @cancel.prevent="close" @click="backdrop"><div class="flex items-center justify-between gap-4 border-b border-stone-100 px-6 py-5"><h2 :id="titleId" class="text-xl font-semibold tracking-tight">{{ title }}</h2><button type="button" :disabled="busy" aria-label="Close dialog" class="flex size-9 items-center justify-center rounded-full bg-stone-100 text-xl hover:bg-stone-200 disabled:opacity-50" @click="close">×</button></div><div class="p-6"><slot /></div></dialog></template>

<style scoped>
.rintis-dialog[open] { animation: dialog-in 180ms ease-out; }
@keyframes dialog-in { from { opacity: 0; transform: translateY(10px) scale(.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
@media (prefers-reduced-motion: reduce) { .rintis-dialog[open] { animation: none; } }
</style>
