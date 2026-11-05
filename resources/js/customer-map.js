import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const container = document.getElementById('customer-map');
const latitudeInput = document.getElementById('latitude');
const longitudeInput = document.getElementById('longitude');
const clearButton = document.querySelector('[data-customer-clear]');
const mapStatus = document.querySelector('[data-customer-map-status]');

const setStatus = (message) => {
    if (mapStatus) {
        mapStatus.textContent = message;
    }
};

if (container && latitudeInput && longitudeInput) {
    const parseCoordinate = (value) => {
        const number = Number.parseFloat(String(value).replace(',', '.'));

        return Number.isFinite(number) ? number : null;
    };

    const initialLatitude = parseCoordinate(latitudeInput.value);
    const initialLongitude = parseCoordinate(longitudeInput.value);
    const hasInitialPoint = initialLatitude !== null && initialLongitude !== null;
    const defaultCenter = [-2.5, 118.0];

    const map = L.map(container, {
        scrollWheelZoom: false,
    }).setView(hasInitialPoint ? [initialLatitude, initialLongitude] : defaultCenter, hasInitialPoint ? 14 : 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    const markerIcon = L.divIcon({
        className: '',
        html: '<div style="width:28px;height:28px;border-radius:50% 50% 50% 0;background:#0d9488;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 3px 10px rgba(15,23,42,.35);"></div>',
        iconSize: [28, 28],
        iconAnchor: [14, 28],
    });

    let marker = null;

    const syncInputs = (latitude, longitude) => {
        latitudeInput.value = latitude.toFixed(7);
        longitudeInput.value = longitude.toFixed(7);
        latitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
        longitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
    };

    const placeMarker = (latitude, longitude) => {
        if (marker === null) {
            marker = L.marker([latitude, longitude], {
                icon: markerIcon,
                draggable: true,
            }).addTo(map);

            marker.on('dragend', () => {
                const position = marker.getLatLng();
                syncInputs(position.lat, position.lng);
                setStatus(`Titik dipilih: ${position.lat.toFixed(6)}, ${position.lng.toFixed(6)}`);
            });
        } else {
            marker.setLatLng([latitude, longitude]);
        }
    };

    if (hasInitialPoint) {
        placeMarker(initialLatitude, initialLongitude);
    }

    map.on('click', (event) => {
        placeMarker(event.latlng.lat, event.latlng.lng);
        syncInputs(event.latlng.lat, event.latlng.lng);
        setStatus(`Titik dipilih: ${event.latlng.lat.toFixed(6)}, ${event.latlng.lng.toFixed(6)}`);
    });

    const updateFromInputs = () => {
        const latitude = parseCoordinate(latitudeInput.value);
        const longitude = parseCoordinate(longitudeInput.value);

        if (latitude === null || longitude === null) {
            return;
        }

        placeMarker(latitude, longitude);
        map.panTo([latitude, longitude]);
        setStatus(`Koordinat diperbarui dari input: ${latitude.toFixed(6)}, ${longitude.toFixed(6)}`);
    };

    latitudeInput.addEventListener('change', updateFromInputs);
    longitudeInput.addEventListener('change', updateFromInputs);

    clearButton?.addEventListener('click', () => {
        latitudeInput.value = '';
        longitudeInput.value = '';
        latitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
        longitudeInput.dispatchEvent(new Event('input', { bubbles: true }));

        if (marker !== null) {
            map.removeLayer(marker);
            marker = null;
        }

        map.setView(defaultCenter, 5);
        setStatus('Titik lokasi dihapus.');
    });

    window.setTimeout(() => map.invalidateSize(), 150);
}
