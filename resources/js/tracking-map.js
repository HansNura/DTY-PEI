import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const container = document.getElementById('tracking-map');
const dataElement = document.getElementById('tracking-data');

if (container && dataElement) {
    const points = JSON.parse(dataElement.textContent || '[]');

    const map = L.map(container, {
        scrollWheelZoom: true,
    }).setView([-2.5, 118.0], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    const markers = [];

    points.forEach((point) => {
        if (point.latitude === null || point.longitude === null) {
            return;
        }

        const marker = L.circleMarker([point.latitude, point.longitude], {
            radius: 9,
            color: '#3730a3',
            fillColor: '#6366f1',
            fillOpacity: 0.9,
            weight: 2,
        }).addTo(map);

        marker.bindPopup(
            `<strong>${escapeHtml(point.nomor_do)}</strong><br>`
            + `${escapeHtml(point.customer)}<br>`
            + `${escapeHtml(point.fleet)} &middot; ${escapeHtml(point.driver)}<br>`
            + `<span>${escapeHtml(point.status)}</span><br>`
            + `<a href="${escapeHtml(point.url)}">Lihat detail</a>`,
        );

        markers.push(marker);
    });

    if (markers.length > 0) {
        map.fitBounds(L.featureGroup(markers).getBounds().pad(0.25), {
            maxZoom: 12,
        });
    }
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[character]));
}
