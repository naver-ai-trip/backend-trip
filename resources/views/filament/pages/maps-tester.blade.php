<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Forms --}}
        <div>
            {{ $this->form }}
        </div>

        {{-- Dynamic Map Display --}}
        @if($mapData)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Map Preview</h3>
                
                <div wire:ignore id="map" class="w-full h-96 rounded-lg border border-gray-300 dark:border-gray-600"></div>
                
                @if(isset($mapData['distance']))
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                            <div class="text-sm text-blue-600 dark:text-blue-400 font-medium">Distance</div>
                            <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                                {{ number_format($mapData['distance'] / 1000, 2) }} km
                            </div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                            <div class="text-sm text-green-600 dark:text-green-400 font-medium">Duration</div>
                            <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                                {{ number_format($mapData['duration'] / 1000 / 60, 0) }} min
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Initialize NAVER Map --}}
            @once
            <script src="https://openapi.map.naver.com/openapi/v3/maps.js?ncpClientId={{ config('services.naver.maps.client_id') }}"></script>
            @endonce
            
            <script>
                // Reinitialize map after Livewire updates
                window.initNaverMap = function() {
                    const mapData = @json($mapData);
                    
                    // Initialize map
                    const mapOptions = {
                        center: new naver.maps.LatLng(mapData.center.lat, mapData.center.lng),
                        zoom: 14,
                        zoomControl: true,
                        zoomControlOptions: {
                            position: naver.maps.Position.TOP_RIGHT
                        }
                    };
                    
                    const map = new naver.maps.Map('map', mapOptions);
                    
                    // Add markers
                    if (mapData.markers) {
                        mapData.markers.forEach(function(marker) {
                            new naver.maps.Marker({
                                position: new naver.maps.LatLng(marker.lat, marker.lng),
                                map: map,
                                title: marker.label
                            });
                        });
                    }
                    
                    // Draw path if available
                    if (mapData.path && mapData.path.length > 0) {
                        const pathCoordinates = mapData.path.map(function(coord) {
                            return new naver.maps.LatLng(coord[1], coord[0]); // Note: NAVER returns [lng, lat]
                        });
                        
                        new naver.maps.Polyline({
                            map: map,
                            path: pathCoordinates,
                            strokeColor: '#5347AA',
                            strokeWeight: 5,
                            strokeOpacity: 0.8
                        });
                        
                        // Fit bounds to show entire route
                        const bounds = new naver.maps.LatLngBounds();
                        pathCoordinates.forEach(function(coord) {
                            bounds.extend(coord);
                        });
                        map.fitBounds(bounds);
                    }
                };
                
                // Initialize on page load and after Livewire updates
                if (typeof naver !== 'undefined') {
                    window.initNaverMap();
                } else {
                    document.addEventListener('DOMContentLoaded', window.initNaverMap);
                }
                
                // Re-initialize after Livewire updates
                document.addEventListener('livewire:navigated', window.initNaverMap);
                Livewire.hook('morph.updated', () => {
                    setTimeout(window.initNaverMap, 100);
                });
            </script>
        @endif

        {{-- JSON Result Display --}}
        @if($result)
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">API Response</h3>
                <pre class="text-xs text-gray-800 dark:text-gray-200 overflow-x-auto whitespace-pre-wrap">{{ $result }}</pre>
            </div>
        @endif
    </div>
</x-filament-panels::page>
