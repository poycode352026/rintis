<script setup>
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { normalizeStoreSlug } from "../../Support/storeIdentity.js";
import AppLayout from "../../Layouts/AppLayout.vue";
import FormField from "../../Components/Shared/FormField.vue";
import LocationPicker from "../../Components/LocationPicker.vue";
import RegionFields from "../../Components/RegionFields.vue";
import DistanceInput from "../../Components/Shared/DistanceInput.vue";
import RupiahInput from "../../Components/Shared/RupiahInput.vue";
import BankAccountsFields from "../../Components/BankAccountsFields.vue";
defineOptions({ layout: AppLayout });
const props = defineProps({
    store: Object,
    submitUrl: String,
    backUrl: String,
    categories: Array,
    bankAccounts: Array,
    regionsUrl: String,
    mapTilesUrl: String,
    availabilityUrl: String,
});
console.log(props);
const page = usePage();
const focusArea = ref(null);
const form = useForm({
    name: props.store?.name ?? "",
    slug: props.store?.slug ?? "",
    tagline: props.store?.tagline ?? "",
    is_listed: true,
    category_id: props.store?.category_id ?? "",
    province: props.store?.province ?? "",
    city: props.store?.city ?? "",
    district: props.store?.district ?? "",
    province_code: props.store?.province_code ?? "",
    city_code: props.store?.city_code ?? "",
    district_code: props.store?.district_code ?? "",
    address_line: props.store?.address_line ?? "",
    latitude: props.store?.latitude ?? "",
    longitude: props.store?.longitude ?? "",
    pickup_enabled: props.store?.pickup_enabled ?? false,
    pickup_instructions: props.store?.pickup_instructions ?? "",
    delivery_enabled: props.store?.delivery_enabled ?? false,
    delivery_fee_mode: props.store?.delivery_fee_mode ?? "",
    priority_enabled: props.store?.delivery_options?.priority?.enabled ?? false,
    priority_fee: props.store?.delivery_options?.priority?.fee ?? 0,
    priority_minutes: props.store?.delivery_options?.priority?.minutes ?? 30,
    hemat_enabled: props.store?.delivery_options?.hemat?.enabled ?? false,
    hemat_fee: props.store?.delivery_options?.hemat?.fee ?? 0,
    hemat_minutes: props.store?.delivery_options?.hemat?.minutes ?? 60,
    delivery_fixed_fee: props.store?.delivery_fixed_fee ?? "",
    delivery_rate_per_km: props.store?.delivery_rate_per_km ?? "",
    delivery_min_distance_m: props.store?.delivery_min_distance_m ?? "",
    delivery_max_distance_m: props.store?.delivery_max_distance_m ?? "",
    cash_enabled: props.store?.cash_enabled ?? true,
    bank_transfer_enabled: props.store?.bank_transfer_enabled ?? true,
    deleted_bank_account_ids: [],
    bank_accounts: (props.bankAccounts ?? []).map(
        ({ id, bank_name, account_holder, account_number, is_active }) => ({
            id,
            bank_name,
            account_holder,
            account_number,
            is_active,
        }),
    ),
});
const customSlug = ref(Boolean(props.store));
const onSlugInput = (event) => {
    customSlug.value = true;
    const normalized = normalizeStoreSlug(event.target.value, false);
    event.target.value = normalized;
    form.slug = normalized;
};
watch(
    () => form.name,
    (name) => {
        if (!customSlug.value) form.slug = normalizeStoreSlug(name);
    },
);
const availability = ref({
    checking: false,
    nameAvailable: null,
    slugAvailable: null,
    message: "",
});
let identityTimer;
let identityRequest;
watch(
    () => [form.name, form.slug],
    ([name, slug], _, onCleanup) => {
        clearTimeout(identityTimer);
        identityRequest?.abort();
        availability.value = {
            checking: false,
            nameAvailable: null,
            slugAvailable: null,
            message: "",
        };
        if (
            !name.trim() ||
            name.trim().length > 100 ||
            !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug) ||
            slug.length < 3 ||
            slug.length > 80
        )
            return;
        const controller = new AbortController();
        identityRequest = controller;
        availability.value.checking = true;
        identityTimer = setTimeout(async () => {
            try {
                const url = new URL(props.availabilityUrl);
                url.searchParams.set("name", name);
                url.searchParams.set("slug", slug);
                const response = await fetch(url, {
                    headers: { Accept: "application/json" },
                    credentials: "same-origin",
                    signal: AbortSignal.any([
                        controller.signal,
                        AbortSignal.timeout(10000),
                    ]),
                });
                if (!response.ok) throw new Error();
                const result = await response.json();
                if (!controller.signal.aborted)
                    availability.value = {
                        ...result,
                        checking: false,
                        message: "",
                    };
            } catch (error) {
                if (!controller.signal.aborted)
                    availability.value = {
                        checking: false,
                        nameAvailable: null,
                        slugAvailable: null,
                        message:
                            "Availability check unavailable. Your name and URL will be verified when saving.",
                    };
            }
        }, 500);
        onCleanup(() => {
            clearTimeout(identityTimer);
            controller.abort();
        });
    },
    { immediate: true },
);
onBeforeUnmount(() => {
    clearTimeout(identityTimer);
    identityRequest?.abort();
});
const identityBlocked = computed(
    () =>
        availability.value.checking ||
        availability.value.nameAvailable === false ||
        availability.value.slugAvailable === false,
);
const rateUnit = ref("km");
const deliveryRate = computed({
    get: () =>
        form.delivery_rate_per_km === "" || form.delivery_rate_per_km == null
            ? ""
            : Number(form.delivery_rate_per_km) /
              (rateUnit.value === "m" ? 1000 : 1),
    set: (value) => {
        form.delivery_rate_per_km =
            value === ""
                ? ""
                : Number(value) * (rateUnit.value === "m" ? 1000 : 1);
    },
});
const changeRateUnit = (event) => {
    const next = event.target.value;
    if (
        next === "m" &&
        form.delivery_rate_per_km !== "" &&
        Number(form.delivery_rate_per_km) % 1000 !== 0
    ) {
        event.target.value = rateUnit.value;
        return;
    }
    rateUnit.value = next;
};
const sections = computed(() => [
    {
        id: "store-identity",
        label: "Identity",
        ready: Boolean(form.name && form.slug && form.category_id),
    },
    {
        id: "store-location",
        label: "Location",
        ready: Boolean(
            form.province && form.city && form.district && form.address_line,
        ),
    },
    {
        id: "store-fulfillment",
        label: "Pickup & delivery",
        ready: form.pickup_enabled || form.delivery_enabled,
    },
    {
        id: "store-payments",
        label: "Bank accounts",
        ready: form.bank_accounts.some(
            (bank) =>
                bank.bank_name &&
                bank.account_holder &&
                bank.account_number &&
                bank.is_active,
        ),
    },
]);
const submit = () => {
    if (identityBlocked.value) return;
    form.slug = normalizeStoreSlug(form.slug);
    return props.store
        ? form.patch(props.submitUrl, { preserveScroll: false })
        : form.post(props.submitUrl);
};
</script>

