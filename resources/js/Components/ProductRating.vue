<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
const props = defineProps({ items: Array, reviews: Array, submitUrl: String });
const products = computed(() => props.items.map((item, line_index) => ({ ...item, line_index })).filter((item, index, items) => items.findIndex(other => other.open_order_item_id === item.open_order_item_id) === index));
const form = useForm({ reviews: props.items.map((item, line_index) => ({ line_index, rating: 0 })) });
const saved = index => props.reviews?.find(review => props.items[review.line_index]?.open_order_item_id === props.items[index]?.open_order_item_id)?.rating;
const pending = computed(() => form.reviews.filter(review => products.value.some(item => item.line_index === review.line_index) && review.rating && !saved(review.line_index)));
const remaining = computed(() => products.value.some(item => !saved(item.line_index)));
watch(() => props.reviews, () => { form.reviews.forEach(review => { if (saved(review.line_index)) review.rating = 0; }); });
const submit = () => form.transform(() => ({ reviews: pending.value })).post(props.submitUrl, { preserveScroll: true });
</script>
<template>
<section class="mt-6 rounded-2xl border border-stone-200 bg-white p-5">
    <h2 class="text-xl font-semibold">{{ remaining ? 'How was your order?' : 'Thank you for your ratings!' }}</h2>
    <form @submit.prevent="submit" class="mt-4">
        <div v-for="item in products" :key="item.line_index" class="border-t border-stone-100 py-4 first:border-0">
            <p class="font-medium">{{ item.name }}</p>
            <p v-if="saved(item.line_index)" class="mt-3 text-xl text-amber-500" :aria-label="`${saved(item.line_index)} stars`">{{ '★'.repeat(saved(item.line_index)) }}</p>
            <fieldset v-else :disabled="form.processing" class="mt-3 flex gap-1"><legend class="sr-only">Rate {{ item.name }}</legend><label v-for="star in 5" :key="star" class="cursor-pointer rounded-lg p-1 text-3xl leading-none focus-within:ring-2 focus-within:ring-stone-500" :class="star <= form.reviews[item.line_index].rating ? 'text-amber-500' : 'text-stone-300'"><input v-model="form.reviews[item.line_index].rating" type="radio" :name="`rating-${item.line_index}`" :value="star" :aria-label="`${star} stars`" class="sr-only">★</label></fieldset>
        </div>
        <p v-for="(error,key) in form.errors" :key="key" role="alert" class="mt-2 text-sm text-red-700">{{ error }}</p>
        <button v-if="remaining" :disabled="!pending.length || form.processing" class="mt-4 w-full rounded-full bg-[#504941] px-5 py-3 text-sm text-white disabled:opacity-40">{{ form.processing ? 'Saving…' : 'Save ratings' }}</button>
    </form>
</section>
</template>
