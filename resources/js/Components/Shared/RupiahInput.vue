<script setup>
import { computed, ref } from 'vue';
import { parseRupiah, formatRupiah } from '../../Support/rupiah.js';
const props = defineProps({ name: String, label: String, error: String, required: Boolean });
const model = defineModel();
const display = computed(() => formatRupiah(model.value));
const inputError = ref('');
const input = (event) => {
    const element = event.target;
    const digitCount = element.value.slice(0, element.selectionStart).replace(/\D/g, '').length;
    const parsed = parseRupiah(element.value);
    inputError.value = parsed.valid ? '' : 'Enter a whole, non-negative rupiah amount.';
    element.setCustomValidity(inputError.value);
    if (!parsed.valid) return;
    model.value = parsed.value;
    const formatted = formatRupiah(parsed.value);
    element.value = formatted;
    let position = 0;
    let count = 0;
    while (position < formatted.length && count < digitCount) { if (/\d/.test(formatted[position])) count++; position++; }
    element.setSelectionRange(position, position);
};
</script>

<template><div><label :for="name" class="text-sm font-medium">{{ label }}</label><div class="relative mt-2"><span aria-hidden="true" class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-sm font-medium text-stone-400">Rp</span><input :id="name" :name="name" :value="display" type="text" inputmode="numeric" autocomplete="off" maxlength="15" :required="required" :aria-invalid="Boolean(error || inputError)" :aria-describedby="(error || inputError) ? `${name}-error` : undefined" placeholder="0" class="w-full rounded-xl border border-stone-300 bg-white py-3 pr-4 pl-12 text-base tabular-nums outline-none focus:border-stone-500 focus:ring-2 focus:ring-stone-200" @input="input"></div><p v-if="error || inputError" :id="`${name}-error`" role="alert" class="mt-2 text-sm text-red-700">{{ error || inputError }}</p></div></template>
