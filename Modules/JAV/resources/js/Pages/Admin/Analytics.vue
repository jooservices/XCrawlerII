<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted } from 'vue';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';

const props = defineProps({
    days: Number,
    totals: Object,
    todayCreated: Object,
    dailyCreated: Object,
    providerDailyCreated: Object,
    dailyEngagement: Object,
    providerStats: Array,
    topViewed: Array,
    topDownloaded: Array,
    topRated: Array,
    quality: Object,
    syncHealth: Object,
});

let charts = [];

const createdLabels = computed(() => props.dailyCreated?.jav?.labels || []);

const ensureChartJs = async () => {
    if (window.Chart) {
        return window.Chart;
    }

    await new Promise((resolve, reject) => {
        const existing = document.querySelector('script[data-chartjs="true"]');
        if (existing) {
            existing.addEventListener('load', () => resolve());
            existing.addEventListener('error', () => reject(new Error('Failed to load Chart.js')));
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
        script.async = true;
        script.dataset.chartjs = 'true';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load Chart.js'));
        document.head.appendChild(script);
    });

    return window.Chart;
};

const destroyCharts = () => {
    charts.forEach((chart) => chart.destroy());
    charts = [];
};

const renderCharts = async () => {
    const Chart = await ensureChartJs();
    destroyCharts();

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0 },
            },
        },
    };

    const createdCtx = document.getElementById('createdChart');
    if (createdCtx) {
        charts.push(new Chart(createdCtx, {
            type: 'line',
            data: {
                labels: createdLabels.value,
                datasets: [
                    {
                        label: 'Movies',
                        data: props.dailyCreated?.jav?.values || [],
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13,110,253,0.15)',
                        tension: 0.2,
                    },
                    {
                        label: 'Actors',
                        data: props.dailyCreated?.actors?.values || [],
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25,135,84,0.15)',
                        tension: 0.2,
                    },
                    {
                        label: 'Tags',
                        data: props.dailyCreated?.tags?.values || [],
                        borderColor: '#6f42c1',
                        backgroundColor: 'rgba(111,66,193,0.15)',
                        tension: 0.2,
                    },
                ],
            },
            options: commonOptions,
        }));
    }

    const engagementCtx = document.getElementById('engagementChart');
    if (engagementCtx) {
        charts.push(new Chart(engagementCtx, {
            type: 'bar',
            data: {
                labels: createdLabels.value,
                datasets: [
                    { label: 'Likes', data: props.dailyEngagement?.favorites?.values || [], backgroundColor: 'rgba(220,53,69,0.6)' },
                    { label: 'Watchlist', data: props.dailyEngagement?.watchlists?.values || [], backgroundColor: 'rgba(255,193,7,0.6)' },
                    { label: 'Ratings', data: props.dailyEngagement?.ratings?.values || [], backgroundColor: 'rgba(13,202,240,0.6)' },
                    { label: 'History Events', data: props.dailyEngagement?.history?.values || [], backgroundColor: 'rgba(32,201,151,0.6)' },
                ],
            },
            options: commonOptions,
        }));
    }

    const providerCtx = document.getElementById('providerCreatedChart');
    if (providerCtx) {
        const providerSeries = props.providerDailyCreated?.series || {};
        const providerPalette = ['#0d6efd', '#198754', '#dc3545', '#6f42c1', '#fd7e14', '#20c997'];
        const providerDatasets = Object.entries(providerSeries).map(([source, values], idx) => ({
            label: source,
            data: values,
            borderColor: providerPalette[idx % providerPalette.length],
            backgroundColor: `${providerPalette[idx % providerPalette.length]}55`,
            tension: 0.2,
        }));

        charts.push(new Chart(providerCtx, {
            type: 'line',
            data: {
                labels: createdLabels.value,
                datasets: providerDatasets,
            },
            options: commonOptions,
        }));
    }
};

onMounted(() => {
    renderCharts();
});

onBeforeUnmount(() => {
    destroyCharts();
});
</script>

