<script setup>
import { computed, ref } from 'vue';
const props = defineProps({ name: String, label: String, error: String, required: Boolean });
const meters = defineModel();
const unit = ref('km');
const value = computed({
    get: () => meters.value === '' || meters.value == null ? '' : Number(meters.value) / (unit.value === 'km' ? 1000 : 1),
    set: (value) => { meters.value = value === '' ? '' : Math.round(Number(value) * (unit.value === 'km' ? 1000 : 1)); },
});
</script>
<template>
    <div><label :for="name" class="text-sm font-medium">{{ label }}</label>
        <div class="mt-2 flex overflow-hidden rounded-xl border border-stone-300 bg-white focus-within:ring-2 focus-within:ring-stone-200">
            <input :id="name" v-model="value" type="number" :step="unit === 'km' ? '0.001' : '1'" :min="unit === 'km' ? '0.001' : '1'" :max="unit === 'km' ? '1000' : '1000000'" :required="required" :aria-invalid="Boolean(error)" class="min-w-0 flex-1 px-4 py-3 outline-none">
            <select v-model="unit" :aria-label="`${label} unit`" class="border-l border-stone-200 bg-stone-50 px-3 text-sm"><option value="m">Meters</option><option value="km">Kilometers</option></select>
        </div><p v-if="error" role="alert" class="mt-2 text-sm text-red-700">{{ error }}</p>
    </div>
</template>
