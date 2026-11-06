<script setup>
import { computed } from 'vue';
import RupiahInput from './Shared/RupiahInput.vue';
import { previewProductCost, compatibleCostUnits } from '../Support/productCosting.js';
const props = defineProps({ form: Object });
const emit = defineEmits(['price-applied']);
const units = ['g', 'kg', 'ml', 'l', 'pcs'];
const item = () => ({ name: '', purchase_quantity: '', purchase_unit: 'g', purchase_cost: '', used_quantity: '', used_unit: 'g' });
const start = () => { props.form.costing = { batch_yield: 1, additional_cost: 0, target_markup: '', ingredients: [item()] }; };
const rowKeys = new WeakMap();
let nextRowKey = 0;
const rowKey = (row) => { if (!rowKeys.has(row)) rowKeys.set(row, ++nextRowKey); return rowKeys.get(row); };
const purchaseUnitChanged = (row) => {
    if (!compatibleCostUnits(row.purchase_unit).includes(row.used_unit)) {
        row.used_unit = row.purchase_unit;
        row.used_quantity = '';
    }
};
const estimate = computed(() => props.form.costing ? previewProductCost(props.form.costing) : { rows: [], value: null, error: '', suggestionError: '' });
const errors = computed(() => Object.entries(props.form.errors).filter(([key]) => key === 'costing' || key.startsWith('costing.')));
const money = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
</script>

