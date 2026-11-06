import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const container = document.getElementById('driver-trip-map');

if (container) {
    const latitudeInput = document.getElementById('trip-latitude');
    const longitudeInput = document.getElementById('trip-longitude');
    const gpsButton = document.querySelector('[data-trip-use-gps]');
    const clearButton = document.querySelector('[data-trip-clear]');
    const mapStatus = document.querySelector('[data-trip-map-status]');

    const parseCoordinate = (value) => {
        const number = Number.parseFloat(String(value ?? '').replace(',', '.'));

        return Number.isFinite(number) ? number : null;
    };

    const initialLatitude = parseCoordinate(container.dataset.initialLat);
    const initialLongitude = parseCoordinate(container.dataset.initialLng);
    const lastLatitude = parseCoordinate(container.dataset.lastLat);
    const lastLongitude = parseCoordinate(container.dataset.lastLng);
    const destinationLatitude = parseCoordinate(container.dataset.destinationLat);
    const destinationLongitude = parseCoordinate(container.dataset.destinationLng);

    const hasInitialPoint = initialLatitude !== null && initialLongitude !== null;
    const hasLastPoint = lastLatitude !== null && lastLongitude !== null;
    const hasDestination = destinationLatitude !== null && destinationLongitude !== null;
    const defaultCenter = [-2.5, 118.0];

    const center = hasInitialPoint
        ? [initialLatitude, initialLongitude]
        : hasLastPoint
            ? [lastLatitude, lastLongitude]
            : hasDestination
                ? [destinationLatitude, destinationLongitude]
                : defaultCenter;
    const zoom = hasInitialPoint || hasLastPoint ? 13 : hasDestination ? 11 : 5;

    const map = L.map(container, {
        scrollWheelZoom: false,
    }).setView(center, zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    const destinationIcon = L.divIcon({
        className: '',
        html: '<div style="width:30px;height:30px;border-radius:50% 50% 50% 0;background:#e11d48;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 3px 10px rgba(15,23,42,.35);"></div>',
        iconSize: [30, 30],
        iconAnchor: [15, 30],
    });

    const selectedIcon = L.divIcon({
        className: '',
        html: '<div style="width:28px;height:28px;border-radius:50% 50% 50% 0;background:#0d9488;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 3px 10px rgba(15,23,42,.35);"></div>',
        iconSize: [28, 28],
        iconAnchor: [14, 28],
    });

    const lastIcon = L.divIcon({
        className: '',
        html: '<div style="width:14px;height:14px;border-radius:50%;background:#64748b;border:3px solid #fff;box-shadow:0 2px 6px rgba(15,23,42,.3);"></div>',
        iconSize: [14, 14],
        iconAnchor: [7, 7],
    });

    if (hasDestination) {
        L.marker([destinationLatitude, destinationLongitude], {
            icon: destinationIcon,
            interactive: true,
        })
            .addTo(map)
            .bindTooltip(`Tujuan: ${container.dataset.destinationLabel ?? 'Pelanggan'}`, {
                direction: 'top',
                offset: [0, -24],
            });
    }

    if (hasLastPoint && !hasInitialPoint) {
        L.marker([lastLatitude, lastLongitude], {
            icon: lastIcon,
            interactive: true,
        })
            .addTo(map)
            .bindTooltip('Posisi update terakhir', {
                direction: 'top',
                offset: [0, -8],
            });
    }

    let marker = null;

    const setStatus = (message) => {
        if (mapStatus) {
            mapStatus.textContent = message;
        }
    };

    const dispatchSelection = (latitude, longitude, source) => {
        window.dispatchEvent(new CustomEvent('trip-location-selected', {
            detail: {
                latitude: latitude === null ? '' : latitude.toFixed(7),
                longitude: longitude === null ? '' : longitude.toFixed(7),
                source,
            },
        }));
    };

    const placeMarker = (latitude, longitude) => {
        if (marker === null) {
            marker = L.marker([latitude, longitude], {
                icon: selectedIcon,
                draggable: true,
                zIndexOffset: 500,
            }).addTo(map);

            marker.on('dragend', () => {
                const position = marker.getLatLng();
                dispatchSelection(position.lat, position.lng, 'Peta');
                setStatus(`Titik dipilih: ${position.lat.toFixed(6)}, ${position.lng.toFixed(6)}`);
            });
        } else {
            marker.setLatLng([latitude, longitude]);
        }
    };

    const selectPoint = (latitude, longitude, source) => {
        placeMarker(latitude, longitude);
        dispatchSelection(latitude, longitude, source);
        setStatus(`Titik dipilih: ${latitude.toFixed(6)}, ${longitude.toFixed(6)}`);
    };

    if (hasInitialPoint) {
        placeMarker(initialLatitude, initialLongitude);
        setStatus(`Titik dipilih: ${initialLatitude.toFixed(6)}, ${initialLongitude.toFixed(6)}`);
    }

    map.on('click', (event) => {
        selectPoint(event.latlng.lat, event.latlng.lng, 'Peta');
    });

    gpsButton?.addEventListener('click', () => {
        if (!navigator.geolocation) {
            setStatus('Perangkat ini tidak mendukung lokasi GPS.');
            return;
        }

        setStatus('Mengambil lokasi GPS...');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const { latitude, longitude } = position.coords;
                map.setView([latitude, longitude], 16);
                selectPoint(latitude, longitude, 'GPS');
            },
            () => {
                setStatus('Lokasi GPS tidak tersedia. Silakan pilih titik langsung di peta.');
            },
            {
                enableHighAccuracy: true,
                timeout: 8000,
                maximumAge: 30000,
            },
        );
    });

    clearButton?.addEventListener('click', () => {
        if (marker !== null) {
            map.removeLayer(marker);
            marker = null;
        }

        dispatchSelection(null, null, '');
        setStatus('Titik lokasi dihapus. Status akan dikirim tanpa koordinat.');
    });

    window.setTimeout(() => map.invalidateSize(), 150);
}
