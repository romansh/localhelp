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
                @php
                    $categoryColors = [
                        'products' => '#3b82f6',
                        'medicine' => '#ef4444',
                        'transport' => '#8b5cf6',
                        'other' => '#6b7280',
                    ];
                @endphp
                @foreach (\App\Models\HelpRequest::categories() as $key => $label)
                    <label class="inline-flex items-center gap-1.5 text-sm cursor-pointer">
                        <input type="checkbox"
                               value="{{ $key }}"
                               wire:model.live.debounce.150ms="filters"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="inline-block w-3 h-3 rounded-full border border-white shadow-sm" 
                              style="background-color: {{ $categoryColors[$key] ?? '#6b7280' }}"></span>
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
                     x-show="!$wire.showForm" x-transition>
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
            @request-created="$wire.$parent.closeForm(); $wire.$parent.refreshRequests();" />
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

// Current user id for popup action logic
const MY_ID = @js(auth()->id());

            window.takeRequest = function(requestId) {
                if (window.Livewire?.dispatch) {
                    window.Livewire.dispatch('take-request', { requestId: requestId });
                } else {
                    $wire.call('takeRequest', requestId);
                }
            }

            window.doneRequest = function(requestId) {
                if (window.Livewire?.dispatch) {
                    window.Livewire.dispatch('done-request', { requestId: requestId });
                } else {
                    $wire.call('doneRequest', requestId);
                }
            }
            window.handleOpenForm = function(requestId) {
                if (window.LOCALHELP_MARKER_LAYER && window.LOCALHELP_MAP) {
                    window.LOCALHELP_MARKER_LAYER.eachLayer(function(layer) {
                        if (layer._helpRequestId === requestId) {
                            window.LOCALHELP_MAP.panTo(layer.getLatLng(), { animate: true });
                            setTimeout(function() { layer.openPopup(); }, 300);
                        }
                    });
                }
            }
const STATUS_ICONS = {
    open: '🟢',
    in_progress: '🟡',
    fulfilled: '✅',
};

function createMarkerIcon(category) {
    var color = CATEGORY_COLORS[category] || CATEGORY_COLORS.other;
    return L.divIcon({
        className: 'custom-marker',
        html: '<div style="'
            + 'width: 28px; height: 28px; border-radius: 50%;'
            + 'background:' + color + '; border: 3px solid white;'
            + 'box-shadow: 0 2px 6px rgba(0,0,0,0.3);'
            + '"></div>',
        iconSize: [28, 28],
        iconAnchor: [14, 14],
        popupAnchor: [0, -16],
    });
}

function buildPopup(data) {
    var icon = STATUS_ICONS[data.status] || '';
    var escape = function(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };
    var categoryLabel    = escape(data.category_label || data.category);
    var contactTypeLabel = escape(data.contact_type_label || data.contact_type);
    var statusLabel      = escape(data.status_label || data.status);

    var html = '<div style="min-width: 200px; font-family: system-ui, sans-serif; font-size: 13px; line-height: 1.45;">';
    html += '<div style="font-weight: 600; font-size: 14px; margin-bottom: 6px;">'
          + icon + ' ' + escape(data.title) + '</div>';
    if (data.description) {
        html += '<p style="color: #555; margin: 0 0 6px;">'
              + escape(data.description).substring(0, 140) + '</p>';
    }
    html += '<div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:6px;">'
          + '<span style="background:#eef2ff; color:#4f46e5; border-radius:4px; padding:1px 6px;">' + categoryLabel + '</span>'
          + '<span style="background:#f3f4f6; color:#374151; border-radius:4px; padding:1px 6px;">' + statusLabel + '</span>'
          + '</div>';
    html += '<div style="color: #374151; margin-bottom: 2px;">'
          + '<strong>' + contactTypeLabel + ':</strong> ' + escape(data.contact_value)
          + '</div>';
    html += '<div style="color: #9ca3af; font-size: 11px;">'
          + escape(data.user_name) + ' · ' + escape(data.created_at)
          + '</div>';

        // Show helper info if exists
        if (data.helper_name) {
          html += '<div style="color:#374151; margin-top:6px; font-size:13px;">'
              + '<strong>{{ __('ui.helped_by') }}:</strong> ' + escape(data.helper_name)
              + '</div>';
        }

        // Action buttons
            html += '<div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">';
            html += '<button class="inline-flex items-center px-2 py-1 rounded text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-700" onclick="window.handleOpenForm(' + data.id + ')">'
                + '{{ __('ui.view') }}' + '</button>';
            if (!data.helper_id) {
              html += '<button class="inline-flex items-center px-2 py-1 rounded text-sm font-medium bg-gray-200 text-gray-800 hover:bg-gray-300" onclick="window.takeRequest(' + data.id + ')">'
                  + '{{ __('ui.ill_help') }}' + '</button>';
            } else if (data.helper_id == MY_ID) {
              html += '<button class="inline-flex items-center px-2 py-1 rounded text-sm font-medium bg-green-600 text-white hover:bg-green-700" onclick="window.doneRequest(' + data.id + ')">'
                  + '{{ __('ui.mark_done') }}' + '</button>';
            }
            html += '</div>';
    html += '</div>';
    return html;
}