<template>
    <section class="border-t border-stone-200 pt-6">
        <div class="flex flex-wrap items-start justify-between gap-3"><div><span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-600">Private · Only you</span><h2 class="mt-3 text-xl font-semibold">Production cost (HPP)</h2><p class="mt-2 text-sm leading-6 text-stone-500">Enter everything used to make this recipe: ingredients, cups, straws and packaging. This worksheet is never shown to customers.</p></div><button v-if="!form.costing" type="button" class="rounded-xl border border-stone-300 px-4 py-3 text-sm font-medium hover:bg-stone-50" @click="start">+ Add HPP calculation</button></div>
        <div v-if="form.costing" class="mt-6 space-y-5">
            <p class="text-sm text-stone-500">Enter the amounts used for one product. Example: 18 g of coffee and 1 cup.</p>
            <div class="overflow-x-auto rounded-2xl border border-stone-200">
                <table class="w-full min-w-[850px] text-left text-sm"><caption class="sr-only">Ingredients and packaging costs</caption>
                    <thead class="border-b border-stone-200 bg-stone-50 text-xs text-stone-500"><tr><th scope="col" class="px-3 py-3">Ingredient / packaging</th><th scope="col" class="px-3 py-3">Purchased quantity</th><th scope="col" class="px-3 py-3">Total purchase price</th><th scope="col" class="px-3 py-3">Quantity used</th><th scope="col" class="px-3 py-3">Cost used</th><th scope="col" class="sticky right-0 w-16 min-w-16 border-l border-stone-200 bg-stone-50 px-3 py-3 text-center"><span class="sr-only">Remove ingredient</span></th></tr></thead>
                    <tbody class="divide-y divide-stone-100"><template v-for="(row, index) in form.costing.ingredients" :key="rowKey(row)">
                        <tr class="align-top">
                            <td class="min-w-40 px-3 py-3"><label :for="`cost-name-${index}`" class="sr-only">Ingredient {{ index + 1 }}</label><input :id="`cost-name-${index}`" v-model="row.name" required maxlength="100" placeholder="e.g. Coffee beans" class="w-full rounded-lg border border-stone-300 bg-white px-3 py-3"></td>
                            <td class="px-3 py-3"><div class="flex"><input v-model="row.purchase_quantity" :aria-label="`Purchased quantity for item ${index + 1}`" type="number" min="0.001" max="1000" step="0.001" required class="w-24 rounded-l-lg border border-stone-300 bg-white px-2 py-3"><select v-model="row.purchase_unit" :aria-label="`Purchased unit for item ${index + 1}`" class="rounded-r-lg border border-l-0 border-stone-300 bg-white px-2" @change="purchaseUnitChanged(row)"><option v-for="unit in units" :key="unit">{{ unit }}</option></select></div></td>
                            <td class="min-w-44 px-3 py-1 [&_label]:sr-only"><RupiahInput v-model="row.purchase_cost" :name="`purchase-cost-${index}`" :label="`Purchase price for item ${index + 1}`" required /></td>
                            <td class="px-3 py-3"><div class="flex"><input v-model="row.used_quantity" :aria-label="`Quantity used for item ${index + 1}`" type="number" min="0.001" max="1000" step="0.001" required class="w-24 rounded-l-lg border border-stone-300 bg-white px-2 py-3"><select v-model="row.used_unit" :aria-label="`Usage unit for item ${index + 1}`" class="rounded-r-lg border border-l-0 border-stone-300 bg-white px-2"><option v-for="unit in compatibleCostUnits(row.purchase_unit)" :key="unit">{{ unit }}</option></select></div></td>
                            <td class="px-3 py-3 align-middle font-semibold whitespace-nowrap tabular-nums">{{ estimate.rows[index]?.value != null ? money(estimate.rows[index].value) : '—' }}</td>
                            <td class="sticky right-0 w-16 min-w-16 border-l border-stone-200 bg-[#fffdf9] px-3 py-3 text-center align-middle"><button type="button" :aria-label="`Remove ${row.name || `item ${index + 1}`}`" title="Remove ingredient" class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg border border-red-100 bg-white text-red-600 transition-colors hover:border-red-200 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600" @click="form.costing.ingredients.splice(index, 1)"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4"><path d="m6 6 12 12M18 6 6 18" /></svg></button></td>
                        </tr>
                        <tr v-if="estimate.rows[index]?.error"><td colspan="6" role="status" class="bg-amber-50 px-3 py-2 text-xs text-amber-800">Item {{ index + 1 }}: {{ estimate.rows[index].error }}</td></tr>
                    </template></tbody>
                </table>
            </div>
            <button type="button" :disabled="form.costing.ingredients.length >= 50" class="rounded-xl border border-stone-300 px-4 py-3 text-sm font-medium hover:bg-stone-50 disabled:opacity-50" @click="form.costing.ingredients.push(item())">+ Add ingredient / packaging</button>
            <details :open="Boolean(form.errors['costing.batch_yield'] || form.errors['costing.additional_cost'])" class="rounded-xl border border-stone-200 p-4"><summary class="cursor-pointer text-sm font-medium">Recipe quantity &amp; other costs</summary><div class="mt-4 space-y-4">
            <div class="grid gap-5 sm:grid-cols-2"><div><label for="batch-yield" class="text-sm font-medium">How many products does this recipe make?</label><input id="batch-yield" v-model="form.costing.batch_yield" type="number" min="1" max="1000000" step="1" required class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3"><p class="mt-2 text-xs text-stone-500">For one Americano: enter 1. For a recipe making 10 cups together: enter 10, and enter ingredients for all 10 cups.</p></div><RupiahInput v-model="form.costing.additional_cost" name="additional-cost" label="Other costs for this recipe" required /></div>
            <p class="text-xs leading-6 text-stone-500">Additional costs are only costs not already listed above, such as labor or utilities. Use 0 if none; do not enter the selling price or repeat ingredient costs here. Costs not entered are not included. Item costs and HPP per product are rounded to whole rupiah.</p>
            </div></details>
            <div class="grid gap-4 rounded-2xl bg-[#eee8dd] p-5 sm:grid-cols-2"><div v-if="Number(form.costing.batch_yield) > 1"><p class="text-xs text-stone-600">Total recipe cost</p><p class="mt-2 text-2xl font-semibold">{{ estimate.value ? money(estimate.value.batchCost) : '—' }}</p></div><div><p class="text-xs text-stone-600">Estimated HPP per product</p><p class="mt-2 text-2xl font-semibold">{{ estimate.value ? money(estimate.value.unitCost) : '—' }}</p></div></div>
            <details :open="Boolean(form.errors['costing.target_markup'])" class="rounded-2xl border border-stone-200 p-5"><summary class="cursor-pointer font-semibold">Price suggestion · Optional</summary><div class="mt-4"><p class="mt-2 text-xs leading-6 text-stone-500">Add your target percentage to HPP. Example: HPP Rp8.000 + 100% = selling price Rp16.000. This is before any costs not included in HPP.</p><label for="target-markup" class="mt-4 block text-sm font-medium">Target profit from HPP (%) · Optional</label><input id="target-markup" v-model="form.costing.target_markup" type="number" min="0" max="1000000" step="0.01" placeholder="e.g. 100" class="mt-2 w-full max-w-48 rounded-xl border border-stone-300 bg-white px-4 py-3"><div v-if="estimate.value?.suggestedPrice != null" class="mt-4 flex flex-wrap items-center justify-between gap-4"><p class="text-2xl font-semibold">{{ money(estimate.value.suggestedPrice) }}</p><button type="button" class="rounded-xl bg-[#504941] px-4 py-3 text-sm font-medium text-white" @click="form.price = estimate.value.suggestedPrice; emit('price-applied', estimate.value.suggestedPrice)">Use as base price</button></div><p v-if="estimate.value && (form.costing.target_markup === '' || form.costing.target_markup == null)" class="mt-3 text-sm text-stone-500">Your HPP is ready. Enter a target profit percentage above to display a price suggestion.</p><p v-if="estimate.suggestionError" role="status" class="mt-3 text-sm text-amber-800">{{ estimate.suggestionError }}</p><p v-if="estimate.value?.priceLimitExceeded" class="mt-3 text-sm text-amber-800">The suggested price exceeds the supported base-price limit. Review your costs and target profit.</p><p v-if="estimate.value && form.price !== '' && Number(form.price) < estimate.value.unitCost" class="mt-3 text-sm text-amber-800">Your base price is below your estimated HPP.</p></div></details>
            <p v-if="estimate.error" role="status" class="rounded-xl bg-amber-50 p-4 text-sm text-amber-800">{{ estimate.error }}</p>
            <div v-if="errors.length" role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-700"><p v-for="[key, message] in errors" :key="key">{{ message }}</p></div>
            <button type="button" class="text-xs text-red-700 underline" @click="form.costing = null">Remove HPP calculation when saving</button>
        </div>
    </section>
</template>
