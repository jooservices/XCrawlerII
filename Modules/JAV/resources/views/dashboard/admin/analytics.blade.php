@extends('jav::layouts.dashboard')

@section('content')
<div class="container-fluid">
    <style>
        .analytics-chart-wrap {
            height: 280px;
            position: relative;
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Analytics</h2>
        <form method="GET" action="{{ route('jav.blade.admin.analytics') }}" class="d-flex align-items-center gap-2">
            <label for="days" class="small text-muted mb-0">Window</label>
            <select id="days" name="days" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach([7, 14, 30, 90] as $option)
                    <option value="{{ $option }}" {{ $days === $option ? 'selected' : '' }}>
                        Last {{ $option }} days
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Movies (JAV)</p>
                    <h3 class="mb-1">{{ number_format($totals['jav']) }}</h3>
                    <small class="text-muted">Created today: {{ number_format($todayCreated['jav']) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Actors</p>
                    <h3 class="mb-1">{{ number_format($totals['actors']) }}</h3>
                    <small class="text-muted">Created today: {{ number_format($todayCreated['actors']) }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Tags</p>
                    <h3 class="mb-1">{{ number_format($totals['tags']) }}</h3>
                    <small class="text-muted">Created today: {{ number_format($todayCreated['tags']) }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Daily Created (Movies / Actors / Tags)</h5>
                    <div class="analytics-chart-wrap">
                        <canvas id="createdChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">By Provider (Movies)</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                            <tr>
                                <th>Provider</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Today</th>
                                <th class="text-end">Last {{ $days }}d</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($providerStats as $provider)
                                @php
                                    $providerSource = data_get($provider, 'source', 'unknown');
                                    $providerTotal = (int) data_get($provider, 'total_count', 0);
                                    $providerToday = (int) data_get($provider, 'today_count', 0);
                                    $providerWindow = (int) data_get($provider, 'window_count', 0);
                                @endphp
                                <tr>
                                    <td><code>{{ $providerSource }}</code></td>
                                    <td class="text-end">{{ number_format($providerTotal) }}</td>
                                    <td class="text-end">{{ number_format($providerToday) }}</td>
                                    <td class="text-end">{{ number_format($providerWindow) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No provider data.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Daily Engagement (Likes / Watchlist / Ratings / History)</h5>
                    <div class="analytics-chart-wrap">
                        <canvas id="engagementChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Data Quality</h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-1">Movies missing actors: <strong>{{ number_format($quality['missing_actors']) }}</strong></li>
                        <li class="mb-1">Movies missing tags: <strong>{{ number_format($quality['missing_tags']) }}</strong></li>
                        <li class="mb-1">Movies missing image: <strong>{{ number_format($quality['missing_image']) }}</strong></li>
                        <li class="mb-1">Movies missing date: <strong>{{ number_format($quality['missing_date']) }}</strong></li>
                        <li class="mb-1">Orphan actors: <strong>{{ number_format($quality['orphan_actors']) }}</strong></li>
                        <li class="mb-1">Orphan tags: <strong>{{ number_format($quality['orphan_tags']) }}</strong></li>
                        <li class="mb-1">Avg actors/movie: <strong>{{ number_format($quality['avg_actors_per_jav'], 2) }}</strong></li>
                        <li>Avg tags/movie: <strong>{{ number_format($quality['avg_tags_per_jav'], 2) }}</strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Daily Movies Created by Provider</h5>
                    <div class="analytics-chart-wrap">
                        <canvas id="providerCreatedChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Sync Health</h5>
                    <p class="mb-2"><strong>Pending Jobs:</strong> {{ number_format($syncHealth['pending_jobs']) }}</p>
                    <p class="mb-0"><strong>Failed Jobs (24h):</strong> {{ number_format($syncHealth['failed_jobs_24h']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Top Viewed Movies</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                            <tr>
                                <th>Movie</th>
                                <th class="text-end">Views</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($topViewed as $item)
                                @php
                                    $itemUuid = data_get($item, 'uuid');
                                    $itemCode = data_get($item, 'code', '');
                                    $itemViews = (int) data_get($item, 'views', 0);
                                @endphp
                                <tr>
                                    <td>
                                        @if($itemUuid)
                                            <a href="{{ route('jav.blade.movies.show', $itemUuid) }}" class="text-decoration-none">
                                                {{ $itemCode }}
                                            </a>
                                        @else
                                            {{ $itemCode }}
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($itemViews) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-muted">No data.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Top Downloaded Movies</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                            <tr>
                                <th>Movie</th>
                                <th class="text-end">Downloads</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($topDownloaded as $item)
                                @php
                                    $itemUuid = data_get($item, 'uuid');
                                    $itemCode = data_get($item, 'code', '');
                                    $itemDownloads = (int) data_get($item, 'downloads', 0);
                                @endphp
                                <tr>
                                    <td>
                                        @if($itemUuid)
                                            <a href="{{ route('jav.blade.movies.show', $itemUuid) }}" class="text-decoration-none">
                                                {{ $itemCode }}
                                            </a>
                                        @else
                                            {{ $itemCode }}
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($itemDownloads) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-muted">No data.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Top Rated Movies</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                            <tr>
                                <th>Movie</th>
                                <th class="text-end">Avg</th>
                                <th class="text-end">Count</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($topRated as $item)
                                @php
                                    $itemUuid = data_get($item, 'uuid');
                                    $itemCode = data_get($item, 'code', '');
                                    $itemAvg = (float) data_get($item, 'ratings_avg_rating', 0);
                                    $itemCount = (int) data_get($item, 'ratings_count', 0);
                                @endphp
                                <tr>
                                    <td>
                                        @if($itemUuid)
                                            <a href="{{ route('jav.blade.movies.show', $itemUuid) }}" class="text-decoration-none">
                                                {{ $itemCode }}
                                            </a>
                                        @else
                                            {{ $itemCode }}
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($itemAvg, 2) }}</td>
                                    <td class="text-end">{{ number_format($itemCount) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">No data.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const createdLabels = @json($dailyCreated['jav']['labels']);
        const createdJav = @json($dailyCreated['jav']['values']);
        const createdActors = @json($dailyCreated['actors']['values']);
        const createdTags = @json($dailyCreated['tags']['values']);
        const providerSeries = @json($providerDailyCreated['series']);

        const engagementFavorites = @json($dailyEngagement['favorites']['values']);
        const engagementWatchlists = @json($dailyEngagement['watchlists']['values']);
        const engagementRatings = @json($dailyEngagement['ratings']['values']);
        const engagementHistory = @json($dailyEngagement['history']['values']);

        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        };

        const createdCtx = document.getElementById('createdChart');
        if (createdCtx) {
            new Chart(createdCtx, {
                type: 'line',
                data: {
                    labels: createdLabels,
                    datasets: [
                        { label: 'Movies', data: createdJav, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.15)', tension: 0.2 },
                        { label: 'Actors', data: createdActors, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.15)', tension: 0.2 },
                        { label: 'Tags', data: createdTags, borderColor: '#6f42c1', backgroundColor: 'rgba(111,66,193,0.15)', tension: 0.2 }
                    ]
                },
                options: commonOptions
            });
        }

        const engagementCtx = document.getElementById('engagementChart');
        if (engagementCtx) {
            new Chart(engagementCtx, {
                type: 'bar',
                data: {
                    labels: createdLabels,
                    datasets: [
                        { label: 'Likes', data: engagementFavorites, backgroundColor: 'rgba(220,53,69,0.6)' },
                        { label: 'Watchlist', data: engagementWatchlists, backgroundColor: 'rgba(255,193,7,0.6)' },
                        { label: 'Ratings', data: engagementRatings, backgroundColor: 'rgba(13,202,240,0.6)' },
                        { label: 'History Events', data: engagementHistory, backgroundColor: 'rgba(32,201,151,0.6)' }
                    ]
                },
                options: commonOptions
            });
        }

        const providerCtx = document.getElementById('providerCreatedChart');
        if (providerCtx) {
            const providerPalette = ['#0d6efd', '#198754', '#dc3545', '#6f42c1', '#fd7e14', '#20c997'];
            const providerDatasets = Object.entries(providerSeries).map(([source, values], idx) => ({
                label: source,
                data: values,
                borderColor: providerPalette[idx % providerPalette.length],
                backgroundColor: providerPalette[idx % providerPalette.length] + '55',
                tension: 0.2
            }));

            new Chart(providerCtx, {
                type: 'line',
                data: {
                    labels: createdLabels,
                    datasets: providerDatasets
                },
                options: commonOptions
            });
        }
    });
</script>
@endpush
