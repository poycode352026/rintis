<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
const props = defineProps({ form: Object, endpoint: String });
const emit = defineEmits(['focus']);
const provinces = ref([]); const cities = ref([]); const districts = ref([]);
const busy = ref(false); const error = ref('');
let revision = 0;
let disposed = false;
const normalize = (name) => (name ?? '').toLowerCase().replace(/^(kota|kabupaten|kab\.)\s+/, '').trim();
const fetchRows = async (level, parent = '') => {
    const url = new URL(props.endpoint); url.searchParams.set('level', level); if (parent) url.searchParams.set('parent', parent);
    const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    if (!response.ok) throw new Error('Region options could not be loaded. Your saved address has not been changed.');
    return (await response.json()).data;
};
const find = (rows, code, name) => rows.find((row) => row.id === code) ?? rows.find((row) => normalize(row.name) === normalize(name));
const initialize = async () => {
    const version = ++revision; busy.value = true; error.value = '';
    try {
        const ps = await fetchRows('provinces'); if (version !== revision || disposed) return; provinces.value = ps;
        const p = find(ps, props.form.province_code, props.form.province); if (!p) return;
        const cs = await fetchRows('cities', p.id); if (version !== revision || disposed) return; cities.value = cs;
        const c = find(cs, props.form.city_code, props.form.city);
        let ds = []; let d;
        if (c) { ds = await fetchRows('districts', c.id); if (version !== revision || disposed) return; districts.value = ds; d = find(ds, props.form.district_code, props.form.district); }
        if (!props.form.province_code && !(c && d)) { cities.value = []; districts.value = []; }
        if (c && d) { props.form.province_code = p.id; props.form.province = p.name; props.form.city_code = c.id; props.form.city = c.name; props.form.district_code = d.id; props.form.district = d.name; }
    } catch (exception) { if (!disposed && version === revision) error.value = exception.message; }
    finally { if (!disposed && version === revision) busy.value = false; }
};
const changed = async (level) => {
    const version = ++revision; busy.value = true; error.value = '';
    const rows = level === 'province' ? provinces.value : level === 'city' ? cities.value : districts.value;
    const selected = rows.find((row) => row.id === props.form[`${level}_code`]); props.form[level] = selected?.name ?? '';
    props.form.latitude = ''; props.form.longitude = '';
    if (selected?.lat !== null && selected?.lng !== null && selected) emit('focus', { lat: selected.lat, lng: selected.lng, zoom: level === 'province' ? 7 : level === 'city' ? 12 : 14 });
    if (level === 'province') { props.form.city_code = ''; props.form.city = ''; cities.value = []; }
    if (level !== 'district') { props.form.district_code = ''; props.form.district = ''; districts.value = []; }
    try {
        if (selected && level !== 'district') { const list = await fetchRows(level === 'province' ? 'cities' : 'districts', selected.id); if (version !== revision || disposed) return; if (level === 'province') cities.value = list; else districts.value = list; }
    } catch (exception) { if (!disposed && version === revision) error.value = exception.message; }
    finally { if (!disposed && version === revision) busy.value = false; }
};
onMounted(initialize); onBeforeUnmount(() => { disposed = true; revision++; });
</script>

<template><div class="grid gap-5 sm:grid-cols-2"><div v-for="field in [{ key: 'province', label: 'Province', rows: provinces }, { key: 'city', label: 'City / Regency', rows: cities }, { key: 'district', label: 'District', rows: districts }]" :key="field.key" :class="field.key === 'district' ? 'sm:col-span-2' : ''"><label :for="field.key" class="text-sm font-medium">{{ field.label }}</label><select :id="field.key" v-model="form[`${field.key}_code`]" :disabled="busy || !field.rows.length" :required="form.is_listed && !form[field.key]" :aria-invalid="Boolean(form.errors[`${field.key}_code`] || form.errors[field.key])" class="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm disabled:bg-stone-50 disabled:text-stone-400" @change="changed(field.key)"><option value="">{{ !form[`${field.key}_code`] && form[field.key] ? `${form[field.key]} (saved)` : `Select ${field.label.toLowerCase()}` }}</option><option v-for="row in field.rows" :key="row.id" :value="row.id">{{ row.name }}</option></select><p v-if="form.errors[`${field.key}_code`] || form.errors[field.key]" role="alert" class="mt-2 text-xs text-red-700">{{ form.errors[`${field.key}_code`] || form.errors[field.key] }}</p></div><p v-if="busy" role="status" class="text-xs text-stone-500 sm:col-span-2">Loading region options…</p><p v-if="error" role="alert" class="rounded-xl bg-amber-50 p-4 text-xs leading-6 text-amber-900 sm:col-span-2">{{ error }} <button type="button" class="font-medium underline" @click="initialize">Retry</button></p><p class="text-xs text-stone-400 sm:col-span-2">Region data: <a href="https://github.com/emsifa/api-wilayah-indonesia" target="_blank" rel="noopener noreferrer" class="underline">API Wilayah Indonesia</a></p></div></template>
