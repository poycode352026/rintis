<script setup>
defineProps({ label: String, name: String, error: String, type: { type: String, default: 'text' }, autocomplete: String, placeholder: String, required: Boolean, maxlength: [String, Number] });
const model = defineModel();
</script>

<template>
    <div class="flex flex-col gap-2">
        <label :for="name" class="text-sm font-medium text-stone-800">{{ label }}</label>
        <input :id="name" v-model="model" :name="name" :type="type" :autocomplete="autocomplete" :placeholder="placeholder" :required="required" :maxlength="maxlength" :aria-invalid="Boolean(error)" :aria-describedby="error ? `${name}-error` : undefined" class="w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-base text-stone-900 outline-none placeholder:text-stone-400 focus:border-stone-600 focus:ring-2 focus:ring-stone-200">
        <p v-if="error" :id="`${name}-error`" role="alert" class="text-sm text-red-700">{{ error }}</p>
    </div>
</template>
