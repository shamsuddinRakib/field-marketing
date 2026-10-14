@extends('backend.layouts.master')

@section('meta')
    <title>MR Location Map</title>
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">MR Location Map</h6>
            <p class="m-0">View Marketing Representative location history by date</p>
        </div>

        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="{{ route('backend.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">MR Location Map</li>
        </ul>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Location Search</h5>
        </div>

        <div class="card-body">

            <form method="GET" action="{{ route('locations.map') }}">

                <div class="row align-items-end">
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Marketing Representative
                            </label>

                            <select name="user_id" class="form-control" required>
                                <option value="">Select Marketing Representative</option>

                                @foreach ($mrs as $mr)
                                    <option value="{{ $mr->user_id }}"
                                        {{ request('user_id') == $mr->user_id ? 'selected' : '' }}>
                                        {{ $mr->user?->name ?? 'N/A' }}
                                        @if ($mr->employee_id)
                                            - {{ $mr->employee_id }}
                                        @endif
                                    </option>
                                @endforeach

                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold"> Date </label>
                            <input type="date" name="date" class="form-control" value="{{ request('date') }}"
                                required>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="mb-3">
                            <button type="submit"
                                class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
                                <iconify-icon icon="solar:map-point-outline" class="text-xl"></iconify-icon> Show Location
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>


    @if ($location)
        @php
            $latitudes = is_array($location->latitude) ? $location->latitude : [$location->latitude];

            $longitudes = is_array($location->longitude) ? $location->longitude : [$location->longitude];

            $coordinates = [];

            foreach ($latitudes as $index => $latitude) {
                if (isset($longitudes[$index])) {
                    $coordinates[] = [
                        'lat' => (float) $latitude,
                        'lng' => (float) $longitudes[$index],
                    ];
                }
            }
        @endphp

        <div class="card mt-24">

            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="card-title mb-0">
                        Location Route
                    </h5>

                    <p class="mb-0 mt-1 text-secondary">
                        Date: {{ $location->date->format('d M Y') }}
                    </p>
                </div>

                <div>
                    <span class="badge bg-primary">
                        {{ count($coordinates) }} Location Points
                    </span>
                </div>
            </div>

            <div class="card-body">

                @if (count($coordinates) > 0)
                    <div id="mr-location-map" style="height: 600px; width: 100%; border-radius: 8px;">
                    </div>
                @else
                    <div class="alert alert-warning mb-0">
                        No valid location coordinates found for this date.
                    </div>
                @endif

            </div>
        </div>
    @elseif(request()->filled('user_id') && request()->filled('date'))
        <div class="card mt-24">
            <div class="card-body">

                <div class="alert alert-warning mb-0">
                    No location data found for the selected Marketing Representative
                    on {{ \Carbon\Carbon::parse(request('date'))->format('d M Y') }}.
                </div>

            </div>
        </div>
    @endif
@endsection


@section('script')
    {{-- Leaflet CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    {{-- Leaflet JS --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>


    @if ($location && isset($coordinates) && count($coordinates) > 0)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const locations = @json($coordinates);
                if (!locations.length) {
                    return;
                }

                const map = L.map('mr-location-map');

                L.tileLayer(
                    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors'
                    }
                ).addTo(map);


                const routeCoordinates = locations.map(function(location) {
                    return [
                        location.lat,
                        location.lng
                    ];
                });


                // Draw route line

                const polyline = L.polyline(routeCoordinates, {
                    weight: 5
                }).addTo(map);


                //Add markers

                locations.forEach(function(location, index) {

                    const marker = L.marker([
                        location.lat,
                        location.lng
                    ]).addTo(map);


                    let title = 'Location #' + (index + 1);

                    if (index === 0) {
                        title += ' - Start Location';
                    }

                    if (index === locations.length - 1) {
                        title += ' - Last Location';
                    }


                    marker.bindPopup(`
                        <div style="min-width: 180px;">
                            <strong>${title}</strong>
                            <hr style="margin: 6px 0;">
                            <div>
                                <strong>Latitude:</strong>
                                ${location.lat}
                            </div>
                            <div>
                                <strong>Longitude:</strong>
                                ${location.lng}
                            </div>
                        </div>
                    `);

                });


                map.fitBounds(polyline.getBounds(), {
                    padding: [30, 30]
                });
            });
        </script>
    @endif
@endsection
