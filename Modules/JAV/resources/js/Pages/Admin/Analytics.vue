<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

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

const createdLabels = computed(() => props.dailyCreated?.jav?.labels || []);
const baseChartOptions = computed(() => ({
    chart: {
        toolbar: { show: false },
        zoom: { enabled: false },
    },
    dataLabels: { enabled: false },
    xaxis: {
        categories: createdLabels.value,
        labels: {
            rotate: -45,
            hideOverlappingLabels: true,
        },
    },
    yaxis: {
        min: 0,
        forceNiceScale: true,
        labels: {
            formatter: (value) => Math.round(Number(value || 0)).toLocaleString(),
        },
    },
    grid: {
        borderColor: '#e9ecef',
        strokeDashArray: 3,
    },
    legend: {
        position: 'top',
        horizontalAlign: 'left',
    },
    tooltip: {
        y: {
            formatter: (value) => Math.round(Number(value || 0)).toLocaleString(),
        },
    },
}));

const createdSeries = computed(() => ([
    { name: 'Movies', data: props.dailyCreated?.jav?.values || [] },
    { name: 'Actors', data: props.dailyCreated?.actors?.values || [] },
    { name: 'Tags', data: props.dailyCreated?.tags?.values || [] },
]));

const createdChartOptions = computed(() => ({
    ...baseChartOptions.value,
    stroke: { width: 2, curve: 'smooth' },
    colors: ['#0d6efd', '#198754', '#6f42c1'],
}));

const engagementSeries = computed(() => ([
    { name: 'Likes', data: props.dailyEngagement?.favorites?.values || [] },
    { name: 'Watchlist', data: props.dailyEngagement?.watchlists?.values || [] },
    { name: 'Ratings', data: props.dailyEngagement?.ratings?.values || [] },
    { name: 'History Events', data: props.dailyEngagement?.history?.values || [] },
]));

const engagementChartOptions = computed(() => ({
    ...baseChartOptions.value,
    chart: {
        ...baseChartOptions.value.chart,
        stacked: false,
    },
    plotOptions: {
        bar: {
            borderRadius: 4,
            columnWidth: '55%',
        },
    },
    colors: ['#dc3545', '#ffc107', '#0dcaf0', '#20c997'],
}));

const providerSeries = computed(() => {
    const series = props.providerDailyCreated?.series || {};
    return Object.entries(series).map(([source, values]) => ({
        name: source,
        data: values,
    }));
});

const providerChartOptions = computed(() => ({
    ...baseChartOptions.value,
    stroke: { width: 2, curve: 'smooth' },
    colors: ['#0d6efd', '#198754', '#dc3545', '#6f42c1', '#fd7e14', '#20c997'],
}));
</script>

