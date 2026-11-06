<script setup>
import { computed } from 'vue';
import RupiahInput from './Shared/RupiahInput.vue';
const props = defineProps({ form: Object });
const choice = () => ({ id: crypto.randomUUID(), name: '', price_adjustment: 0, cost_adjustment: 0 });
const addGroup = () => props.form.options.push({ id: crypto.randomUUID(), name: '', selection: 'single', required: true, choices: [choice()] });
const errors = computed(() => Object.entries(props.form.errors).filter(([key]) => key === 'options' || key.startsWith('options.')));
</script>
<template>
    <section class="border-t border-stone-200 pt-6">
        <div class="flex flex-wrap justify-between gap-4"><div><h2 class="text-xl font-semibold">Product choices</h2><p class="mt-2 text-sm leading-6 text-stone-500">Create groups such as Size, Strength, Sugar, Color or Extras. Each choice can add to the base selling price.</p></div><button type="button" :disabled="form.options.length >= 8" class="self-start rounded-xl border border-stone-300 px-4 py-3 text-sm font-medium disabled:opacity-50" @click="addGroup">+ Add option group</button></div>
        <p v-if="!form.options.length" class="mt-4 text-sm text-stone-500">No choices yet. Customers will see the standard product.</p>
        <div v-for="(group, groupIndex) in form.options" :key="group.id" class="mt-5 rounded-2xl border border-stone-200 bg-stone-50/50 p-5">
            <div class="flex items-start justify-between gap-4"><div class="min-w-0 flex-1"><label :for="`group-${group.id}`" class="text-sm font-medium">Group name</label><input :id="`group-${group.id}`" v-model="group.name" required maxlength="80" placeholder="e.g. Size" class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3"></div><button type="button" class="mt-8 text-xs text-red-700 underline" @click="form.options.splice(groupIndex, 1)">Remove group</button></div>
            <div class="mt-4 flex flex-wrap items-center gap-5"><label class="text-sm">Selection<select v-model="group.selection" class="ml-3 rounded-lg border border-stone-300 bg-white px-3 py-2"><option value="single">Choose one</option><option value="multiple">Choose multiple</option></select></label><label class="flex items-center gap-2 text-sm"><input v-model="group.required" type="checkbox" class="size-4 accent-stone-700">Required choice</label></div>
            <div v-for="(item, itemIndex) in group.choices" :key="item.id" class="mt-4 rounded-xl border border-stone-200 bg-white p-4"><div class="flex items-center justify-between gap-3"><label :for="`choice-${item.id}`" class="text-sm font-medium">Choice name</label><button type="button" class="text-xs text-red-700 underline" @click="group.choices.splice(itemIndex, 1)">Remove</button></div><input :id="`choice-${item.id}`" v-model="item.name" required maxlength="80" placeholder="e.g. Large, Strong, Less sugar" class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3"><div class="mt-4"><RupiahInput v-model="item.price_adjustment" :name="`extra-price-${item.id}`" label="Extra price charge" required /></div></div>
            <button type="button" :disabled="group.choices.length >= 20" class="mt-4 rounded-lg border border-stone-300 px-4 py-2 text-sm disabled:opacity-50" @click="group.choices.push(choice())">+ Add choice</button>
        </div>
        <p v-if="form.options.length" class="mt-4 text-xs leading-6 text-stone-500">Use 0 for no extra charge. Selected extras are added to the base price.</p>
        <div v-if="errors.length" role="alert" class="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-700"><p v-for="[key, message] in errors" :key="key">{{ message }}</p></div>
    </section>
</template>
