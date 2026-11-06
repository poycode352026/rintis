<script setup>
import { computed } from 'vue';
const props = defineProps({ order: Object });
const awaitingPayment = computed(() => props.order.payment_method !== 'cash' && props.order.payment_status !== 'paid');
const steps = computed(() => [
    { status: 'received', label: 'Order placed' },
    ...(props.order.payment_method !== 'cash' ? [{ status: 'payment', label: props.order.payment_status === 'paid' ? 'Payment confirmed' : 'Awaiting payment' }] : []),
    { status: 'preparing', label: 'Preparing' },
    { status: 'ready', label: props.order.method === 'pickup' ? 'Ready for pickup' : 'Ready for delivery' },
    ...(props.order.method === 'delivery' ? [{ status: 'out_for_delivery', label: 'On the way' }] : []),
    { status: 'completed', label: 'Completed' },
]);
const effectiveStatus = computed(() => props.order.status === 'out_for_delivery' && props.order.deliveryStarted === false ? 'ready' : props.order.status);
const position = computed(() => awaitingPayment.value ? 1 : props.order.status === 'received' && props.order.payment_status === 'paid' && props.order.payment_method !== 'cash' ? 1 : steps.value.findIndex(step => step.status === effectiveStatus.value));
const time = status => {
    const value = status === 'payment' ? props.order.bank_account?.paid_at : props.order.progress?.find(event => event.status === (status === 'out_for_delivery' && props.order.deliveryStarted ? 'delivery_started' : status))?.at;
    return value ? new Intl.DateTimeFormat('en-GB', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' }).format(new Date(value)) + ' WIB' : '';
};
</script>
<template>
<section aria-label="Order progress" class="overflow-hidden rounded-2xl border border-stone-200 bg-white p-5">
    <p v-if="order.status === 'cancelled'" class="text-sm text-stone-600">Order cancelled</p><p v-else-if="order.status === 'expired'" class="text-sm text-amber-800">Order expired · QRIS payment was not completed before the deadline.</p><ol v-else class="flex flex-col sm:flex-row">
        <li v-for="(step,index) in steps" :key="step.status" :aria-current="index === position ? 'step' : undefined" class="relative flex flex-1 gap-3 pb-5 last:pb-0 sm:flex-col sm:gap-3 sm:pb-0">
            <span v-if="index < steps.length - 1" class="absolute top-8 bottom-0 left-4 w-0.5 sm:top-4 sm:right-0 sm:bottom-auto sm:left-8 sm:h-0.5 sm:w-auto" :class="index < position ? 'bg-[#869873]' : 'bg-stone-100'"/>
            <span class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold" :class="index <= position ? 'bg-[#60734c] text-white' : 'bg-stone-100 text-stone-400'"><span v-if="index === position && order.status !== 'completed'" class="absolute inset-0 rounded-full ring-4 ring-[#869873]/20 motion-safe:animate-pulse"/>{{ index < position || order.status === 'completed' ? '✓' : index + 1 }}</span>
            <div class="pr-2"><p class="text-xs font-semibold leading-5" :class="index <= position ? 'text-stone-800' : 'text-stone-400'">{{ step.label }}</p><p v-if="index <= position && time(step.status)" class="mt-1 text-[11px] leading-4 text-stone-500">{{ time(step.status) }}</p></div>
        </li>
    </ol>
</section>
</template>
