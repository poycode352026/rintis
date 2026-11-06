<script setup>
import DeliveryMotion from '../../Components/DeliveryMotion.vue';
import DeliveryWindow from '../../Components/DeliveryWindow.vue';
import { Head, Link, router, usePoll } from '@inertiajs/vue3';
import ProductRating from '../../Components/ProductRating.vue';
import PickupPass from '../../Components/PickupPass.vue';
import PickupMap from '../../Components/PickupMap.vue';
import OrderProgress from '../../Components/OrderProgress.vue';
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue';
import Dialog from '../../Components/Shared/Dialog.vue';
import { money } from '../../Support/cart.js';
import { paymentCountdown } from '../../Support/paymentExpiry.js';
const copyMessage = ref('');
const retrying = ref(false);
const retryPayment = () => {
    if (retrying.value || !props.order.paymentRetryUrl) return;
    clearTimeout(retryTimer); retryTimer = null;
    retrying.value = true;
    router.post(props.order.paymentRetryUrl, {}, { async: true, preserveScroll: true, onFinish: () => { retrying.value = false; scheduleRetry(); } });
};
const copyAccount = async () => { try { await navigator.clipboard.writeText(props.order.bank_account.account_number); copyMessage.value = 'Account number copied.'; } catch { copyMessage.value = 'Copy failed. Please select and copy the account number below.'; } };
const props = defineProps({ order: Object, storeName: String, backLabel: String, backUrl: String, mapTilesUrl: String, serverNow: String });
const clock = ref(Date.now());
let clockTimer;
const countdown = computed(() => paymentCountdown(props.order.bank_account?.expires_at, clock.value + (Date.parse(props.serverNow || '') - receivedAt || 0)));
let receivedAt = Date.now();
watch(() => props.serverNow, () => { receivedAt = Date.now(); });
watch(() => countdown.value?.expired, expired => { if (expired && mounted) refreshStatus(); });
const autoAttempts = ref(0);
let retryTimer = null;
let mounted = false;
const missingVa = computed(() => props.order.bank_account?.provider === 'midtrans' && !(props.order.payment_method === 'qris' ? props.order.bank_account.qr_url : props.order.bank_account.account_number) && props.order.payment_status !== 'paid' && props.order.status !== 'expired' && !['expire', 'cancel', 'deny'].includes(props.order.bank_account.status));
const retryable = computed(() => !['credentials', 'response_mismatch'].includes(props.order.bank_account?.last_error?.kind));
const scheduleRetry = () => {
    if (!mounted || retryTimer || retrying.value || !missingVa.value || !retryable.value || autoAttempts.value >= 3) return;
    const delay = [3000, 10000, 25000][autoAttempts.value];
    retryTimer = setTimeout(() => { retryTimer = null; autoAttempts.value++; retryPayment(); }, delay);
};
watch(() => props.order.bank_account, () => {
    if (!missingVa.value || !retryable.value) { clearTimeout(retryTimer); retryTimer = null; }
    else scheduleRetry();
}, { deep: true });
const paymentSuccessOpen = ref(false);
const syncError = ref('');
const refreshing = ref(false);
const refreshMessage = ref('');
const refreshStatus = (manual = false) => {
    if (refreshing.value || retrying.value) return;
    const options = { only: ['order'], async: true, preserveScroll: true,
        onStart: () => { refreshing.value = true; refreshMessage.value = ''; },
        onSuccess: () => { syncError.value = ''; if (manual) refreshMessage.value = 'Status updated.'; },
        onError: () => { syncError.value = 'Status could not be updated. Please check your connection and retry.'; },
        onFinish: () => { refreshing.value = false; },
    };
    if (manual && props.order.paymentRefreshUrl) router.post(props.order.paymentRefreshUrl, {}, { ...options, only: ['order', 'serverNow'] });
    else router.reload({ ...options, only: ['order', 'serverNow'] });
};
const refreshWhenVisible = () => { if (!document.hidden) refreshStatus(); };
onMounted(() => {
    mounted = true; scheduleRetry(); clockTimer = setInterval(() => { clock.value = Date.now(); }, 1000);
    document.addEventListener('visibilitychange', refreshWhenVisible);
    window.addEventListener('focus', refreshWhenVisible);
    window.addEventListener('online', refreshWhenVisible);
});
onBeforeUnmount(() => {
    mounted = false; clearTimeout(retryTimer); clearInterval(clockTimer);
    document.removeEventListener('visibilitychange', refreshWhenVisible);
    window.removeEventListener('focus', refreshWhenVisible);
    window.removeEventListener('online', refreshWhenVisible);
});
const successKey = () => `rintis.payment-success.${props.order.reference}`;
watch(() => props.order.payment_status, (status) => {
    if (status !== 'paid' || props.order.payment_method === 'cash' || props.order.status === 'completed') return;
    try { if (sessionStorage.getItem(successKey())) return; } catch { /* Confirmation still works without storage. */ }
    paymentSuccessOpen.value = true;
}, { immediate: true });
const closePaymentSuccess = () => {
    paymentSuccessOpen.value = false;
    try { sessionStorage.setItem(successKey(), 'seen'); } catch { /* No private customer data is persisted. */ }
};
usePoll(15000, { only: ['order', 'serverNow'], onBefore: () => !retrying.value, onSuccess: () => { syncError.value = ''; }, onError: () => { syncError.value = 'Status could not be updated. Please check your connection and retry.'; } }, { keepAlive: true, mode: 'rest' });
const expired = computed(() => props.order.status === 'expired' || (props.order.payment_method === 'qris' && props.order.payment_status !== 'paid' && countdown.value?.expired));
const awaitingPayment = computed(() => props.order.payment_method !== 'cash' && props.order.payment_status !== 'paid');
const readyPickup = computed(() => props.order.status === 'ready' && props.order.method === 'pickup');
const headline = computed(() => props.order.status === 'cancelled' ? 'Order cancelled.' : expired.value ? 'Order expired.' : awaitingPayment.value ? 'Complete your payment.' : ({ completed: props.order.method === 'delivery' ? 'Order delivered.' : 'Order collected.', received: 'Order received.', preparing: 'Your order is being prepared.', ready: props.order.method === 'pickup' ? 'Ready for pickup.' : 'Ready for delivery.', out_for_delivery: props.order.deliveryStarted ? 'Your order is on the way.' : 'Ready for delivery.' })[props.order.status] || 'Your order.');
const message = computed(() => props.order.status === 'cancelled' ? 'This order was cancelled by the seller. No cash payment is due.' : expired.value ? 'The QRIS payment deadline has passed. This unpaid order has expired and will not be prepared.' : props.order.status === 'completed' ? (props.order.method === 'delivery' ? 'Your order has been delivered. Thank you!' : 'Your order has been collected. Thank you!') : props.order.status === 'ready' && props.order.method === 'pickup' ? 'Your order is ready. You can now collect it at the pickup location below.' : props.order.status === 'ready' ? 'Your order is ready and waiting to be dispatched by the seller.' : props.order.status === 'out_for_delivery' ? (props.order.deliveryStarted ? 'The seller has started the delivery trip.' : 'Your order is ready and waiting for the delivery trip to start.') : props.order.status === 'preparing' ? 'The seller is preparing your order. Please wait for the ready notification here.' : props.order.payment_method !== 'cash' && props.order.payment_status === 'unpaid' ? 'Complete your payment first. The seller can prepare your order after payment is verified.' : props.order.method === 'pickup' ? 'Please wait for the seller to prepare your order. Come to collect it once it is marked Ready for pickup.' : 'Please wait for the seller to prepare and dispatch your order.');
</script>
<template><Head title="Order received"><meta name="robots" content="noindex, nofollow"/></Head><div class="min-h-screen bg-[#f5f2ec] px-5 py-12 text-[#504941]"><main class="mx-auto max-w-2xl rounded-3xl border border-stone-200 bg-[#fffdf9] p-6 sm:p-10"><div class="flex size-12 items-center justify-center rounded-full text-xl" :class="awaitingPayment ? 'bg-amber-50 text-amber-800' : 'bg-green-100 text-green-800'" aria-hidden="true">{{ awaitingPayment ? '◷' : '✓' }}</div><p class="mt-6 text-xs uppercase tracking-widest text-stone-500">{{ storeName }}</p><h1 class="mt-3 text-3xl font-semibold">{{ headline }}</h1><p role="status" aria-live="polite" class="mt-4 text-sm leading-6 text-stone-600">{{ message }}</p><p v-if="order.delivery?.delivery_service_name" class="mt-5 text-sm font-semibold">{{ order.delivery.delivery_service_name }} delivery</p><DeliveryWindow v-if="order.delivery && order.status !== 'cancelled'" :window="{ startsAt: order.delivery.delivery_starts_at, endsAt: order.delivery.delivery_ends_at }" class="mt-5"/><DeliveryMotion v-if="order.status === 'out_for_delivery' && order.deliveryStarted" class="mt-6"/><ProductRating v-if="order.reviewUrl" :items="order.items" :reviews="order.reviews" :submit-url="order.reviewUrl"/><section v-if="order.payment_method === 'qris' && order.payment_status !== 'paid' && order.bank_account?.qr_url && !expired && !countdown?.expired && !['expire', 'cancel', 'deny'].includes(order.bank_account.status)" class="mt-6 rounded-2xl border border-stone-200 bg-white p-5 text-center"><h2 class="font-semibold">Pay with QRIS</h2><img :src="order.bank_account.qr_url" referrerpolicy="no-referrer" alt="Scan QRIS to pay for this order" class="mx-auto mt-4 w-64 max-w-full rounded-xl"><p class="mt-4 text-xl font-semibold">{{ money(order.total) }}</p><p class="mt-2 text-sm text-stone-500">Scan using your banking or e-wallet app.</p></section><section v-if="order.payment_status !== 'paid' && order.bank_account && order.bank_account.account_number && !['expire', 'cancel', 'deny'].includes(order.bank_account.status) && !countdown?.expired" class="mt-7 rounded-2xl bg-stone-100 p-5"><h2 class="font-semibold">{{ order.bank_account.provider === 'midtrans' ? 'Virtual Account' : 'Bank transfer' }} · {{ order.payment_status === 'unpaid' ? 'Unpaid' : order.payment_status }}</h2><p class="mt-3 text-sm">{{ order.bank_account.bank_name }} · {{ order.bank_account.account_holder }}</p><div class="mt-3 flex flex-wrap items-center justify-between gap-3"><p class="select-all break-all font-mono text-xl font-semibold">{{ order.bank_account.account_number }}</p><button type="button" class="rounded-full border border-stone-300 bg-white px-4 py-2 text-xs font-medium" @click="copyAccount">Copy account number</button></div><p v-if="copyMessage" role="status" class="mt-2 text-xs text-stone-600">{{ copyMessage }}</p><p class="mt-3 text-sm leading-6 text-stone-500">Transfer {{ money(order.total) }} to this account.</p></section><section v-if="order.bank_account?.provider === 'midtrans' && order.payment_status !== 'paid' && !expired" class="mt-5 rounded-2xl border border-stone-200 p-5 text-sm"><p v-if="missingVa" role="status">{{ !retryable ? 'Payment setup needs attention from the store. Your order is saved; please contact the seller.' : autoAttempts < 3 ? 'Preparing your payment. We will retry automatically; please keep this page open.' : 'Your payment is taking longer than expected. Your order is saved. You can safely retry below.' }}</p><p v-if="['expire', 'cancel', 'deny'].includes(order.bank_account.status)" role="alert" class="text-amber-800">This payment is {{ order.bank_account.status }}. Please contact the store; do not transfer to this number.</p><div v-if="countdown" class="flex flex-wrap items-center justify-between gap-3"><span class="text-stone-500">{{ countdown.expired ? 'Payment time ended' : 'Pay within' }}</span><strong class="font-mono text-2xl tabular-nums tracking-wide" :class="countdown.seconds <= 300 ? 'text-amber-800' : 'text-stone-800'">{{ countdown.label }}</strong></div><button v-if="missingVa && (autoAttempts >= 3 || !retryable)" type="button" :disabled="retrying" class="mt-3 rounded-full bg-[#504941] px-5 py-3 font-medium text-white disabled:opacity-40" @click="retryPayment">{{ retrying ? 'Retrieving…' : 'Retry payment' }}</button></section><PickupPass v-if="order.pickupPass" :code="order.pickupPass" :reference="order.reference"/><section v-if="readyPickup && order.pickup" class="mt-7 overflow-hidden rounded-3xl border border-stone-200"><div class="bg-[#edf2e7] p-5"><span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-[#60734c]">✓ {{ order.status === 'completed' ? 'Collected' : 'Ready for pickup' }}</span><h2 class="mt-4 text-xl font-semibold">{{ order.status === 'completed' ? 'Pickup location' : 'Collect your order here.' }}</h2><p class="mt-2 text-sm font-medium">{{ storeName }}</p></div><div class="space-y-5 p-5"><div class="flex items-start gap-3"><svg aria-hidden="true" viewBox="0 0 24 24" class="mt-0.5 size-5 shrink-0 text-stone-500" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg><p class="whitespace-pre-line text-sm leading-6">{{ [order.pickup.address_line, order.pickup.district, order.pickup.city, order.pickup.province].filter(Boolean).join(', ') }}</p></div><p v-if="order.pickup.pickup_instructions" class="rounded-xl bg-stone-50 p-4 text-sm leading-6">{{ order.pickup.pickup_instructions }}</p><PickupMap :location="order.pickup" :tiles-url="mapTilesUrl"/></div></section><details :open="order.status !== 'cancelled' && !readyPickup && !awaitingPayment && !expired && order.status !== 'completed'" class="mt-6"><summary class="cursor-pointer rounded-xl border border-stone-200 p-4 text-sm font-medium">View order details</summary><div class="mt-6"><OrderProgress v-if="!expired" :order="order"/></div><div class="mt-3 flex items-center justify-between gap-3 text-xs text-stone-500"><span>Updates automatically every 15 seconds.</span><button type="button" :disabled="refreshing || retrying" :aria-busy="refreshing" class="inline-flex min-h-10 items-center justify-center rounded-full border border-stone-300 bg-white px-4 py-2 font-medium text-stone-700 transition-colors hover:bg-stone-50 disabled:cursor-wait disabled:opacity-50" @click="refreshStatus(true)">{{ refreshing ? 'Checking…' : retrying ? 'Retrieving payment…' : 'Refresh status' }}</button></div><p v-if="refreshMessage" role="status" aria-live="polite" class="mt-2 text-right text-xs text-green-800">{{ refreshMessage }}</p><p v-if="syncError" role="alert" class="mt-3 text-sm text-amber-800">{{ syncError }}</p><p class="mt-3 font-mono text-sm">{{ order.reference }}</p><p v-if="order.status === 'completed'" class="mt-5 rounded-2xl bg-green-50 p-5 text-sm text-green-800">✓ {{ order.method === 'delivery' ? 'Delivered' : 'Collected' }} · Payment received<span v-if="order.cash_received !== null" class="mt-2 block">Cash received {{ money(order.cash_received) }} · Change {{ money(order.cash_change) }}</span></p><p class="mt-4 text-sm leading-6 text-stone-500">Keep this private page link to pay and track this order. Anyone with the link can view its details.</p><div class="mt-6 border-y border-stone-200 py-5 text-sm"><p>{{ order.customer_name }} · {{ order.phone }}</p><p class="mt-2 capitalize">{{ order.method }} · {{ order.payment_method === 'cash' ? 'Cash on ' + order.method : order.payment_method === 'qris' ? 'QRIS' : 'Bank transfer' }}</p></div><article v-for="(item, index) in order.items" :key="index" class="flex justify-between gap-5 border-b border-stone-100 py-4 text-sm"><div><p>{{ item.quantity }} × {{ item.name }}</p><p class="mt-1 text-xs leading-5 text-stone-500">{{ item.choices.map(choice => `${choice.group}: ${choice.name}`).join(' · ') }}</p></div><span class="shrink-0">{{ money(item.line_total) }}</span></article><p class="mt-5 flex justify-between text-sm"><span>Delivery</span><span>{{ money(order.delivery_fee) }}</span></p><p class="mt-4 flex justify-between text-xl font-semibold"><span>{{ order.status === 'cancelled' ? 'Order total' : order.payment_status === 'paid' ? 'Paid amount' : order.payment_method === 'cash' ? 'Cash due' : 'Payment amount' }}</span><span>{{ money(order.total) }}</span></p><p v-if="order.method === 'pickup' && !readyPickup" class="mt-7 rounded-2xl border border-dashed border-stone-300 p-5 text-sm leading-6 text-stone-500">Your pickup address and map will appear here when the seller marks your order ready for pickup.</p><section v-if="order.delivery" class="mt-7 rounded-2xl bg-stone-100 p-5"><h2 class="font-semibold">Delivery address</h2><p class="mt-3 whitespace-pre-line text-sm leading-6">{{ order.delivery.address_line }}</p></section></details><Link :href="backUrl" class="mt-8 inline-block rounded-full border border-stone-300 px-5 py-3 text-sm font-medium">← {{ backLabel || 'Back to menu' }}</Link></main><Dialog :open="paymentSuccessOpen" title="Payment successful" @close="closePaymentSuccess"><div class="mx-auto flex size-16 items-center justify-center rounded-full bg-green-100 text-3xl text-green-800" aria-hidden="true">✓</div><p class="mt-5 text-center text-2xl font-semibold">{{ money(order.total) }}</p><p class="mt-3 text-center text-sm leading-6 text-stone-600">Your payment has been verified. The seller can now prepare your order.</p><button type="button" class="mt-6 w-full rounded-full bg-[#504941] px-5 py-3 text-sm font-semibold text-white" @click="closePaymentSuccess">Track my order</button></Dialog></div></template>
