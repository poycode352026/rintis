<script setup>
import { useForm } from '@inertiajs/vue3';
const props = defineProps({ product: Object, customerName: String, compact: Boolean });
const form = useForm({ is_active: !props.product.is_active });
const submit = () => {
    if (props.product.is_active && !window.confirm(`Deactivate ${props.product.name} access for ${props.customerName}? Their stored data will be preserved.`)) return;
    form.patch(props.product.updateUrl, { preserveScroll: true });
};
</script>

<template>
    <form :class="compact ? 'inline-flex flex-col items-start gap-2' : 'flex flex-wrap items-center justify-between gap-5 rounded-2xl border border-slate-200 bg-white p-6'" @submit.prevent="submit">
        <div v-if="!compact"><h3 class="font-semibold">{{ product.name }}</h3><p class="mt-2 text-sm" :class="product.is_active ? 'text-green-700' : 'text-slate-500'">{{ product.is_active ? 'Active access' : 'Not active' }}</p></div>
        <p v-if="form.errors.is_active" role="alert" class="text-sm text-red-700">{{ form.errors.is_active }}</p>
        <button :disabled="form.processing" :aria-label="`${product.is_active ? 'Deactivate' : 'Activate'} ${product.name} access for ${customerName}`" :class="product.is_active ? 'border border-red-200 text-red-700 hover:bg-red-50' : 'bg-blue-700 text-white hover:bg-blue-800'" class="rounded-xl px-4 py-2.5 text-sm font-medium disabled:opacity-50">{{ form.processing ? 'Updating…' : product.is_active ? (compact ? 'Deactivate' : 'Deactivate access') : (compact ? 'Activate' : 'Activate access') }}</button>
    </form>
</template>
