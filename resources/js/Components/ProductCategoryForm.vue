<script setup>
import { useForm } from '@inertiajs/vue3';
import FormField from './Shared/FormField.vue';
const props = defineProps({ category: Object, storeUrl: String });
const emit = defineEmits(['saved']);
const form = useForm({ name: props.category?.name ?? '' });
const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => { form.reset(); emit('saved'); } };
    props.category ? form.patch(props.category.updateUrl, options) : form.post(props.storeUrl, options);
};
</script>

<template><form class="flex flex-col gap-5 rounded-2xl border border-stone-200 bg-[#fffdf9] p-6" @submit.prevent="submit"><FormField v-model="form.name" name="category-name" label="Category name" placeholder="e.g. Coffee" required maxlength="100" :error="form.errors.name" /><button :disabled="form.processing" class="self-start rounded-xl bg-[#504941] px-5 py-3 text-sm font-medium text-white disabled:opacity-50">{{ form.processing ? 'Saving…' : category ? 'Save category' : 'Add category' }}</button></form></template>
