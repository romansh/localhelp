{{--
    wire:poll.30000ms.visible — Livewire polling fallback:
    Fires refreshRequests() every 30 s, but only when the tab is visible.
    When Reverb WebSocket is active the Echo listeners (below in @script)
    deliver updates instantly, so the poll just adds a cheap safety net.
--}}
<div class="h-[calc(100vh-3.5rem)] flex flex-col lg:flex-row"
     wire:poll.30000ms.visible="refreshRequests">
    {{-- Sidebar --}}
    <aside class="w-full lg:w-80 xl:w-96 bg-white border-r border-gray-200 flex flex-col z-[500] order-2 lg:order-1
                  max-h-[40vh] lg:max-h-full overflow-hidden">
        {{-- Filters --}}
        <div class="p-3 border-b border-gray-100 space-y-2">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">{{ __('ui.filters') }}</h3>
                @if ($areaNorth !== null)
                    <button wire:click="clearArea"
                            class="text-xs text-indigo-600 hover:text-indigo-800">
                        {{ __('ui.clear_area') }}
                    </button>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach (\App\Models\HelpRequest::categories() as $key => $label)
                    <label class="inline-flex items-center gap-1.5 text-sm cursor-pointer">
                        <input type="checkbox"
                               value="{{ $key }}"
                               wire:model.live="filters"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="select-none">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Request list --}}
        <div class="flex-1 overflow-y-auto p-3 space-y-2">
            @forelse ($markers as $marker)
                <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm hover:shadow-md transition
                            cursor-pointer group"
                     x-data
                     @click="$dispatch('focus-marker', { id: {{ $marker['id'] }} })">
                    {{-- Header --}}
                    <div class="flex items-start justify-between gap-2">
                        <h4 class="font-medium text-sm text-gray-900 leading-snug">{{ $marker['title'] }}</h4>
                        <span @class([
                            'shrink-0 text-xs px-2 py-0.5 rounded-full font-medium',
                            'bg-green-100 text-green-700' => $marker['status'] === 'open',
                            'bg-yellow-100 text-yellow-700' => $marker['status'] === 'in_progress',
                            'bg-gray-100 text-gray-500' => $marker['status'] === 'fulfilled',
                        ])>
                            {{ \App\Models\HelpRequest::statuses()[$marker['status']] ?? $marker['status'] }}
                        </span>
                    </div>

                    {{-- Category badge --}}
                    <div class="mt-1.5 flex items-center gap-2 text-xs text-gray-500">
                        <span @class([
                            'inline-flex items-center gap-1 px-1.5 py-0.5 rounded',
                            'bg-blue-50 text-blue-700' => $marker['category'] === 'products',
                            'bg-red-50 text-red-700' => $marker['category'] === 'medicine',
                            'bg-purple-50 text-purple-700' => $marker['category'] === 'transport',
                            'bg-gray-50 text-gray-600' => $marker['category'] === 'other',
                        ])>
                            {{ \App\Models\HelpRequest::categories()[$marker['category']] ?? $marker['category'] }}
                        </span>
                        <span>{{ $marker['created_at'] }}</span>
                    </div>

                    {{-- Description preview --}}
                    @if ($marker['description'])
                        <p class="mt-1.5 text-xs text-gray-500 line-clamp-2">{{ $marker['description'] }}</p>
                    @endif

                    {{-- Contact --}}
                    <div class="mt-2 text-xs text-gray-600">
                        <span class="font-medium">{{ \App\Models\HelpRequest::contactTypes()[$marker['contact_type']] ?? $marker['contact_type'] }}:</span>
                        {{ $marker['contact_value'] }}
                    </div>

                    {{-- Owner actions --}}
                    @if ($marker['is_owner'])
                        <div class="mt-2 pt-2 border-t border-gray-100 flex flex-wrap gap-1">
                            @if ($marker['status'] === 'open')
                                <button wire:click="updateStatus({{ $marker['id'] }}, 'in_progress')"
                                        class="text-xs px-2 py-1 rounded bg-yellow-50 text-yellow-700 hover:bg-yellow-100 transition">
                                    {{ __('requests.mark_in_progress') }}
                                </button>
                            @endif
                            @if ($marker['status'] !== 'fulfilled')
                                <button wire:click="updateStatus({{ $marker['id'] }}, 'fulfilled')"
                                        class="text-xs px-2 py-1 rounded bg-green-50 text-green-700 hover:bg-green-100 transition">
                                    {{ __('requests.mark_fulfilled') }}
                                </button>
                            @endif
                            @if ($marker['status'] === 'in_progress')
                                <button wire:click="updateStatus({{ $marker['id'] }}, 'open')"
                                        class="text-xs px-2 py-1 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 transition">
                                    {{ __('requests.reopen') }}
                                </button>
                            @endif
                            <button wire:click="deleteRequest({{ $marker['id'] }})"
                                    wire:confirm="{{ __('ui.confirm_delete') }}"
                                    class="text-xs px-2 py-1 rounded bg-red-50 text-red-700 hover:bg-red-100 transition">
                                {{ __('ui.delete') }}
                            </button>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center text-gray-400 py-8 text-sm">
                    {{ __('ui.no_requests') }}
                </div>
            @endforelse
        </div>
    </aside>

    {{-- Map — wire:ignore prevents Livewire morphdom from destroying the
         Leaflet map container during re-renders. Marker updates are handled
         via $wire.$watch('markers') in the Alpine component below. --}}
    <div class="flex-1 relative order-1 lg:order-2 min-h-[40vh] lg:min-h-0"
         wire:ignore
         x-data="mapComponent(@js($markers), @js([
             'lat' => config('localhelp.map.default_lat'),
             'lng' => config('localhelp.map.default_lng'),
             'zoom' => config('localhelp.map.default_zoom'),
             'lang' => app()->getLocale(),
         ]))"
         @focus-marker.window="focusMarker($event.detail.id)">

        <div id="map" class="absolute inset-0 z-0"></div>

        {{-- Create request button (floating) --}}
        @auth
            <div class="absolute bottom-4 right-4 z-[1000]">
                <div class="bg-white rounded-lg shadow-lg px-3 py-2 text-xs text-gray-500 mb-2 text-center"
                     x-show="!formMode" x-transition>
                    {{ __('ui.click_map') }}
                </div>
            </div>
        @endauth
    </div>

    {{-- Request Form Modal --}}
    @if ($showForm)
        <livewire:request-form
            :lat="$newLat"
            :lng="$newLng"
            @close="closeForm"
            @request-created="closeForm(); refreshRequests();" />
    @endif
