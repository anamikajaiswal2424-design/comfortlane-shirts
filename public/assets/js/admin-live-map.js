document.addEventListener('DOMContentLoaded', () => {
    const mapElement = document.getElementById('visitor-world-map');
    if (!mapElement) return;

    const endpoint = mapElement.dataset.liveEndpoint || 'live-visitors.php';
    const liveCount = document.querySelector('[data-live-count]');
    const mappedCount = document.querySelector('[data-mapped-count]');
    const visitorList = document.querySelector('[data-live-list]');
    const updatedLabel = document.querySelector('[data-live-updated]');
    const initialElement = document.getElementById('live-map-initial');
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    }[character]));
    const relativeTime = (seconds) => {
        const value = Math.max(0, Number(seconds || 0));
        if (value < 60) return `${Math.round(value)}s`;
        return `${Math.max(1, Math.floor(value / 60))}m`;
    };
    const locationLabel = (visitor) => [visitor.city, visitor.state || visitor.country].filter(Boolean).join(', ') || 'Approximate location';

    let initialData = null;
    try { initialData = JSON.parse(initialElement?.textContent || 'null'); } catch (error) { initialData = null; }

    let map = null;
    let markerLayer = null;
    let fittedVisitors = false;
    if (window.L) {
        map = window.L.map(mapElement, {
            worldCopyJump: true,
            minZoom: 1,
            zoomControl: true,
            attributionControl: true
        }).setView([20, 8], 2);
        window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        markerLayer = window.L.layerGroup().addTo(map);
    } else {
        mapElement.classList.add('map-unavailable');
        mapElement.textContent = 'The live map could not load. Visitor city and state will still update in the list.';
    }

    const renderVisitors = (data) => {
        const visitors = Array.isArray(data?.visitors) ? data.visitors : [];
        if (liveCount) liveCount.textContent = String(Number(data?.active_visitors || 0));
        if (mappedCount) mappedCount.textContent = String(Number(data?.mapped_visitors || visitors.length));
        if (updatedLabel) updatedLabel.textContent = 'just now';

        if (visitorList) {
            visitorList.innerHTML = visitors.length ? visitors.map((visitor) => `
                <article>
                    <i></i>
                    <div><strong>${escapeHtml(locationLabel(visitor))}</strong><small>${escapeHtml(visitor.path || 'Store page')} · ${escapeHtml(visitor.device || 'device')}</small></div>
                    <time>${escapeHtml(relativeTime(visitor.seconds_ago))}</time>
                </article>
            `).join('') : '<p class="live-map-empty">Waiting for a consented live visitor location.</p>';
        }

        if (!map || !markerLayer) return;
        markerLayer.clearLayers();
        const coordinates = [];
        visitors.forEach((visitor) => {
            const latitude = Number(visitor.latitude);
            const longitude = Number(visitor.longitude);
            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;
            coordinates.push([latitude, longitude]);
            const markerIcon = window.L.divIcon({
                className: 'live-map-marker',
                html: '<span class="live-map-pulse"></span>',
                iconSize: [18, 18],
                iconAnchor: [9, 9]
            });
            const popup = `<strong>${escapeHtml(locationLabel(visitor))}</strong><br>${escapeHtml(visitor.country || '')}<br>${escapeHtml(visitor.path || 'Store page')} · ${escapeHtml(relativeTime(visitor.seconds_ago))} ago`;
            window.L.marker([latitude, longitude], { icon: markerIcon }).bindPopup(popup).addTo(markerLayer);
        });
        if (!fittedVisitors && coordinates.length === 1) {
            map.setView(coordinates[0], 4);
            fittedVisitors = true;
        } else if (!fittedVisitors && coordinates.length > 1) {
            map.fitBounds(coordinates, { padding: [35, 35], maxZoom: 4 });
            fittedVisitors = true;
        }
    };

    const refreshVisitors = async () => {
        if (document.hidden) return;
        try {
            const response = await fetch(endpoint, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store'
            });
            if (response.redirected || !String(response.headers.get('content-type') || '').includes('application/json')) {
                window.location.href = 'login.php';
                return;
            }
            if (!response.ok) throw new Error('Live visitor feed unavailable');
            renderVisitors(await response.json());
        } catch (error) {
            if (updatedLabel) updatedLabel.textContent = 'retrying…';
        }
    };

    if (initialData) renderVisitors(initialData);
    window.setInterval(refreshVisitors, 10000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshVisitors(); });
});
