const Plugin = window.PluginBaseClass;

/**
 * MiniwaldExplorer – Miniforest Detail Map Plugin
 *
 * Renders a simple, read-only MapLibre GL map on the miniforest detail page.
 * Shows a single custom PNG marker at the miniforest's lat/lng position.
 */
export default class MiniwaldDetailMapPlugin extends Plugin {
    static options = {
        tileStyleUrl: '',
        lat: 50.88,
        lng: 6.38,
        zoom: 15,
        markerUrl: '',
    };

    init() {
        this.map = null;

        this._parseOptions();

        if (!this.options.lat || !this.options.lng) {
            console.warn(
                '[MiniwaldDetailMap] No lat/lng provided – map not initialized.'
            );
            return;
        }

        this._initMap();
    }

    _parseOptions() {
        const el = this.el;
        const lat = parseFloat(el.dataset.lat);
        const lng = parseFloat(el.dataset.lng);

        if (!isNaN(lat)) this.options.lat = lat;
        if (!isNaN(lng)) this.options.lng = lng;

        this.options.tileStyleUrl =
            el.dataset.tileStyleUrl || this.options.tileStyleUrl;
        this.options.markerUrl = el.dataset.markerUrl || this.options.markerUrl;
    }

    _initMap() {
        if (typeof maplibregl === 'undefined') {
            console.error('[MiniwaldDetailMap] MapLibre GL not loaded.');
            return;
        }

        try {
            this.map = new maplibregl.Map({
                container: this.el,
                style: this.options.tileStyleUrl,
                center: [this.options.lng, this.options.lat],
                zoom: this.options.zoom,
                attributionControl: false,
                interactive: true,
            });
        } catch (e) {
            console.error('[MiniwaldDetailMap] Map creation failed:', e);
            return;
        }

        this.map.addControl(
            new maplibregl.AttributionControl({ compact: true }),
            'bottom-right'
        );
        this.map.addControl(new maplibregl.NavigationControl(), 'top-right');

        this.map.on('load', () => {
            this._collapseAttribution();
            this._addMarker();
        });
    }

    _addMarker() {
        const { lat, lng, markerUrl } = this.options;

        if (markerUrl) {
            // Use a DOM-based Marker with a custom <img> element.
            // This avoids MapLibre's fetch()-based loadImage() which can fail
            // due to CORS/CSP restrictions on static assets, while a plain
            // browser <img> load succeeds without those constraints.
            const el = document.createElement('img');
            el.src = markerUrl;
            el.className = 'miniforest-detail-map__marker-img';
            el.alt = '';

            new maplibregl.Marker({ element: el, anchor: 'bottom' })
                .setLngLat([lng, lat])
                .addTo(this.map);
        } else {
            this._addDefaultMarker(lat, lng);
        }
    }

    _addDefaultMarker(lat, lng) {
        new maplibregl.Marker({ color: '#4CAF50' })
            .setLngLat([lng, lat])
            .addTo(this.map);
    }

    _collapseAttribution() {
        let counter = 0;
        const interval = setInterval(() => {
            const attrib = this.map
                .getContainer()
                .querySelector('.maplibregl-ctrl-attrib');
            if (attrib) {
                attrib.removeAttribute('open');
                attrib.classList.remove('maplibregl-compact-show');
                clearInterval(interval);
            }
            if (++counter > 100) clearInterval(interval);
        }, 100);
    }
}