// Register Alpine component for the Leaflet map
Alpine.data('mapComponent', (initialMarkers, mapConfig) => ({
    map: null,
    markerLayer: null,
    drawControl: null,
    drawnLayer: null,
    markersData: initialMarkers || [],
    isRendering: false,  // Flag to prevent concurrent renderMarkers() calls

    init() {
        this.map = L.map('map').setView(
            [mapConfig.lat, mapConfig.lng],
            mapConfig.zoom
        );

        // Tile URL with optional lang param for providers that support it.
        const tileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
            + (mapConfig.lang ? '?lang=' + mapConfig.lang : '');

        L.tileLayer(tileUrl, {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 18,
        }).addTo(this.map);

        this.markerLayer = new L.LayerGroup().addTo(this.map);
        // expose map + markerLayer globally for popup helpers
        window.LOCALHELP_MAP = this.map;
        window.LOCALHELP_MARKER_LAYER = this.markerLayer;
        this.drawnLayer = new L.FeatureGroup().addTo(this.map);

        // Leaflet Draw control (rectangle only, no edit-vertices button)
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
            edit: { featureGroup: this.drawnLayer, edit: false, remove: true },
        });
        this.map.addControl(this.drawControl);

        // Handle rectangle drawn
        this.map.on(L.Draw.Event.CREATED, (e) => {
            // Verify state before processing drawing
            if (!this.drawnLayer || !this.map.hasLayer(this.drawnLayer)) {
                console.warn('[LocalHelp] Draw event: drawnLayer detached, re-adding');
                if (this.drawnLayer) {
                    this.map.addLayer(this.drawnLayer);
                }
            }
            
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

        // Hide the useless 'Save' button when delete mode is activated —
        // the only useful actions are 'Clear All' and 'Cancel'.
        this.map.on('draw:deletestart', () => {
            requestAnimationFrame(() => {
                document.querySelectorAll('.leaflet-draw-actions a').forEach(a => {
                    if (a.getAttribute('title') === L.drawLocal.edit.toolbar.actions.save.title) {
                        a.closest('li').style.display = 'none';
                    }
                });
            });
        });

        // Click to place new request
        let lastClickTime = 0;
        this.map.on('click', (e) => {
            // Throttle clicks to prevent rapid double-click issues
            const now = Date.now();
            if (now - lastClickTime < 300) {
                console.debug('[LocalHelp] Click throttled');
                return;
            }
            lastClickTime = now;
            
            // Verify map state before triggering Livewire action
            if (!this.markerLayer || !this.map.hasLayer(this.markerLayer)) {
                console.warn('[LocalHelp] Map click: invalid state, refreshing');
                this.refreshMapState();
                return;
            }
            
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

        // ─── Handle page visibility changes ─────────────────────────────
        // When tab becomes visible after long inactivity, refresh map state
        // to prevent "stuck" markers and ensure proper zoom/pan behavior.
        let lastVisibilityChange = Date.now();
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                const inactiveMs = Date.now() - lastVisibilityChange;
                console.debug('[LocalHelp] Page visible after ' + Math.round(inactiveMs / 1000) + 's');
                
                // If inactive for more than 30 seconds, invalidate map and refresh
                if (inactiveMs > 30000) {
                    console.debug('[LocalHelp] Long inactivity detected, refreshing map');
                    this.refreshMapState();
                }
            }
            lastVisibilityChange = Date.now();
        });

        // ─── Handle Livewire lifecycle events ───────────────────────────
        // Reconnect can happen after network issues or long inactivity.
        // When Livewire reconnects, the Alpine component may lose sync.
        Livewire.hook('commit', ({ component, respond }) => {
            // After each Livewire commit, ensure map layers are intact
            if (this.markerLayer && this.map && !this.map.hasLayer(this.markerLayer)) {
                console.warn('[LocalHelp] Livewire commit: markerLayer detached, re-adding');
                this.map.addLayer(this.markerLayer);
            }
        });

        // Handle browser sleep/wake (e.g., laptop lid close/open)
        window.addEventListener('focus', () => {
            console.debug('[LocalHelp] Window focus gained, checking map state');
            this.refreshMapState();
        });
    },

    // Refresh map state after inactivity or reconnection
    refreshMapState() {
        if (!this.map) return;
        
        try {
            // Invalidate Leaflet tile cache and force re-render
            this.map.invalidateSize();
            
            // Re-attach layers if they were detached
            if (this.markerLayer && !this.map.hasLayer(this.markerLayer)) {
                console.warn('[LocalHelp] refreshMapState: re-adding markerLayer');
                this.map.addLayer(this.markerLayer);
            }
            if (this.drawnLayer && !this.map.hasLayer(this.drawnLayer)) {
                console.warn('[LocalHelp] refreshMapState: re-adding drawnLayer');
                this.map.addLayer(this.drawnLayer);
            }
            
            // Force re-render of current markers
            $wire.call('refreshRequests');
        } catch (error) {
            console.error('[LocalHelp] Error refreshing map state:', error);
        }
    },

    // Check if map is in valid state for operations
    isMapValid() {
        return this.map 
            && this.markerLayer 
            && this.map.hasLayer(this.markerLayer);
    },

    renderMarkers(markers) {
        // ─── Possible causes of "stuck" markers: ───────────────────────
        // 1. Race condition: Echo + polling trigger simultaneous updates
        // 2. Open popup during clearLayers() prevents marker removal
        // 3. Multiple filter changes (wire:model.live) fire rapid updates
        // 4. Leaflet Draw interaction conflicts with marker rendering
        // 5. markerLayer detached from map during Livewire morphdom pass
        // 6. Long page inactivity: browser throttles JS, Livewire disconnects
        // 7. Browser sleep/wake: laptop lid close, tab backgrounded
        // 8. Map click during invalid state triggers Livewire roundtrip
        //
        // Protections implemented:
        // - isRendering flag prevents concurrent execution
        // - Close all popups before clearing layers
        // - Verify markerLayer is still attached to map
        // - Page visibility API: auto-refresh after 30s inactivity
        // - Livewire commit hook: re-attach layers after reconnect
        // - Window focus handler: refresh on wake from sleep
        // - Click throttling (300ms) and state validation
        // - Detailed console logging for debugging
        
        if (!this.markerLayer) {
            console.warn('[LocalHelp] renderMarkers: markerLayer not initialized');
            return;
        }
        
        // Prevent concurrent rendering (race condition protection)
        if (this.isRendering) {
            console.debug('[LocalHelp] renderMarkers: already rendering, skipping');
            return;
        }
        
        this.isRendering = true;
        
        try {
            // Check if markerLayer is still attached to the map
            if (!this.map.hasLayer(this.markerLayer)) {
                console.warn('[LocalHelp] markerLayer detached from map, re-adding');
                this.map.addLayer(this.markerLayer);
            }
            
            // Close all popups before clearing (prevents stuck markers)
            this.map.closePopup();
            
            // Clear existing markers
            this.markerLayer.clearLayers();
            
            console.debug('[LocalHelp] Rendering ' + (markers || []).length + ' markers');
            
            // Add new markers
            (markers || []).forEach(data => {
                const marker = L.marker([data.lat, data.lng], {
                    icon: createMarkerIcon(data.category),
                    // Prevent marker clicks from bubbling to map.on('click')
                    // so clicking a marker opens its popup instead of the form.
                    bubblingMouseEvents: false,
                }).bindPopup(buildPopup(data), {
                    autoPan: true,
                    keepInView: true,
                    closeOnClick: false,
                    autoClose: false,
                });

                marker._helpRequestId = data.id;
                this.markerLayer.addLayer(marker);
            });
        } catch (error) {
            console.error('[LocalHelp] Error rendering markers:', error);
        } finally {
            this.isRendering = false;
        }
    },

    focusMarker(id) {
        if (!this.markerLayer || !this.map) {
            console.warn('[LocalHelp] focusMarker: map or markerLayer not available');
            return;
        }
        
        // Ensure markerLayer is attached
        if (!this.map.hasLayer(this.markerLayer)) {
            console.warn('[LocalHelp] focusMarker: markerLayer detached, re-adding');
            this.map.addLayer(this.markerLayer);
        }
        
        this.markerLayer.eachLayer(layer => {
            if (layer._helpRequestId === id) {
                this.map.panTo(layer.getLatLng(), { animate: true });
                setTimeout(() => layer.openPopup(), 300); // Delay to allow pan animation
            }
        });
    },
}));
</script>
@endscript