</div>

@script
<script>
// ─── Category colors for markers ─────────────────────────
const CATEGORY_COLORS = {
    products: '#3b82f6',
    medicine: '#ef4444',
    transport: '#8b5cf6',
    other: '#6b7280',
};

const STATUS_ICONS = {
    open: '🟢',
    in_progress: '🟡',
    fulfilled: '✅',
};

function createMarkerIcon(category) {
    const color = CATEGORY_COLORS[category] || CATEGORY_COLORS.other;
    return L.divIcon({
        className: 'custom-marker',
        html: `<div style="
            width: 28px; height: 28px; border-radius: 50%;
            background: ${color}; border: 3px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        "></div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14],
        popupAnchor: [0, -16],
    });
}

function buildPopup(data) {
    const icon = STATUS_ICONS[data.status] || '';
    const escape = (str) => {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };
    // Use pre-translated labels passed from the server (respects current locale).
    const categoryLabel    = escape(data.category_label || data.category);
    const contactTypeLabel = escape(data.contact_type_label || data.contact_type);
    const statusLabel      = escape(data.status_label || data.status);
    return `
        <div style="min-width: 200px; font-family: system-ui, sans-serif; font-size: 13px; line-height: 1.45;">
            <div style="font-weight: 600; font-size: 14px; margin-bottom: 6px;">
                ${icon} ${escape(data.title)}
            </div>
            ${data.description ? `<p style="color: #555; margin: 0 0 6px;">${escape(data.description).substring(0, 140)}</p>` : ''}
            <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:6px;">
                <span style="background:#eef2ff; color:#4f46e5; border-radius:4px; padding:1px 6px;">${categoryLabel}</span>
                <span style="background:#f3f4f6; color:#374151; border-radius:4px; padding:1px 6px;">${statusLabel}</span>
            </div>
            <div style="color: #374151; margin-bottom: 2px;">
                <strong>${contactTypeLabel}:</strong> ${escape(data.contact_value)}
            </div>
            <div style="color: #9ca3af; font-size: 11px;">
                ${escape(data.user_name)} · ${escape(data.created_at)}
            </div>
        </div>
    `;
}

// Register Alpine component for the Leaflet map
Alpine.data('mapComponent', (initialMarkers, mapConfig) => ({
    map: null,
    markerLayer: null,
    drawControl: null,
    drawnLayer: null,
    markersData: initialMarkers || [],

    init() {
        this.map = L.map('map').setView(
            [mapConfig.lat, mapConfig.lng],
            mapConfig.zoom
        );

            // tile URL may include ?lang= parameter; many providers ignore it but
            // some (e.g. custom OSM forks) will respect it and render labels in
            // the appropriate language. Including it causes no harm when unused.
            const tileUrl = `https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png${mapConfig.lang ? '?lang='+mapConfig.lang : ''}`;

            L.tileLayer(tileUrl, {
        this.drawnLayer = new L.FeatureGroup().addTo(this.map);

        // Leaflet Draw control (rectangle only)
        this.drawControl = new L.Control.Draw({
            draw: {
                polyline: false,
                polygon: false,
                circle: false,
                circlemarker: false,
                marker: false,
                rectangle: {
                    shapeOptions: { color: '#6366f1', weight: 2, fillOpacity: 0.1 },
                },
            },
            edit: { featureGroup: this.drawnLayer, remove: true },
        });
        this.map.addControl(this.drawControl);

        // Handle rectangle drawn
        this.map.on(L.Draw.Event.CREATED, (e) => {
            this.drawnLayer.clearLayers();
            this.drawnLayer.addLayer(e.layer);
            const bounds = e.layer.getBounds();
            $wire.call('updateArea',
                bounds.getNorth(), bounds.getSouth(),
                bounds.getEast(), bounds.getWest()
            );
        });

        // Handle drawn items deleted
        this.map.on(L.Draw.Event.DELETED, () => {
            $wire.call('clearArea');
        });

        // Click to place new request
        this.map.on('click', (e) => {
            $wire.call('openForm', e.latlng.lat, e.latlng.lng);
        });

        // Initial markers
        this.renderMarkers(this.markersData);

        // Watch for Livewire property changes — this is the primary
        // mechanism to re-render markers when filters change, new
        // requests arrive via poll or Echo, etc.
        $wire.$watch('markers', (newMarkers) => {
            this.renderMarkers(newMarkers);
        });

        // Subscribe to Reverb channel for instant updates.
        // If Reverb is unavailable, wire:poll.30000ms.visible (on the outer div)
        // acts as the fallback and refreshes the list every 30 s.
        if (window.Echo) {
            window.Echo.channel('help-requests')
                .listen('HelpRequestCreated', () => {
                    console.debug('[LocalHelp] WS: new request received — refreshing.');
                    $wire.call('refreshRequests');
                })
                .listen('HelpRequestUpdated', () => {
                    console.debug('[LocalHelp] WS: request updated — refreshing.');
                    $wire.call('refreshRequests');
                });
        }
    },

    renderMarkers(markers) {
        if (!this.markerLayer) return;
        this.markerLayer.clearLayers();
        (markers || []).forEach(data => {
            const marker = L.marker([data.lat, data.lng], {
                icon: createMarkerIcon(data.category),
                // Prevent marker clicks from bubbling to map.on('click')
                // so clicking a marker opens its popup instead of the form.
                bubblingMouseEvents: false,
            }).bindPopup(buildPopup(data));

            marker._helpRequestId = data.id;
            this.markerLayer.addLayer(marker);
        });
    },

    focusMarker(id) {
        if (!this.markerLayer) return;
        this.markerLayer.eachLayer(layer => {
            if (layer._helpRequestId === id) {
                this.map.panTo(layer.getLatLng(), { animate: true });
                layer.openPopup();
            }
        });
    },
}));
</script>
@endscript