<template>
    <Head :title="store ? 'Store settings' : 'Create your store'" />
    <Link :href="backUrl" class="text-sm text-stone-500 hover:underline"
        >← {{ store ? "Store overview" : "Discover stores" }}</Link
    >
    <div class="mt-6">
        <p class="text-xs font-medium tracking-widest text-stone-400 uppercase">
            Your store / {{ store ? "Settings" : "Setup" }}
        </p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
            {{ store ? "Make it yours." : "A home for your store." }}
        </h1>
        <p class="mt-3 max-w-xl text-sm leading-7 text-stone-500">
            Your identity, location, pickup, delivery and bank accounts.
            Everything in one place.
        </p>
    </div>
    <p
        v-if="page.props.status && !form.isDirty"
        role="status"
        class="mt-6 rounded-xl border border-green-200 bg-green-50 px-5 py-3 text-sm text-green-800"
    >
        {{ page.props.status }}
    </p>
    <form
        class="mt-8 grid items-start gap-7 lg:grid-cols-[minmax(0,1fr)_260px]"
        @submit.prevent="submit"
    >
        <div class="min-w-0 space-y-6">
            <section
                id="store-identity"
                class="scroll-mt-32 rounded-3xl border border-stone-200 bg-[#fffdf9] p-6 sm:p-8"
            >
                <div class="mb-7 flex items-center gap-3">
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-[#eee8dd] text-sm font-semibold"
                        >01</span
                    >
                    <h2 class="text-xl font-semibold tracking-tight">
                        Store identity
                    </h2>
                </div>
                <div class="flex flex-col gap-5">
                    <FormField
                        v-model="form.name"
                        name="name"
                        label="Store name"
                        placeholder="Wemeet Coffee"
                        required
                        maxlength="100"
                        :error="
                            form.errors.name ||
                            (availability.nameAvailable === false
                                ? 'This store name is already in use.'
                                : '')
                        "
                    />
                    <div class="flex flex-col gap-2">
                        <label
                            for="slug"
                            class="text-sm font-medium text-stone-800"
                            >Store URL</label
                        >
                        <input
                            id="slug"
                            :value="form.slug"
                            name="slug"
                            required
                            minlength="3"
                            maxlength="80"
                            pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                            autocomplete="off"
                            placeholder="wemeet-coffee"
                            :aria-invalid="
                                Boolean(
                                    form.errors.slug ||
                                    availability.slugAvailable === false,
                                )
                            "
                            class="w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-base outline-none focus:border-stone-600 focus:ring-2 focus:ring-stone-200"
                            @input="onSlugInput"
                            @blur="form.slug = normalizeStoreSlug(form.slug)"
                        />
                        <p
                            v-if="
                                form.errors.slug ||
                                availability.slugAvailable === false
                            "
                            role="alert"
                            class="text-sm text-red-700"
                        >
                            {{
                                form.errors.slug ||
                                "This store URL is already in use."
                            }}
                        </p>
                        <p class="text-xs text-stone-500">
                            Lowercase letters, numbers and hyphens. At least 3
                            characters.
                        </p>
                    </div>
                    <p
                        v-if="availability.checking"
                        role="status"
                        class="text-xs text-stone-500"
                    >
                        Checking store name and URL…
                    </p>
                    <p
                        v-else-if="
                            availability.nameAvailable &&
                            availability.slugAvailable
                        "
                        role="status"
                        class="text-xs text-green-700"
                    >
                        Store name and URL are available.
                    </p>
                    <p
                        v-if="availability.message"
                        role="status"
                        class="text-xs text-amber-800"
                    >
                        {{ availability.message }}
                    </p>
                    <p class="-mt-3 break-all text-xs leading-6 text-stone-400">
                        /stores/{{ form.slug || "your-store" }} ·
                        Changing this changes your public link.
                    </p>
                    <FormField
                        v-model="form.tagline"
                        name="tagline"
                        label="Tagline (optional)"
                        placeholder="A little coffee, a brighter day."
                        maxlength="180"
                        :error="form.errors.tagline"
                    />
                    <div>
                        <label for="category_id" class="text-sm font-medium"
                            >Business category</label
                        ><select
                            id="category_id"
                            v-model="form.category_id"
                            required
                            :aria-invalid="Boolean(form.errors.category_id)"
                            class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm"
                        >
                            <option value="">Choose a category</option>
                            <option
                                v-for="category in categories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.category_id"
                            role="alert"
                            class="mt-2 text-sm text-red-700"
                        >
                            {{ form.errors.category_id }}
                        </p>
                    </div>
                </div>
            </section>
            <section
                id="store-location"
                class="scroll-mt-32 rounded-3xl border border-stone-200 bg-[#fffdf9] p-6 sm:p-8"
            >
                <div class="mb-7 flex items-center gap-3">
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-[#eee8dd] text-sm font-semibold"
                        >02</span
                    >
                    <h2 class="text-xl font-semibold tracking-tight">
                        Store location
                    </h2>
                </div>
                <RegionFields
                    :form="form"
                    :endpoint="regionsUrl"
                    @focus="focusArea = $event"
                />
                <div class="mt-6">
                    <FormField
                        v-model="form.address_line"
                        name="address_line"
                        label="Street address"
                        autocomplete="street-address"
                        required
                        maxlength="300"
                        :error="form.errors.address_line"
                    />
                </div>
                <div class="mt-6">
                    <LocationPicker
                        v-model:latitude="form.latitude"
                        v-model:longitude="form.longitude"
                        :tiles-url="mapTilesUrl"
                        :focus-area="focusArea"
                    />
                    <p
                        v-if="form.errors.latitude || form.errors.longitude"
                        role="alert"
                        class="mt-2 text-sm text-red-700"
                    >
                        {{ form.errors.latitude || form.errors.longitude }}
                    </p>
                    <p class="mt-3 text-xs leading-6 text-stone-400">
                        Select your region first, then pin the store entrance.
                        Current location works best while you are at the store.
                    </p>
                </div>
                <p
                    class="mt-5 rounded-xl bg-stone-50 p-4 text-xs leading-6 text-stone-500"
                >
                    Only your city and province appear publicly. Your full
                    address and store pin are reserved for pickup order details.
                </p>
            </section>
            <section
                id="store-fulfillment"
                class="scroll-mt-32 rounded-3xl border border-stone-200 bg-[#fffdf9] p-6 sm:p-8"
            >
                <h2 class="text-xl font-semibold">Pickup &amp; delivery</h2>
                <p class="mt-2 text-sm leading-6 text-stone-500">
                    Rintis uses open order by default. Set pickup and delivery
                    rules here; future open-order periods will keep their own
                    copy of these settings.
                </p>
                <div class="mt-6 space-y-6">
                    <div class="rounded-2xl border border-stone-200 p-5">
                        <label class="flex items-center gap-3 font-medium"
                            ><input
                                v-model="form.pickup_enabled"
                                type="checkbox"
                                class="size-4 accent-stone-700"
                            />Enable pickup</label
                        >
                        <p class="mt-2 text-xs leading-6 text-stone-500">
                            Customers collect their orders at your store. No
                            delivery fee.
                        </p>
                        <FormField
                            v-if="form.pickup_enabled"
                            v-model="form.pickup_instructions"
                            name="pickup_instructions"
                            label="Pickup instructions (optional)"
                            class="mt-4"
                            maxlength="1000"
                            :error="form.errors.pickup_instructions"
                        />
                        <p
                            v-if="form.errors.pickup_enabled"
                            role="alert"
                            class="mt-2 text-sm text-red-700"
                        >
                            {{ form.errors.pickup_enabled }}
                        </p>
                    </div>
                    <div class="rounded-2xl border border-stone-200 p-5">
                        <label class="flex items-center gap-3 font-medium"
                            ><input
                                v-model="form.delivery_enabled"
                                type="checkbox"
                                class="size-4 accent-stone-700"
                            />Enable delivery</label
                        >
                        <p
                            v-if="form.errors.delivery_enabled"
                            role="alert"
                            class="mt-2 text-sm text-red-700"
                        >
                            {{ form.errors.delivery_enabled }}
                        </p>
                        <div
                            v-if="form.delivery_enabled"
                            class="mt-5 space-y-5"
                        >
                            <p
                                class="rounded-xl bg-stone-100 p-4 text-xs leading-6"
                            >
                                Your store pin is the delivery starting point.
                                Distances will use road routes at checkout; the
                                map alone does not calculate delivery fees.
                            </p>
                            <div>
                                <label
                                    for="delivery_fee_mode"
                                    class="text-sm font-medium"
                                    >Delivery pricing</label
                                ><select
                                    id="delivery_fee_mode"
                                    v-model="form.delivery_fee_mode"
                                    required
                                    class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm"
                                >
                                    <option value="">Choose pricing</option>
                                    <option value="fixed">
                                        Fixed delivery fee
                                    </option>
                                    <option value="road_distance">
                                        Price by road distance
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors.delivery_fee_mode"
                                    role="alert"
                                    class="mt-2 text-sm text-red-700"
                                >
                                    {{ form.errors.delivery_fee_mode }}
                                </p>
                            </div>
                            <RupiahInput
                                v-if="form.delivery_fee_mode === 'fixed'"
                                v-model="form.delivery_fixed_fee"
                                name="delivery_fixed_fee"
                                label="Fixed delivery fee"
                                required
                                :error="form.errors.delivery_fixed_fee"
                            />
                            <div
                                v-if="
                                    form.delivery_fee_mode === 'road_distance'
                                "
                                class="rounded-2xl bg-stone-50 p-4"
                            >
                                <div class="mb-4">
                                    <label
                                        for="rate-unit"
                                        class="text-sm font-medium"
                                        >Charge per</label
                                    ><select
                                        id="rate-unit"
                                        :value="rateUnit"
                                        class="ml-3 rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm"
                                        @change="changeRateUnit"
                                    >
                                        <option value="km">Kilometer</option>
                                        <option
                                            value="m"
                                            :disabled="
                                                form.delivery_rate_per_km !==
                                                    '' &&
                                                Number(
                                                    form.delivery_rate_per_km,
                                                ) %
                                                    1000 !==
                                                    0
                                            "
                                        >
                                            Meter
                                        </option>
                                    </select>
                                </div>
                                <RupiahInput
                                    v-model="deliveryRate"
                                    name="delivery_rate_per_km"
                                    :label="
                                        rateUnit === 'km'
                                            ? 'Rate per kilometer'
                                            : 'Rate per meter'
                                    "
                                    required
                                    :error="form.errors.delivery_rate_per_km"
                                />
                                <p
                                    class="mt-2 text-xs leading-6 text-stone-500"
                                >
                                    Meter pricing uses whole rupiah. Keep
                                    kilometer pricing for rates such as Rp
                                    1.500/km.
                                </p>
                            </div>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <DistanceInput
                                    v-model="form.delivery_min_distance_m"
                                    name="delivery_min_distance_m"
                                    label="Minimum delivery distance"
                                    required
                                    :error="form.errors.delivery_min_distance_m"
                                /><DistanceInput
                                    v-model="form.delivery_max_distance_m"
                                    name="delivery_max_distance_m"
                                    label="Maximum delivery distance"
                                    required
                                    :error="form.errors.delivery_max_distance_m"
                                />
                            </div>
                            <p class="text-xs leading-6 text-stone-500">
                                Delivery is available only within this distance
                                range. Example: minimum 100 m and maximum 5 km
                                excludes a destination only 10 m away. There is
                                no minimum delivery fee. Distance pricing is
                                proportional and rounded to whole rupiah.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
            <section v-if="form.delivery_enabled" class="space-y-4 rounded-3xl border border-stone-200 bg-[#fffdf9] p-6"><h2 class="text-xl font-semibold">Default delivery choices</h2><p class="text-sm text-stone-500">Used for new sessions. Adjust them when opening a PO without changing these defaults.</p><div v-for="service in ['priority', 'hemat']" :key="service" class="rounded-2xl border border-stone-200 p-4"><label class="flex items-center gap-3 font-medium"><input v-model="form[`${service}_enabled`]" type="checkbox" class="accent-stone-700">{{ service === 'priority' ? 'Priority' : 'Hemat · Starts 15 minutes after closing' }}</label><div v-if="form[`${service}_enabled`]" class="mt-4 grid gap-4 sm:grid-cols-2"><RupiahInput v-model="form[`${service}_fee`]" :name="`default-${service}-fee`" :label="service === 'priority' ? 'Extra fee on top of Standard' : 'Hemat delivery fee'" required/><label class="text-sm">{{ service === 'priority' ? 'Estimated time from ordering (minutes)' : 'Estimated delivery duration (minutes)' }}<input v-model="form[`${service}_minutes`]" type="number" min="1" max="1440" required class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3"></label></div></div></section>
            <section
                id="store-payments"
                class="scroll-mt-32 rounded-3xl border border-stone-200 bg-[#fffdf9] p-6 sm:p-8"
            >
                <h2 class="text-xl font-semibold">Payment methods</h2><p class="mt-2 text-sm text-stone-500">Choose how customers can pay for new orders.</p><div class="my-5 grid gap-3 sm:grid-cols-2"><label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-stone-200 bg-white p-5"><input v-model="form.cash_enabled" type="checkbox" class="size-4 accent-stone-700"/><span><span class="block text-sm font-semibold">Cash</span><span class="mt-1 block text-xs text-stone-500">Pay on pickup or delivery</span></span></label><label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-stone-200 bg-white p-5"><input v-model="form.bank_transfer_enabled" type="checkbox" class="size-4 accent-stone-700"/><span><span class="block text-sm font-semibold">Digital payment</span><span class="mt-1 block text-xs text-stone-500">QRIS when payment connection is available</span></span></label></div><p v-if="!form.cash_enabled && !form.bank_transfer_enabled" class="mb-5 text-sm text-amber-800">Checkout is unavailable while both payment methods are off.</p><div class="border-t border-stone-200 pt-5"><BankAccountsFields :form="form" /></div>
            </section>
        </div>
        <aside
            class="rounded-3xl border border-stone-200 bg-[#fffdf9] p-6 lg:sticky lg:top-28"
        >
            <p
                class="text-xs font-medium tracking-widest text-stone-400 uppercase"
            >
                Your store
            </p>
            <h2 class="mt-3 break-words text-xl font-semibold">
                {{ form.name || "Let’s make it yours" }}
            </h2>
            <p
                class="mt-3 inline-flex rounded-full bg-stone-100 px-3 py-1 text-xs text-stone-500"
            >
                Open-order store
            </p>
            <nav
                aria-label="Store settings sections"
                class="mt-6 flex flex-col gap-2"
            >
                <a
                    v-for="section in sections"
                    :key="section.id"
                    :href="`#${section.id}`"
                    class="flex items-center justify-between rounded-xl px-3 py-3 text-sm hover:bg-stone-50"
                    ><span>{{ section.label }}</span
                    ><span
                        :class="
                            section.ready ? 'text-green-700' : 'text-stone-300'
                        "
                        aria-hidden="true"
                        >{{ section.ready ? "✓" : "○" }}</span
                    ><span class="sr-only">{{
                        section.ready ? "Configured" : "Not configured"
                    }}</span></a
                >
            </nav>
            <button
                :disabled="form.processing || identityBlocked"
                class="mt-6 w-full rounded-full bg-[#504941] px-5 py-3.5 text-sm font-semibold text-white transition-colors hover:bg-stone-700 disabled:opacity-50"
            >
                {{
                    form.processing
                        ? "Saving…"
                        : store
                          ? "Save settings"
                          : "Create store"
                }}
            </button>
            <p class="mt-3 text-center text-xs leading-5 text-stone-400">
                Your store page is public after creation. Orders will require an
                active open-order period.
            </p>
        </aside>
    </form>
</template>
