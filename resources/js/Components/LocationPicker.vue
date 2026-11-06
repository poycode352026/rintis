<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
const props = defineProps({ tilesUrl: String, focusArea: Object, title: { type: String, default: 'Pin your store' } });
const latitude = defineModel('latitude');
const longitude = defineModel('longitude');
const container = ref(null);
const locating = ref(false);
const message = ref('');
const tileError = ref(false);
const hasPin = ref(false);
let map;
let marker;
let layer;
const coords = () => latitude.value !== '' && latitude.value !== null && latitude.value !== undefined && longitude.value !== '' && longitude.value !== null && longitude.value !== undefined && Number.isFinite(Number(latitude.value)) && Number.isFinite(Number(longitude.value)) ? [Number(latitude.value), Number(longitude.value)] : null;
const icon = L.divIcon({ className: '', iconSize: [40, 48], iconAnchor: [20, 45], html: '<svg width="40" height="48" viewBox="0 0 40 48" xmlns="http://www.w3.org/2000/svg"><path d="M20 46S3 28 3 19a17 17 0 0 1 34 0c0 9-17 27-17 27Z" fill="#504941" stroke="white" stroke-width="3"/><circle cx="20" cy="19" r="6" fill="white"/></svg>' });
const select = (point, move = false) => {
    latitude.value = point.lat.toFixed(7);
    longitude.value = point.lng.toFixed(7);
    updatePin();
    if (move) map.setView([point.lat, point.lng], 17);
};
const updatePin = () => {
    if (!map) return;
    const point = coords();
    hasPin.value = Boolean(point);
    if (!point) { if (marker) { marker.remove(); marker = null; } return; }
    if (marker) marker.setLatLng(point);
    else { marker = L.marker(point, { icon, draggable: true, autoPan: true, title: 'Location — drag to adjust' }).addTo(map); marker.on('dragend', () => select(marker.getLatLng())); }
};
const locate = () => {
    message.value = '';
    if (!navigator.geolocation) { message.value = 'Your browser cannot find your location. Choose a point on the map instead.'; return; }
    locating.value = true;
    navigator.geolocation.getCurrentPosition((position) => { if (map) select({ lat: position.coords.latitude, lng: position.coords.longitude }, true); locating.value = false; }, (error) => { message.value = error.code === 1 ? 'Location permission was denied. You can still choose a location on the map.' : 'Your location could not be found. Please choose a point on the map.'; locating.value = false; }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
};
onMounted(() => {
    const point = coords();
    map = L.map(container.value, { scrollWheelZoom: false }).setView(point ?? [-2.5, 118], point ? 16 : 4);
    layer = L.tileLayer(props.tilesUrl, { maxZoom: 19, referrerPolicy: 'origin', attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap contributors</a>' }).addTo(map);
    layer.on('tileerror', () => { tileError.value = true; });
    map.on('click', (event) => select(event.latlng));
    updatePin();
});
watch([latitude, longitude], updatePin);
watch(() => props.focusArea, (point) => { if (map && point && Number.isFinite(point.lat) && Number.isFinite(point.lng)) map.setView([point.lat, point.lng], point.zoom ?? 12); });
onBeforeUnmount(() => { map?.remove(); map = null; });
</script>

<template><div class="overflow-hidden rounded-2xl border border-stone-200 bg-white"><div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4"><div><p class="text-sm font-semibold">{{ title }}</p><p class="mt-1 text-xs leading-5 text-stone-500">Tap the map or drag the pin to the entrance.</p></div><button type="button" :disabled="locating" class="rounded-full bg-[#504941] px-4 py-2.5 text-xs font-medium text-white disabled:opacity-50" @click="locate">{{ locating ? 'Locating…' : '◎ Use current location' }}</button></div><div ref="container" role="region" aria-label="Interactive location map. Use current location or click the map to set a pin." class="relative z-0 h-80 w-full bg-stone-100" /><div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-xs"><span :class="hasPin ? 'text-green-700' : 'text-stone-500'" role="status">{{ hasPin ? '✓ Location selected. Drag it to fine-tune.' : 'No location selected yet.' }}</span><button type="button" class="text-stone-600 underline" @click="select(map.getCenter())">Place pin at map center</button><button v-if="hasPin" type="button" class="text-stone-500 underline" @click="latitude = ''; longitude = ''">Clear pin</button></div><p v-if="message" role="alert" class="border-t border-stone-100 px-5 py-3 text-xs leading-6 text-amber-800">{{ message }}</p><div v-if="tileError" role="status" class="border-t border-stone-100 px-5 py-3 text-xs text-amber-800">Map images could not load. Check your connection. <button type="button" class="underline" @click="tileError = false; layer.redraw()">Retry map</button></div></div></template>