<template>
    <Head title="Analytics" />

    <DashboardLayout>
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">Analytics</h2>
                <form method="GET" :action="route('jav.vue.admin.analytics')" class="d-flex align-items-center gap-2">
                    <label for="days" class="small text-muted mb-0">Window</label>
                    <select id="days" name="days" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option v-for="option in [7, 14, 30, 90]" :key="option" :value="option" :selected="days === option">
                            Last {{ option }} days
                        </option>
                    </select>
                </form>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <p class="text-muted mb-1">Total Movies (JAV)</p>
                            <h3 class="mb-1">{{ Number(totals?.jav || 0).toLocaleString() }}</h3>
                            <small class="text-muted">Created today: {{ Number(todayCreated?.jav || 0).toLocaleString() }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <p class="text-muted mb-1">Total Actors</p>
                            <h3 class="mb-1">{{ Number(totals?.actors || 0).toLocaleString() }}</h3>
                            <small class="text-muted">Created today: {{ Number(todayCreated?.actors || 0).toLocaleString() }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <p class="text-muted mb-1">Total Tags</p>
                            <h3 class="mb-1">{{ Number(totals?.tags || 0).toLocaleString() }}</h3>
                            <small class="text-muted">Created today: {{ Number(todayCreated?.tags || 0).toLocaleString() }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Daily Created (Movies / Actors / Tags)</h5>
                            <div style="height: 280px; position: relative;"><canvas id="createdChart"></canvas></div>
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
                                            <th class="text-end">Last {{ days }}d</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="(providerStats || []).length === 0"><td colspan="4" class="text-muted">No provider data.</td></tr>
                                        <tr v-for="provider in providerStats || []" :key="provider.source">
                                            <td><code>{{ provider.source || 'unknown' }}</code></td>
                                            <td class="text-end">{{ Number(provider.total_count || 0).toLocaleString() }}</td>
                                            <td class="text-end">{{ Number(provider.today_count || 0).toLocaleString() }}</td>
                                            <td class="text-end">{{ Number(provider.window_count || 0).toLocaleString() }}</td>
                                        </tr>
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
                            <div style="height: 280px; position: relative;"><canvas id="engagementChart"></canvas></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Data Quality</h5>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-1">Movies missing actors: <strong>{{ Number(quality?.missing_actors || 0).toLocaleString() }}</strong></li>
                                <li class="mb-1">Movies missing tags: <strong>{{ Number(quality?.missing_tags || 0).toLocaleString() }}</strong></li>
                                <li class="mb-1">Movies missing image: <strong>{{ Number(quality?.missing_image || 0).toLocaleString() }}</strong></li>
                                <li class="mb-1">Movies missing date: <strong>{{ Number(quality?.missing_date || 0).toLocaleString() }}</strong></li>
                                <li class="mb-1">Orphan actors: <strong>{{ Number(quality?.orphan_actors || 0).toLocaleString() }}</strong></li>
                                <li class="mb-1">Orphan tags: <strong>{{ Number(quality?.orphan_tags || 0).toLocaleString() }}</strong></li>
                                <li class="mb-1">Avg actors/movie: <strong>{{ Number(quality?.avg_actors_per_jav || 0).toFixed(2) }}</strong></li>
                                <li>Avg tags/movie: <strong>{{ Number(quality?.avg_tags_per_jav || 0).toFixed(2) }}</strong></li>
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
                            <div style="height: 280px; position: relative;"><canvas id="providerCreatedChart"></canvas></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Sync Health</h5>
                            <p class="mb-2"><strong>Pending Jobs:</strong> {{ Number(syncHealth?.pending_jobs || 0).toLocaleString() }}</p>
                            <p class="mb-0"><strong>Failed Jobs (24h):</strong> {{ Number(syncHealth?.failed_jobs_24h || 0).toLocaleString() }}</p>
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
                                    <thead><tr><th>Movie</th><th class="text-end">Views</th></tr></thead>
                                    <tbody>
                                        <tr v-if="(topViewed || []).length === 0"><td colspan="2" class="text-muted">No data.</td></tr>
                                        <tr v-for="item in topViewed || []" :key="`view-${item.uuid || item.code}`">
                                            <td>
                                                <Link v-if="item.uuid" :href="route('jav.vue.movies.show', item.uuid)" class="text-decoration-none">{{ item.code }}</Link>
                                                <template v-else>{{ item.code }}</template>
                                            </td>
                                            <td class="text-end">{{ Number(item.views || 0).toLocaleString() }}</td>
                                        </tr>
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
                                    <thead><tr><th>Movie</th><th class="text-end">Downloads</th></tr></thead>
                                    <tbody>
                                        <tr v-if="(topDownloaded || []).length === 0"><td colspan="2" class="text-muted">No data.</td></tr>
                                        <tr v-for="item in topDownloaded || []" :key="`download-${item.uuid || item.code}`">
                                            <td>
                                                <Link v-if="item.uuid" :href="route('jav.vue.movies.show', item.uuid)" class="text-decoration-none">{{ item.code }}</Link>
                                                <template v-else>{{ item.code }}</template>
                                            </td>
                                            <td class="text-end">{{ Number(item.downloads || 0).toLocaleString() }}</td>
                                        </tr>
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
                                    <thead><tr><th>Movie</th><th class="text-end">Avg</th><th class="text-end">Count</th></tr></thead>
                                    <tbody>
                                        <tr v-if="(topRated || []).length === 0"><td colspan="3" class="text-muted">No data.</td></tr>
                                        <tr v-for="item in topRated || []" :key="`rated-${item.uuid || item.code}`">
                                            <td>
                                                <Link v-if="item.uuid" :href="route('jav.vue.movies.show', item.uuid)" class="text-decoration-none">{{ item.code }}</Link>
                                                <template v-else>{{ item.code }}</template>
                                            </td>
                                            <td class="text-end">{{ Number(item.ratings_avg_rating || 0).toFixed(2) }}</td>
                                            <td class="text-end">{{ Number(item.ratings_count || 0).toLocaleString() }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
