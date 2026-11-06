<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Dialog from './Shared/Dialog.vue';
const props = defineProps({ open: Boolean, name: String, url: String });
const emit = defineEmits(['close']);
const busy = ref(false);
const error = ref('');
const remove = () => {
    busy.value = true; error.value = '';
    router.delete(props.url, { preserveScroll: true, onSuccess: () => emit('close'), onError: () => { error.value = 'The product could not be deleted. Please try again.'; }, onFinish: () => { busy.value = false; } });
};
</script>
<template><Dialog :open="open" title="Delete product?" :busy="busy" @close="emit('close')"><p class="text-sm leading-7">Delete <strong>{{ name }}</strong> from your catalog? It will no longer appear to customers.</p><p v-if="error" role="alert" class="mt-4 text-sm text-red-700">{{ error }}</p><div class="mt-6 flex justify-end gap-3"><button type="button" :disabled="busy" class="rounded-xl border border-stone-300 px-4 py-3 text-sm" @click="emit('close')">Cancel</button><button type="button" :disabled="busy" class="rounded-xl bg-red-700 px-4 py-3 text-sm font-medium text-white disabled:opacity-50" @click="remove">{{ busy ? 'Deleting…' : 'Delete product' }}</button></div></Dialog></template>
