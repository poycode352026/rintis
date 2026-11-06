<script setup>
import { ref } from 'vue';
import CatalogEditor from './CatalogEditor.vue';
import Dialog from '../Shared/Dialog.vue';
defineProps({ categories: Array, storeUrl: String });
const editing = ref(null);
const showEditor = ref(false);
const edit = (record) => { editing.value = record; showEditor.value = true; };
</script>

<template>
    <section>
        <div class="flex flex-wrap items-start justify-between gap-4"><div><h2 class="text-xl font-semibold">Store categories</h2><p class="mt-2 text-sm leading-6 text-slate-500">Business categories available to Rintis stores.</p></div><button type="button" class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800" @click="edit(null)">Add category</button></div>
        <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full min-w-[420px] text-left text-sm"><caption class="sr-only">Rintis store categories</caption><thead class="border-b border-slate-200 bg-slate-50 text-xs text-slate-500 uppercase"><tr><th scope="col" class="px-6 py-4">Name</th><th scope="col" class="px-6 py-4">Slug</th><th scope="col" class="px-6 py-4"><span class="sr-only">Actions</span></th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="category in categories" :key="category.id"><th scope="row" class="px-6 py-4 font-medium">{{ category.name }}</th><td class="px-6 py-4 text-slate-500">{{ category.slug }}</td><td class="px-6 py-4 text-right"><button type="button" :aria-label="`Edit ${category.name}`" class="font-medium text-blue-700 hover:underline" @click="edit(category)">Edit</button></td></tr><tr v-if="!categories.length"><td colspan="3" class="px-6 py-10 text-center text-slate-500">No categories yet.</td></tr></tbody></table>
        </div>
        <Dialog :open="showEditor" :title="editing ? 'Edit category' : 'New category'" @close="showEditor = false"><CatalogEditor v-if="showEditor" :key="editing?.id ?? 'new'" :record="editing" :store-url="storeUrl" identifier="slug" identifier-label="Category slug" @saved="showEditor = false" /></Dialog>
    </section>
</template>
