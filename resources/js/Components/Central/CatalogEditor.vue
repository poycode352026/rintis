<script setup>
import { useForm } from '@inertiajs/vue3';
import FormField from '../Shared/FormField.vue';
const props = defineProps({ record: Object, identifier: String, identifierLabel: String, storeUrl: String, readonlyIdentifier: Boolean });
const emit = defineEmits(['saved']);
const form = useForm({ name: props.record?.name ?? '', [props.identifier]: props.record?.[props.identifier] ?? '' });
const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => { if (!props.record) form.reset(); emit('saved'); } };
    if (props.record) form.patch(props.record.updateUrl, options);
    else form.post(props.storeUrl, options);
};
</script>

<template>
    <form class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6" @submit.prevent="submit">
        <FormField v-model="form.name" :name="`name-${record?.id ?? 'new'}`" label="Name" :error="form.errors.name" required maxlength="100" />
        <div v-if="record && readonlyIdentifier"><p class="text-xs text-slate-500">{{ identifierLabel }}</p><p class="mt-1 font-mono text-sm">{{ record[identifier] }}</p></div>
        <FormField v-else v-model="form[identifier]" :name="`${identifier}-${record?.id ?? 'new'}`" :label="identifierLabel" :error="form.errors[identifier]" required :maxlength="identifier === 'key' ? 80 : 100" />
        <p v-if="record && readonlyIdentifier" class="text-xs text-slate-500">{{ record.workspaceReady ? 'Workspace connected' : 'Workspace not connected yet' }}</p>
        <button :disabled="form.processing" class="self-start rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-50">{{ form.processing ? 'Saving…' : record ? 'Save changes' : 'Add entry' }}</button>
    </form>
</template>
