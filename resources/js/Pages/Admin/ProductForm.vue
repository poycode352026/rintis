<script setup>
import { computed, nextTick, onBeforeUnmount, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import FormField from '../../Components/Shared/FormField.vue';
import ProductOptionsFields from '../../Components/ProductOptionsFields.vue';
import ProductDeleteDialog from '../../Components/ProductDeleteDialog.vue';
import { costingWithMarkup } from '../../Support/productCosting.js';
import ProductCostingFields from '../../Components/ProductCostingFields.vue';
import RupiahInput from '../../Components/Shared/RupiahInput.vue';
defineOptions({ layout: AppLayout });
const props = defineProps({ store: Object, product: Object, categories: Array, submitUrl: String, backUrl: String, categoriesUrl: String, deleteUrl: String });
const form = useForm({ name: props.product?.name ?? '', description: props.product?.description ?? '', price: props.product?.price ?? '', catalog_category_id: props.product?.catalog_category_id ?? '', is_visible: true, options: JSON.parse(JSON.stringify(props.product?.options ?? [])), costing: costingWithMarkup(props.product?.costing), photo: null, remove_image: false });
const activeTab = ref('details');
const appliedMessage = ref('');
const priceApplied = async (value) => { appliedMessage.value = `Base price updated to Rp${new Intl.NumberFormat('id-ID').format(value)}. Save product to keep this change.`; activeTab.value = 'details'; await nextTick(); const input = document.getElementById('price'); input?.scrollIntoView({ behavior: 'smooth', block: 'center' }); input?.focus({ preventScroll: true }); };
const tabs = [{ id: 'details', label: 'Product details' }, { id: 'choices', label: 'Product choices' }, { id: 'hpp', label: 'HPP' }];
const errorTab = (key) => key === 'costing' || key.startsWith('costing.') ? 'hpp' : key === 'options' || key.startsWith('options.') ? 'choices' : 'details';
const tabErrors = computed(() => new Set(Object.keys(form.errors).map(errorTab)));
const moveTab = (event, index) => {
    let next;
    if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
    else if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
    else if (event.key === 'Home') next = 0;
    else if (event.key === 'End') next = tabs.length - 1;
    else return;
    event.preventDefault(); activeTab.value = tabs[next].id;
    document.getElementById(`product-tab-${tabs[next].id}`)?.focus();
};
const showDelete = ref(false);
const preview = ref(props.product?.imageUrl ?? null);
const photoInput = ref(null);
let objectUrl = null;
const clearObjectUrl = () => { if (objectUrl) URL.revokeObjectURL(objectUrl); objectUrl = null; };
const choosePhoto = (event) => {
    clearObjectUrl();
    form.photo = event.target.files[0] ?? null;
    form.remove_image = false;
    objectUrl = form.photo ? URL.createObjectURL(form.photo) : null;
    preview.value = objectUrl ?? props.product?.imageUrl ?? null;
};
const clearSelection = () => { clearObjectUrl(); form.photo = null; photoInput.value.value = ''; preview.value = props.product?.imageUrl ?? null; };
onBeforeUnmount(clearObjectUrl);
const submit = async (event) => {
    const invalid = event.currentTarget.querySelector(':invalid');
    if (invalid) {
        activeTab.value = invalid.closest('[data-product-panel]')?.dataset.productPanel ?? 'details';
        const details = invalid.closest('details');
        if (details) details.open = true;
        await nextTick(); invalid.focus(); invalid.reportValidity();
        return;
    }
    form.transform((data) => ({ ...data, options: JSON.stringify(data.options), _method: props.product ? 'patch' : 'post' })).post(props.submitUrl, {
        forceFormData: true,
        onError: async (errors) => {
            activeTab.value = errorTab(Object.keys(errors)[0] ?? 'name');
            await nextTick(); document.getElementById(`product-panel-${activeTab.value}`)?.focus();
        },
    });
};
</script>

<template>
    <Head :title="product ? 'Edit product' : 'Add product'" />
    <div class="mx-auto max-w-4xl"><Link :href="backUrl" class="text-sm text-stone-500 hover:underline">← Your catalog</Link><p class="mt-7 text-xs text-stone-500">{{ store.name }}</p><h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ product ? 'Edit product' : 'Add a product' }}</h1>
        <div v-if="!categories.length" class="mt-7 rounded-2xl border border-stone-200 bg-white p-7"><p class="leading-7">Create your first product category before adding a product.</p><Link :href="categoriesUrl" class="mt-4 inline-block font-medium underline">Manage categories →</Link></div>
        <form v-else class="mt-7 flex flex-col gap-6 rounded-3xl border border-stone-200 bg-[#fffdf9] p-6 sm:p-8" novalidate @submit.prevent="submit">
            <p v-if="appliedMessage" role="status" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ appliedMessage }}</p><div role="tablist" aria-label="Product sections" class="flex gap-2 overflow-x-auto border-b border-stone-200 pb-3"><button v-for="(tab, index) in tabs" :id="`product-tab-${tab.id}`" :key="tab.id" type="button" role="tab" :aria-selected="activeTab === tab.id" :aria-controls="`product-panel-${tab.id}`" :tabindex="activeTab === tab.id ? 0 : -1" :class="activeTab === tab.id ? 'bg-[#504941] text-white' : 'text-stone-500 hover:bg-stone-100'" class="shrink-0 rounded-xl px-4 py-3 text-sm font-medium" @click="activeTab = tab.id" @keydown="moveTab($event, index)">{{ tab.label }}<span v-if="tabErrors.has(tab.id)" class="ml-2 text-red-500" aria-label="Contains errors">●</span></button></div>
            <div id="product-panel-details" v-show="activeTab === 'details'" role="tabpanel" aria-labelledby="product-tab-details" data-product-panel="details" tabindex="-1" class="flex flex-col gap-6 outline-none">
            <FormField v-model="form.name" name="name" label="Product name" placeholder="e.g. Iced Latte" required maxlength="150" :error="form.errors.name" />
            <div><label for="catalog-category" class="text-sm font-medium">Category</label><select id="catalog-category" v-model="form.catalog_category_id" required :aria-invalid="Boolean(form.errors.catalog_category_id)" class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3"><option value="">Choose a category</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select><p v-if="form.errors.catalog_category_id" role="alert" class="mt-2 text-sm text-red-700">{{ form.errors.catalog_category_id }}</p></div>
            <div><label for="description" class="text-sm font-medium">Description (optional)</label><textarea id="description" v-model="form.description" rows="4" maxlength="2000" :aria-invalid="Boolean(form.errors.description)" class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3" /><p v-if="form.errors.description" role="alert" class="mt-2 text-sm text-red-700">{{ form.errors.description }}</p></div>
            <div><RupiahInput v-model="form.price" name="price" label="Base price (IDR)" required :error="form.errors.price" /><p class="mt-2 text-xs leading-6 text-stone-500">Type 17000 to display 17.000. This base price is not shown in the public catalog.</p></div>
            <div class="border-t border-stone-200 pt-5"><label for="photo" class="text-sm font-medium">Product photo (optional)</label><img v-if="preview && !form.remove_image" :src="preview" alt="Product photo preview" class="mt-4 aspect-[4/3] w-full max-w-xs rounded-2xl object-cover"><input id="photo" ref="photoInput" type="file" accept="image/jpeg,image/png,image/webp" class="mt-4 block w-full text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-stone-100 file:px-4 file:py-2" @change="choosePhoto"><p class="mt-2 text-xs leading-6 text-stone-500">JPEG, PNG, or WebP. Up to 5 MB and 4096 × 4096 pixels. A placeholder is used when no photo is provided.</p><p v-if="form.errors.photo" role="alert" class="mt-2 text-sm text-red-700">{{ form.errors.photo }}</p><button v-if="form.photo" type="button" class="mt-3 text-sm underline" @click="clearSelection">Clear selected file</button><label v-else-if="product?.imageUrl" class="mt-3 flex items-center gap-3 text-sm"><input v-model="form.remove_image" type="checkbox" class="size-4 accent-stone-700">Remove existing photo</label><p v-if="form.errors.remove_image" role="alert" class="mt-2 text-sm text-red-700">{{ form.errors.remove_image }}</p></div>
            <p class="rounded-xl bg-stone-50 p-4 text-sm leading-6 text-stone-500">This product appears in Our Products automatically. Orders will only be available during an active open-order period.</p>
            </div>
            <div id="product-panel-choices" v-show="activeTab === 'choices'" role="tabpanel" aria-labelledby="product-tab-choices" data-product-panel="choices" tabindex="-1" class="outline-none"><ProductOptionsFields :form="form" /></div>
            <div id="product-panel-hpp" v-show="activeTab === 'hpp'" role="tabpanel" aria-labelledby="product-tab-hpp" data-product-panel="hpp" tabindex="-1" class="outline-none"><ProductCostingFields :form="form" @price-applied="priceApplied" /></div>
            <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="w-full" aria-label="Photo upload progress" />
            <div class="flex flex-wrap gap-3 border-t border-stone-200 pt-5"><button :disabled="form.processing" class="rounded-xl bg-[#504941] px-5 py-3 font-medium text-white disabled:opacity-50">{{ form.processing ? 'Saving…' : product ? 'Save product' : 'Add product' }}</button><button v-if="product" type="button" :disabled="form.processing" class="rounded-xl border border-red-200 px-5 py-3 text-sm font-medium text-red-700 hover:bg-red-50" @click="showDelete = true">Delete product</button></div>
        </form>
        <ProductDeleteDialog :open="showDelete" :name="product?.name" :url="deleteUrl" @close="showDelete = false" />
    </div>
</template>
