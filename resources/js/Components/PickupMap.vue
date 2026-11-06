<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import 'leaflet/dist/leaflet.css';
const props = defineProps({ location: Object, tilesUrl: String });
const container = ref(null); const failed = ref(false); let map; let disposed = false;
onMounted(async () => {
    try {
        const { default: L } = await import('leaflet');
        if (disposed) return;
        const point = [Number(props.location.latitude), Number(props.location.longitude)];
        map = L.map(container.value, { scrollWheelZoom: false }).setView(point, 16);
        L.tileLayer(props.tilesUrl, { maxZoom: 19, referrerPolicy: 'origin', attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noreferrer noopener">OpenStreetMap contributors</a>' }).on('tileerror', () => { failed.value = true; }).addTo(map);
        const icon = L.divIcon({ className: '', iconSize: [44,52], iconAnchor: [22,49], html: '<svg width="44" height="52" viewBox="0 0 44 52" xmlns="http://www.w3.org/2000/svg"><path d="M22 49S4 30 4 21a18 18 0 0 1 36 0c0 9-18 28-18 28Z" fill="#504941" stroke="white" stroke-width="3"/><circle cx="22" cy="21" r="7" fill="white"/></svg>' });
        const visit = () => window.open(`https://www.google.com/maps/dir/?api=1&destination=${point[0]},${point[1]}`, '_blank', 'noopener,noreferrer');
        L.marker(point, { icon, title: 'Open pickup location in Google Maps' }).addTo(map).on('click', visit);
        map.on('click', visit);
    } catch { failed.value = true; }
});
onBeforeUnmount(() => { disposed = true; map?.remove(); });
</script>
<template><div class="overflow-hidden rounded-2xl border border-stone-200 bg-stone-100"><div ref="container" role="region" aria-label="Map showing your pickup location" class="relative z-0 h-64 w-full sm:h-72"/><p v-if="failed" role="status" class="bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-800">The map could not load. You can still use the address or open the map below.</p><div class="flex flex-wrap items-center justify-between gap-3 bg-white px-4 py-3"><span class="text-xs text-stone-500">Store pickup point</span><a :href="`https://www.google.com/maps/dir/?api=1&destination=${location.latitude},${location.longitude}`" target="_blank" rel="noreferrer noopener" class="rounded-full bg-[#504941] px-4 py-2.5 text-xs font-medium text-white">Google Maps ↗</a></div></div></template>