<template>
    <Head title="Analytics" />

    
        <div class="ui-container-fluid">
            <div class="u-flex u-justify-between u-items-center mb-3">
                <h2 class="mb-0">Analytics</h2>
                <form method="GET" :action="route('jav.vue.admin.analytics')" class="u-flex u-items-center gap-2">
                    <label for="days" class="small u-text-muted mb-0">Window</label>
                    <select id="days" name="days" class="ui-form-select ui-form-select-sm" onchange="this.form.submit()">
                        <option v-for="option in [7, 14, 30, 90]" :key="option" :value="option" :selected="days === option">
                            Last {{ option }} days
                        </option>
                    </select>
                </form>
            </div>

            <div class="ui-row ui-g-3 mb-3">
                <div class="ui-col-md-4">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <p class="u-text-muted mb-1">Total Movies (JAV)</p>
                            <h3 class="mb-1">{{ Number(totals?.jav || 0).toLocaleString() }}</h3>
                            <small class="u-text-muted">Created today: {{ Number(todayCreated?.jav || 0).toLocaleString() }}</small>
                        </div>
                    </div>
                </div>
                <div class="ui-col-md-4">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <p class="u-text-muted mb-1">Total Actors</p>
                            <h3 class="mb-1">{{ Number(totals?.actors || 0).toLocaleString() }}</h3>
                            <small class="u-text-muted">Created today: {{ Number(todayCreated?.actors || 0).toLocaleString() }}</small>
                        </div>
                    </div>
                </div>
                <div class="ui-col-md-4">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <p class="u-text-muted mb-1">Total Tags</p>
                            <h3 class="mb-1">{{ Number(totals?.tags || 0).toLocaleString() }}</h3>
                            <small class="u-text-muted">Created today: {{ Number(todayCreated?.tags || 0).toLocaleString() }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ui-row ui-g-3 mb-3">
                <div class="ui-col-lg-8">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <h5 class="ui-card-title">Daily Created (Movies / Actors / Tags)</h5>
                            <VueApexCharts
                                type="line"
                                height="280"
                                :options="createdChartOptions"
                                :series="createdSeries"
                            />
                        </div>
                    </div>
                </div>
                <div class="ui-col-lg-4">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <h5 class="ui-card-title">By Provider (Movies)</h5>
                            <div class="ui-table-responsive">
                                <table class="ui-table ui-table-sm ui-table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Provider</th>
                                            <th class="u-text-end">Total</th>
                                            <th class="u-text-end">Today</th>
                                            <th class="u-text-end">Last {{ days }}d</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="(providerStats || []).length === 0"><td colspan="4" class="u-text-muted">No provider data.</td></tr>
                                        <tr v-for="provider in providerStats || []" :key="provider.source">
                                            <td><code>{{ provider.source || 'unknown' }}</code></td>
                                            <td class="u-text-end">{{ Number(provider.total_count || 0).toLocaleString() }}</td>
                                            <td class="u-text-end">{{ Number(provider.today_count || 0).toLocaleString() }}</td>
                                            <td class="u-text-end">{{ Number(provider.window_count || 0).toLocaleString() }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ui-row ui-g-3 mb-3">
                <div class="ui-col-lg-8">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <h5 class="ui-card-title">Daily Engagement (Likes / Watchlist / Ratings / History)</h5>
                            <VueApexCharts
                                type="bar"
                                height="280"
                                :options="engagementChartOptions"
                                :series="engagementSeries"
                            />
                        </div>
                    </div>
                </div>
                <div class="ui-col-lg-4">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <h5 class="ui-card-title">Data Quality</h5>
                            <ul class="ui-list-unstyled mb-0">
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

            <div class="ui-row ui-g-3 mb-3">
                <div class="ui-col-lg-8">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <h5 class="ui-card-title">Daily Movies Created by Provider</h5>
                            <VueApexCharts
                                type="line"
                                height="280"
                                :options="providerChartOptions"
                                :series="providerSeries"
                            />
                        </div>
                    </div>
                </div>
                <div class="ui-col-lg-4">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <h5 class="ui-card-title">Sync Health</h5>
                            <p class="mb-2"><strong>Pending Jobs:</strong> {{ Number(syncHealth?.pending_jobs || 0).toLocaleString() }}</p>
                            <p class="mb-0"><strong>Failed Jobs (24h):</strong> {{ Number(syncHealth?.failed_jobs_24h || 0).toLocaleString() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ui-row ui-g-3">
                <div class="ui-col-lg-4">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <h5 class="ui-card-title">Top Viewed Movies</h5>
                            <div class="ui-table-responsive">
                                <table class="ui-table ui-table-sm ui-table-striped mb-0">
                                    <thead><tr><th>Movie</th><th class="u-text-end">Views</th></tr></thead>
                                    <tbody>
                                        <tr v-if="(topViewed || []).length === 0"><td colspan="2" class="u-text-muted">No data.</td></tr>
                                        <tr v-for="item in topViewed || []" :key="`view-${item.uuid || item.code}`">
                                            <td>
                                                <Link v-if="item.uuid" :href="route('jav.vue.movies.show', item.uuid)" class="u-no-underline">{{ item.code }}</Link>
                                                <template v-else>{{ item.code }}</template>
                                            </td>
                                            <td class="u-text-end">{{ Number(item.views || 0).toLocaleString() }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ui-col-lg-4">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <h5 class="ui-card-title">Top Downloaded Movies</h5>
                            <div class="ui-table-responsive">
                                <table class="ui-table ui-table-sm ui-table-striped mb-0">
                                    <thead><tr><th>Movie</th><th class="u-text-end">Downloads</th></tr></thead>
                                    <tbody>
                                        <tr v-if="(topDownloaded || []).length === 0"><td colspan="2" class="u-text-muted">No data.</td></tr>
                                        <tr v-for="item in topDownloaded || []" :key="`download-${item.uuid || item.code}`">
                                            <td>
                                                <Link v-if="item.uuid" :href="route('jav.vue.movies.show', item.uuid)" class="u-no-underline">{{ item.code }}</Link>
                                                <template v-else>{{ item.code }}</template>
                                            </td>
                                            <td class="u-text-end">{{ Number(item.downloads || 0).toLocaleString() }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ui-col-lg-4">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <h5 class="ui-card-title">Top Rated Movies</h5>
                            <div class="ui-table-responsive">
                                <table class="ui-table ui-table-sm ui-table-striped mb-0">
                                    <thead><tr><th>Movie</th><th class="u-text-end">Avg</th><th class="u-text-end">Count</th></tr></thead>
                                    <tbody>
                                        <tr v-if="(topRated || []).length === 0"><td colspan="3" class="u-text-muted">No data.</td></tr>
                                        <tr v-for="item in topRated || []" :key="`rated-${item.uuid || item.code}`">
                                            <td>
                                                <Link v-if="item.uuid" :href="route('jav.vue.movies.show', item.uuid)" class="u-no-underline">{{ item.code }}</Link>
                                                <template v-else>{{ item.code }}</template>
                                            </td>
                                            <td class="u-text-end">{{ Number(item.ratings_avg_rating || 0).toFixed(2) }}</td>
                                            <td class="u-text-end">{{ Number(item.ratings_count || 0).toLocaleString() }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
</template>
