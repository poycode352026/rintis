<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import 'leaflet/dist/leaflet.css';
const props = defineProps({ origin: Object, stops: Array, tilesUrl: String, selected: Number });
const emit = defineEmits(['select']);
const container = ref(null); const failed = ref(false); let map; let layer; let L; let disposed = false;
const point = location => [Number(location.latitude), Number(location.longitude)];
const render = () => {
    if (!map) return;
    layer.clearLayers(); const bounds = [];
    if (props.origin?.latitude !== null && props.origin?.latitude !== undefined) {
        const p = point(props.origin); bounds.push(p);
        L.marker(p, { icon: L.divIcon({ className: '', html: '<span style="display:grid;place-items:center;width:34px;height:34px;border:3px solid white;border-radius:12px;background:#504941;color:white;font-weight:700;box-shadow:0 2px 8px #0003">S</span>', iconSize: [34,34], iconAnchor: [17,17] }) }).bindTooltip('Store · Starting point').addTo(layer);
    }
    props.stops.forEach((stop, index) => {
        const p = point(stop.location); if (!p.every(Number.isFinite)) return;
        bounds.push(p);
        const color = stop.status === 'completed' ? '#8b987c' : stop.id === props.selected ? '#2563eb' : '#504941';
        const icon = L.divIcon({ className: '', html: `<span style="display:grid;place-items:center;width:38px;height:38px;border:3px solid white;border-radius:50%;background:${color};color:white;font-weight:700;box-shadow:0 2px 8px #0003">${index + 1}</span>`, iconSize: [38,38], iconAnchor: [19,19] });
        const label = document.createElement('span'); label.textContent = `${index + 1}. ${stop.customer_name}`;
        L.marker(p, { icon }).bindTooltip(label).on('click', () => emit('select', stop.id)).addTo(layer);
    });
    if (bounds.length) map.fitBounds(bounds, { padding: [40,40], maxZoom: 16 });
};
onMounted(async () => {
    try {
        L = (await import('leaflet')).default; if (disposed) return;
        map = L.map(container.value, { scrollWheelZoom: false });
        L.tileLayer(props.tilesUrl, { maxZoom: 19, referrerPolicy: 'origin', attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>' }).on('tileerror', () => { failed.value = true; }).addTo(map);
        layer = L.layerGroup().addTo(map); map.setView([0,110], 5); render();
    } catch { failed.value = true; }
});
watch(() => [props.stops, props.origin, props.selected], render, { deep: true });
onBeforeUnmount(() => { disposed = true; map?.remove(); });
</script>
<template><div class="overflow-hidden rounded-3xl border border-stone-200 bg-white"><div ref="container" role="region" aria-label="Delivery map with numbered customer stops" class="relative z-0 h-80 w-full sm:h-[440px]"/><div class="flex flex-wrap items-center gap-4 px-5 py-4 text-xs text-stone-500"><span>■ Store</span><span>● Numbered stops</span><span class="text-blue-700">● Selected destination</span></div><p v-if="failed" role="status" class="bg-amber-50 p-4 text-sm text-amber-800">Map tiles could not load. The stop list and navigation links remain available.</p></div></template>
