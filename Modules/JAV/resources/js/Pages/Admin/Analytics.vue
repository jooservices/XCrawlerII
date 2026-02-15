<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import VueApexCharts from 'vue3-apexcharts';
import ActorInsightsPanel from '@jav/Components/ActorInsightsPanel.vue';
import PageShell from '@jav/Components/UI/PageShell.vue';
import SectionHeader from '@jav/Components/UI/SectionHeader.vue';

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

const chartPalette = {
    primary: 'var(--primary-strong)',
    success: 'var(--success)',
    danger: 'var(--danger)',
    warning: 'var(--warning)',
    info: 'var(--info)',
    purple: 'var(--accent-purple)',
    orange: 'var(--accent-orange)',
    border: 'var(--border)',
};

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
        borderColor: chartPalette.border,
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
    colors: [chartPalette.primary, chartPalette.success, chartPalette.purple],
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
    colors: [chartPalette.danger, chartPalette.warning, chartPalette.info, chartPalette.success],
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
    colors: [chartPalette.primary, chartPalette.success, chartPalette.danger, chartPalette.purple, chartPalette.orange, chartPalette.info],
}));

const mode = ref('basic');
const isAdvancedLoading = ref(false);
const advancedMessage = ref('');
const advancedMessageType = ref('info');

const overviewResult = ref({
    total_actors: 0,
    top_genres: [],
    age_buckets: [],
    blood_types: [],
    actors_timeline: [],
});

const distributionDimension = ref('age_bucket');
const distributionGenre = ref('');
const distributionSize = ref(10);
const distributionResult = ref([]);
const distributionGenreSuggestions = ref([]);

const associationSegmentType = ref('blood_type');
const associationSegmentValue = ref('A');
const associationSize = ref(10);
const associationResult = ref([]);

const trendDimension = ref('age_bucket');
const trendGenre = ref('');
const trendInterval = ref('month');
const trendResult = ref([]);
const trendGenreSuggestions = ref([]);

const predictAge = ref(null);
const predictBloodType = ref('');
const predictBirthplace = ref('');
const predictTags = ref('');
const predictionResult = ref([]);
const predictionMatchedActors = ref(0);
const predictBloodTypeSuggestions = ref([]);
const predictBirthplaceSuggestions = ref([]);

const actorInsightsUuid = ref('');
const actorInsightsResult = ref(null);
const actorSearchQuery = ref('');
const actorSearchSuggestions = ref([]);
const suggestionLoading = ref(false);

const suggestionTimerIds = {
    actor: null,
    distributionGenre: null,
    trendGenre: null,
    predictBirthplace: null,
    predictBloodType: null,
};

const qualityResult = ref({});

const overviewGenreChartOptions = computed(() => ({
    chart: {
        toolbar: { show: false },
    },
    dataLabels: { enabled: false },
    xaxis: {
        categories: (overviewResult.value.top_genres || []).map((item) => item.genre),
        labels: {
            rotate: -30,
            hideOverlappingLabels: true,
        },
    },
    yaxis: {
        min: 0,
        labels: {
            formatter: (value) => Math.round(Number(value || 0)).toLocaleString(),
        },
    },
    colors: [chartPalette.primary],
}));

const overviewGenreSeries = computed(() => ([
    {
        name: 'Movies',
        data: (overviewResult.value.top_genres || []).map((item) => Number(item.count || 0)),
    },
]));

const overviewAgeChartOptions = computed(() => ({
    chart: {
        toolbar: { show: false },
    },
    labels: (overviewResult.value.age_buckets || []).map((item) => item.bucket),
    legend: {
        position: 'bottom',
    },
}));

const overviewAgeSeries = computed(() => (overviewResult.value.age_buckets || []).map((item) => Number(item.count || 0)));

const setAdvancedMessage = (text, type = 'info') => {
    advancedMessage.value = text;
    advancedMessageType.value = type;
};

const requestGet = async (routeName, params = {}) => {
    const response = await axios.get(route(routeName), {
        params,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    return response.data;
};

const requestPost = async (routeName, payload = {}) => {
    const response = await axios.post(route(routeName), payload, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    return response.data;
};

const fetchSuggestions = async (type, query, size = 8) => {
    const body = await requestGet('jav.admin.analytics.suggest', {
        type,
        q: query,
        size,
    });

    return body.items || [];
};

const scheduleSuggestions = (key, callback) => {
    if (suggestionTimerIds[key] !== null) {
        globalThis.clearTimeout(suggestionTimerIds[key]);
    }

    suggestionTimerIds[key] = globalThis.setTimeout(callback, 250);
};

const runDistribution = async () => {
    if (!distributionGenre.value.trim()) {
        setAdvancedMessage('Genre is required for distribution analytics.', 'warning');
        return;
    }

    isAdvancedLoading.value = true;
    try {
        const body = await requestGet('jav.admin.analytics.distribution', {
            dimension: distributionDimension.value,
            genre: distributionGenre.value.trim(),
            size: distributionSize.value,
        });
        distributionResult.value = body.segments || [];
        setAdvancedMessage('Distribution analytics updated.', 'success');
    } catch (error) {
        setAdvancedMessage(error.response?.data?.message || 'Distribution request failed.', 'danger');
    } finally {
        isAdvancedLoading.value = false;
    }
};

const runAssociation = async () => {
    if (!associationSegmentValue.value.trim()) {
        setAdvancedMessage('Segment value is required for association analytics.', 'warning');
        return;
    }

    isAdvancedLoading.value = true;
    try {
        const body = await requestGet('jav.admin.analytics.association', {
            segment_type: associationSegmentType.value,
            segment_value: associationSegmentValue.value.trim(),
            size: associationSize.value,
        });
        associationResult.value = body.rules || [];
        setAdvancedMessage('Association rules updated.', 'success');
    } catch (error) {
        setAdvancedMessage(error.response?.data?.message || 'Association request failed.', 'danger');
    } finally {
        isAdvancedLoading.value = false;
    }
};

const runTrends = async () => {
    isAdvancedLoading.value = true;
    try {
        const body = await requestGet('jav.admin.analytics.trends', {
            dimension: trendDimension.value,
            genre: trendGenre.value.trim() || undefined,
            interval: trendInterval.value,
            size: 5,
        });
        trendResult.value = body.periods || [];
        setAdvancedMessage('Trend analytics updated.', 'success');
    } catch (error) {
        setAdvancedMessage(error.response?.data?.message || 'Trend request failed.', 'danger');
    } finally {
        isAdvancedLoading.value = false;
    }
};

const runPrediction = async () => {
    isAdvancedLoading.value = true;
    try {
        const body = await requestPost('jav.admin.analytics.predict', {
            age: predictAge.value || null,
            blood_type: predictBloodType.value.trim() || null,
            birthplace: predictBirthplace.value.trim() || null,
            movie_tags: predictTags.value
                .split(',')
                .map((tag) => tag.trim())
                .filter((tag) => tag !== ''),
            size: 5,
        });
        predictionMatchedActors.value = Number(body.matched_actors || 0);
        predictionResult.value = body.predictions || [];
        setAdvancedMessage('Prediction updated.', 'success');
    } catch (error) {
        setAdvancedMessage(error.response?.data?.message || 'Prediction request failed.', 'danger');
    } finally {
        isAdvancedLoading.value = false;
    }
};

const loadQuality = async () => {
    isAdvancedLoading.value = true;
    try {
        const body = await requestGet('jav.admin.analytics.quality');
        qualityResult.value = body;
        setAdvancedMessage('Data quality coverage loaded.', 'success');
    } catch (error) {
        setAdvancedMessage(error.response?.data?.message || 'Quality request failed.', 'danger');
    } finally {
        isAdvancedLoading.value = false;
    }
};

const loadOverview = async () => {
    isAdvancedLoading.value = true;
    try {
        const body = await requestGet('jav.admin.analytics.overview', { size: 8 });
        overviewResult.value = body || {};
        setAdvancedMessage('Overview loaded.', 'success');
    } catch (error) {
        setAdvancedMessage(error.response?.data?.message || 'Overview request failed.', 'danger');
    } finally {
        isAdvancedLoading.value = false;
    }
};

const runActorInsights = async () => {
    if (!actorInsightsUuid.value.trim()) {
        const query = actorSearchQuery.value.trim();
        if (!query) {
            setAdvancedMessage('Actor name is required for actor insights.', 'warning');
            return;
        }

        try {
            const candidates = await fetchSuggestions('actor', query, 8);
            const exactMatch = candidates.find((item) => (item.name || '').toLowerCase() === query.toLowerCase());
            const selected = exactMatch || candidates[0] || null;

            if (!selected?.value) {
                setAdvancedMessage('No actor found. Please refine actor name.', 'warning');
                return;
            }

            actorInsightsUuid.value = selected.value;
            actorSearchQuery.value = selected.name || selected.label || query;
        } catch {
            setAdvancedMessage('Unable to resolve actor name right now.', 'danger');
            return;
        }
    }

    isAdvancedLoading.value = true;
    try {
        const body = await requestGet('jav.admin.analytics.actor-insights', {
            actor_uuid: actorInsightsUuid.value.trim(),
            size: 5,
        });
        actorInsightsResult.value = body;
        setAdvancedMessage('Actor insights loaded.', 'success');
    } catch (error) {
        actorInsightsResult.value = null;
        setAdvancedMessage(error.response?.data?.message || 'Actor insights request failed.', 'danger');
    } finally {
        isAdvancedLoading.value = false;
    }
};

const pickActorSuggestion = (item) => {
    actorInsightsUuid.value = item.value || '';
    actorSearchQuery.value = item.name || item.label || '';
    actorSearchSuggestions.value = [];
};

const onActorSearchInput = () => {
    actorInsightsUuid.value = '';
    scheduleSuggestions('actor', async () => {
        const query = actorSearchQuery.value.trim();
        if (query.length < 2) {
            actorSearchSuggestions.value = [];
            return;
        }

        suggestionLoading.value = true;
        try {
            actorSearchSuggestions.value = await fetchSuggestions('actor', query, 8);
        } catch {
            actorSearchSuggestions.value = [];
        } finally {
            suggestionLoading.value = false;
        }
    });
};

const onDistributionGenreInput = () => {
    scheduleSuggestions('distributionGenre', async () => {
        const query = distributionGenre.value.trim();
        if (query.length < 1) {
            distributionGenreSuggestions.value = [];
            return;
        }

        try {
            distributionGenreSuggestions.value = await fetchSuggestions('genre', query, 8);
        } catch {
            distributionGenreSuggestions.value = [];
        }
    });
};

const onTrendGenreInput = () => {
    scheduleSuggestions('trendGenre', async () => {
        const query = trendGenre.value.trim();
        if (query.length < 1) {
            trendGenreSuggestions.value = [];
            return;
        }

        try {
            trendGenreSuggestions.value = await fetchSuggestions('genre', query, 8);
        } catch {
            trendGenreSuggestions.value = [];
        }
    });
};

const onPredictBirthplaceInput = () => {
    scheduleSuggestions('predictBirthplace', async () => {
        const query = predictBirthplace.value.trim();
        if (query.length < 1) {
            predictBirthplaceSuggestions.value = [];
            return;
        }

        try {
            predictBirthplaceSuggestions.value = await fetchSuggestions('birthplace', query, 8);
        } catch {
            predictBirthplaceSuggestions.value = [];
        }
    });
};

const onPredictBloodTypeInput = () => {
    scheduleSuggestions('predictBloodType', async () => {
        const query = predictBloodType.value.trim();
        if (query.length < 1) {
            predictBloodTypeSuggestions.value = [];
            return;
        }

        try {
            predictBloodTypeSuggestions.value = await fetchSuggestions('blood_type', query, 8);
        } catch {
            predictBloodTypeSuggestions.value = [];
        }
    });
};

const applyGuideExample = (example) => {
    if (example === 'distribution') {
        distributionDimension.value = 'age_bucket';
        distributionGenre.value = 'drama';
        runDistribution();
        return;
    }

    if (example === 'association') {
        associationSegmentType.value = 'blood_type';
        associationSegmentValue.value = 'A';
        runAssociation();
        return;
    }

    if (example === 'predict') {
        predictAge.value = 24;
        predictBloodType.value = 'A';
        predictBirthplace.value = 'Tokyo';
        predictTags.value = 'drama,school';
        runPrediction();
        return;
    }

    actorSearchQuery.value = '';
    actorInsightsUuid.value = '';
    actorSearchSuggestions.value = [];
    setAdvancedMessage('Type actor name in the search box below, then click a suggestion or load directly.', 'info');
};

watch(mode, (nextMode) => {
    if (nextMode === 'advanced') {
        if (Object.keys(overviewResult.value || {}).length === 0 || !overviewResult.value.top_genres) {
            loadOverview();
        }
        if (Object.keys(qualityResult.value || {}).length === 0) {
            loadQuality();
        }
    }
});
</script>

<template>
    <Head>
        <title>Analytics</title>
    </Head>

    <PageShell>
        <template #header>
            <SectionHeader title="Analytics" subtitle="Basic and advanced catalog insights" />
        </template>

        <template #actions>
            <div class="u-flex u-items-center gap-2">
                <button type="button" class="ui-btn" :class="mode === 'basic' ? 'ui-btn-primary' : 'ui-btn-outline-primary'" @click="mode = 'basic'">Basic</button>
                <button type="button" class="ui-btn" :class="mode === 'advanced' ? 'ui-btn-primary' : 'ui-btn-outline-primary'" @click="mode = 'advanced'">Advanced</button>
            </div>
        </template>

        <div v-if="mode === 'basic'">
            <div class="u-flex u-justify-end mb-3">
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
                            <VueApexCharts type="line" height="280" :options="createdChartOptions" :series="createdSeries" />
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
                            <VueApexCharts type="bar" height="280" :options="engagementChartOptions" :series="engagementSeries" />
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
                            <VueApexCharts type="line" height="280" :options="providerChartOptions" :series="providerSeries" />
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

        <div v-else>
            <div v-if="advancedMessage" class="ui-alert mb-3" :class="`ui-alert-${advancedMessageType}`">{{ advancedMessage }}</div>

            <div class="ui-row ui-g-3 mb-3">
                <div class="ui-col-md-4">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <p class="u-text-muted mb-1">Indexed Actors</p>
                            <h4 class="mb-0">{{ Number(overviewResult.total_actors || 0).toLocaleString() }}</h4>
                        </div>
                    </div>
                </div>
                <div class="ui-col-md-4">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <p class="u-text-muted mb-1">Top Genre</p>
                            <h4 class="mb-0">{{ overviewResult.top_genres?.[0]?.genre || '--' }}</h4>
                        </div>
                    </div>
                </div>
                <div class="ui-col-md-4">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <p class="u-text-muted mb-1">Top Blood Type</p>
                            <h4 class="mb-0">{{ overviewResult.blood_types?.[0]?.type || '--' }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ui-row ui-g-3 mb-3">
                <div class="ui-col-lg-8">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <div class="u-flex u-justify-between u-items-center mb-2">
                                <h5 class="ui-card-title mb-0">Overview: Top Genres</h5>
                                <button type="button" class="ui-btn ui-btn-outline-primary ui-btn-sm" :disabled="isAdvancedLoading" @click="loadOverview">Refresh</button>
                            </div>
                            <VueApexCharts type="bar" height="260" :options="overviewGenreChartOptions" :series="overviewGenreSeries" />
                        </div>
                    </div>
                </div>
                <div class="ui-col-lg-4">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <h5 class="ui-card-title">Overview: Age Buckets</h5>
                            <VueApexCharts type="donut" height="260" :options="overviewAgeChartOptions" :series="overviewAgeSeries" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="ui-card mb-3">
                <div class="ui-card-body">
                    <h5 class="ui-card-title mb-2">Advanced Guide</h5>
                    <div class="ui-row ui-g-2">
                        <div class="ui-col-lg-6">
                            <div class="ui-border rounded p-2 h-100">
                                <h6 class="mb-1">1) Distribution (Genre → Segment)</h6>
                                <p class="small mb-1"><strong>Goal:</strong> Which segment appears most in one genre.</p>
                                <p class="small mb-1"><strong>Input sample:</strong> Dimension <code>age_bucket</code>, Genre <code>drama</code>.</p>
                                <p class="small mb-2"><strong>Read result:</strong> Higher <code>confidence</code> and <code>lift</code> means stronger concentration.</p>
                                <button type="button" class="ui-btn ui-btn-outline-primary ui-btn-sm" :disabled="isAdvancedLoading" @click="applyGuideExample('distribution')">Try this sample</button>
                            </div>
                        </div>
                        <div class="ui-col-lg-6">
                            <div class="ui-border rounded p-2 h-100">
                                <h6 class="mb-1">2) Association (Segment → Genre)</h6>
                                <p class="small mb-1"><strong>Goal:</strong> Which genres are common for a segment.</p>
                                <p class="small mb-1"><strong>Input sample:</strong> Type <code>blood_type</code>, Value <code>A</code>.</p>
                                <p class="small mb-2"><strong>Read result:</strong> Sort by <code>confidence</code> or <code>lift</code> for best signals.</p>
                                <button type="button" class="ui-btn ui-btn-outline-primary ui-btn-sm" :disabled="isAdvancedLoading" @click="applyGuideExample('association')">Try this sample</button>
                            </div>
                        </div>
                        <div class="ui-col-lg-6">
                            <div class="ui-border rounded p-2 h-100">
                                <h6 class="mb-1">3) Predict Genres (Profile)</h6>
                                <p class="small mb-1"><strong>Goal:</strong> Estimate likely genres for a profile.</p>
                                <p class="small mb-1"><strong>Input sample:</strong> Age <code>24</code>, Blood <code>A</code>, Birthplace <code>Tokyo</code>, Tags <code>drama,school</code>.</p>
                                <p class="small mb-2"><strong>Read result:</strong> <code>probability</code> ranks expected genres for similar actors.</p>
                                <button type="button" class="ui-btn ui-btn-outline-primary ui-btn-sm" :disabled="isAdvancedLoading" @click="applyGuideExample('predict')">Try this sample</button>
                            </div>
                        </div>
                        <div class="ui-col-lg-6">
                            <div class="ui-border rounded p-2 h-100">
                                <h6 class="mb-1">4) Actor Insights (Forecast + Period)</h6>
                                <p class="small mb-1"><strong>Goal:</strong> See one actor’s predicted genres and week/month/year counts.</p>
                                <p class="small mb-1"><strong>How to use:</strong> Type actor name and click load (or pick a suggestion).</p>
                                <p class="small mb-2"><strong>Read result:</strong> Compare totals and averages across windows for stability.</p>
                                <button type="button" class="ui-btn ui-btn-outline-primary ui-btn-sm" :disabled="isAdvancedLoading" @click="applyGuideExample('actor')">Reset actor input</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ui-row ui-g-3 mb-3">
                <div class="ui-col-lg-6">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <h5 class="ui-card-title">Distribution (Genre -> Segment)</h5>
                            <div class="ui-row ui-g-2">
                                <div class="ui-col-md-4"><select v-model="distributionDimension" class="ui-form-select"><option value="age_bucket">Age bucket</option><option value="blood_type">Blood type</option><option value="birthplace">Birthplace</option></select></div>
                                <div class="ui-col-md-5"><input v-model="distributionGenre" class="ui-form-control" placeholder="Genre, ex: drama" @input="onDistributionGenreInput" list="distribution-genre-suggestions"></div>
                                <div class="ui-col-md-3"><input v-model.number="distributionSize" type="number" min="1" max="30" class="ui-form-control"></div>
                            </div>
                            <datalist id="distribution-genre-suggestions">
                                <option v-for="item in distributionGenreSuggestions" :key="`distribution-genre-${item.value}`" :value="item.value">{{ item.label }}</option>
                            </datalist>
                            <button type="button" class="ui-btn ui-btn-primary mt-2" :disabled="isAdvancedLoading" @click="runDistribution">Run</button>
                            <div class="ui-table-responsive mt-3">
                                <table class="ui-table ui-table-sm ui-table-striped mb-0">
                                    <thead><tr><th>Segment</th><th class="u-text-end">Count</th><th class="u-text-end">Confidence</th><th class="u-text-end">Lift</th></tr></thead>
                                    <tbody>
                                        <tr v-if="distributionResult.length === 0"><td colspan="4" class="u-text-muted">No data.</td></tr>
                                        <tr v-for="row in distributionResult" :key="row.segment">
                                            <td>{{ row.segment }}</td>
                                            <td class="u-text-end">{{ Number(row.count || 0).toLocaleString() }}</td>
                                            <td class="u-text-end">{{ Number(row.confidence || 0).toFixed(4) }}</td>
                                            <td class="u-text-end">{{ row.lift === null ? '--' : Number(row.lift).toFixed(4) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ui-col-lg-6">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <h5 class="ui-card-title">Association (Segment -> Genre)</h5>
                            <div class="ui-row ui-g-2">
                                <div class="ui-col-md-4"><select v-model="associationSegmentType" class="ui-form-select"><option value="age_bucket">Age bucket</option><option value="blood_type">Blood type</option><option value="birthplace">Birthplace</option></select></div>
                                <div class="ui-col-md-5"><input v-model="associationSegmentValue" class="ui-form-control" placeholder="A / 23-27 / Tokyo"></div>
                                <div class="ui-col-md-3"><input v-model.number="associationSize" type="number" min="1" max="30" class="ui-form-control"></div>
                            </div>
                            <button type="button" class="ui-btn ui-btn-primary mt-2" :disabled="isAdvancedLoading" @click="runAssociation">Run</button>
                            <div class="ui-table-responsive mt-3">
                                <table class="ui-table ui-table-sm ui-table-striped mb-0">
                                    <thead><tr><th>Genre</th><th class="u-text-end">Count</th><th class="u-text-end">Support</th><th class="u-text-end">Confidence</th><th class="u-text-end">Lift</th></tr></thead>
                                    <tbody>
                                        <tr v-if="associationResult.length === 0"><td colspan="5" class="u-text-muted">No data.</td></tr>
                                        <tr v-for="row in associationResult" :key="row.genre">
                                            <td>
                                                <Link :href="route('jav.vue.dashboard', { tag: row.genre })" class="u-no-underline">
                                                    {{ row.genre }}
                                                </Link>
                                            </td>
                                            <td class="u-text-end">{{ Number(row.count || 0).toLocaleString() }}</td>
                                            <td class="u-text-end">{{ Number(row.support || 0).toFixed(4) }}</td>
                                            <td class="u-text-end">{{ Number(row.confidence || 0).toFixed(4) }}</td>
                                            <td class="u-text-end">{{ row.lift === null ? '--' : Number(row.lift).toFixed(4) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ui-row ui-g-3 mb-3">
                <div class="ui-col-lg-6">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <h5 class="ui-card-title">Trends</h5>
                            <div class="ui-row ui-g-2">
                                <div class="ui-col-md-4"><select v-model="trendDimension" class="ui-form-select"><option value="age_bucket">Age bucket</option><option value="blood_type">Blood type</option><option value="birthplace">Birthplace</option></select></div>
                                <div class="ui-col-md-4"><input v-model="trendGenre" class="ui-form-control" placeholder="Optional genre" @input="onTrendGenreInput" list="trend-genre-suggestions"></div>
                                <div class="ui-col-md-4"><select v-model="trendInterval" class="ui-form-select"><option value="month">Month</option><option value="week">Week</option></select></div>
                            </div>
                            <datalist id="trend-genre-suggestions">
                                <option v-for="item in trendGenreSuggestions" :key="`trend-genre-${item.value}`" :value="item.value">{{ item.label }}</option>
                            </datalist>
                            <button type="button" class="ui-btn ui-btn-primary mt-2" :disabled="isAdvancedLoading" @click="runTrends">Run</button>
                            <div class="ui-table-responsive mt-3">
                                <table class="ui-table ui-table-sm ui-table-striped mb-0">
                                    <thead><tr><th>Period</th><th class="u-text-end">Total</th><th>Top Segment</th><th class="u-text-end">Share</th></tr></thead>
                                    <tbody>
                                        <tr v-if="trendResult.length === 0"><td colspan="4" class="u-text-muted">No data.</td></tr>
                                        <tr v-for="row in trendResult" :key="row.period">
                                            <td>{{ row.period }}</td>
                                            <td class="u-text-end">{{ Number(row.total || 0).toLocaleString() }}</td>
                                            <td>{{ row.top_segment || '--' }}</td>
                                            <td class="u-text-end">{{ Number(row.top_share || 0).toFixed(4) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ui-col-lg-6">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <h5 class="ui-card-title">Predict Genres from Actor Profile</h5>
                            <div class="ui-row ui-g-2">
                                <div class="ui-col-md-4"><input v-model.number="predictAge" type="number" min="16" max="80" class="ui-form-control" placeholder="Age"></div>
                                <div class="ui-col-md-4"><input v-model="predictBloodType" class="ui-form-control" placeholder="Blood type" list="predict-blood-type-suggestions" @input="onPredictBloodTypeInput"></div>
                                <div class="ui-col-md-4"><input v-model="predictBirthplace" class="ui-form-control" placeholder="Birthplace" list="predict-birthplace-suggestions" @input="onPredictBirthplaceInput"></div>
                            </div>
                            <datalist id="predict-blood-type-suggestions">
                                <option v-for="item in predictBloodTypeSuggestions" :key="`predict-blood-type-${item.value}`" :value="item.value">{{ item.label }}</option>
                            </datalist>
                            <datalist id="predict-birthplace-suggestions">
                                <option v-for="item in predictBirthplaceSuggestions" :key="`predict-birthplace-${item.value}`" :value="item.value">{{ item.label }}</option>
                            </datalist>
                            <div class="mt-2"><input v-model="predictTags" class="ui-form-control" placeholder="Movie tags (comma-separated)"></div>
                            <button type="button" class="ui-btn ui-btn-primary mt-2" :disabled="isAdvancedLoading" @click="runPrediction">Predict</button>
                            <p class="small u-text-muted mt-2 mb-1">Matched actors: {{ Number(predictionMatchedActors || 0).toLocaleString() }}</p>
                            <div class="ui-table-responsive">
                                <table class="ui-table ui-table-sm ui-table-striped mb-0">
                                    <thead><tr><th>Genre</th><th class="u-text-end">Count</th><th class="u-text-end">Probability</th></tr></thead>
                                    <tbody>
                                        <tr v-if="predictionResult.length === 0"><td colspan="3" class="u-text-muted">No prediction data.</td></tr>
                                        <tr v-for="row in predictionResult" :key="row.genre">
                                            <td>
                                                <Link :href="route('jav.vue.dashboard', { tag: row.genre })" class="u-no-underline">
                                                    {{ row.genre }}
                                                </Link>
                                            </td>
                                            <td class="u-text-end">{{ Number(row.count || 0).toLocaleString() }}</td>
                                            <td class="u-text-end">{{ Number(row.probability || 0).toFixed(4) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ui-card mb-3">
                <div class="ui-card-body">
                    <h5 class="ui-card-title">Actor Insights (Forecast + Period Counts)</h5>
                    <div class="ui-row ui-g-2 mb-2">
                        <div class="ui-col-md-9">
                            <input v-model="actorSearchQuery" class="ui-form-control" placeholder="Actor name (ex: Yua Mikami)" @input="onActorSearchInput">
                            <div v-if="actorSearchSuggestions.length > 0" class="ui-list-group mt-1">
                                <button
                                    v-for="item in actorSearchSuggestions"
                                    :key="`actor-suggestion-${item.value}`"
                                    type="button"
                                    class="ui-list-group-item ui-list-group-item-action"
                                    @click="pickActorSuggestion(item)"
                                >
                                    {{ item.name || item.label }}
                                </button>
                            </div>
                            <small v-else-if="suggestionLoading" class="u-text-muted">Searching actors...</small>
                        </div>
                        <div class="ui-col-md-3">
                            <button type="button" class="ui-btn ui-btn-primary w-100" :disabled="isAdvancedLoading" @click="runActorInsights">Load Actor Insights</button>
                        </div>
                    </div>

                    <div v-if="actorInsightsResult">
                        <p class="mb-2">
                            <strong>Actor:</strong>
                            {{ actorInsightsResult.actor?.name || '--' }}
                        </p>
                        <ActorInsightsPanel :actor-insights="actorInsightsResult" :show-title="false" />
                    </div>
                </div>
            </div>

            <div class="ui-card">
                <div class="ui-card-body">
                    <div class="u-flex u-justify-between u-items-center mb-2">
                        <h5 class="ui-card-title mb-0">Data Quality Coverage</h5>
                        <button type="button" class="ui-btn ui-btn-outline-primary ui-btn-sm" :disabled="isAdvancedLoading" @click="loadQuality">Refresh</button>
                    </div>
                    <div class="ui-table-responsive">
                        <table class="ui-table ui-table-sm ui-table-striped mb-0">
                            <thead><tr><th>Field</th><th class="u-text-end">Count</th><th class="u-text-end">Rate</th><th class="u-text-end">Missing</th></tr></thead>
                            <tbody>
                                <tr v-for="(value, key) in qualityResult.coverage || {}" :key="key">
                                    <td>{{ key }}</td>
                                    <td class="u-text-end">{{ Number(value.count || 0).toLocaleString() }}</td>
                                    <td class="u-text-end">{{ Number(value.rate || 0).toFixed(4) }}</td>
                                    <td class="u-text-end">{{ Number(value.missing || 0).toLocaleString() }}</td>
                                </tr>
                                <tr v-if="Object.keys(qualityResult.coverage || {}).length === 0"><td colspan="4" class="u-text-muted">No quality data loaded.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </PageShell>
</template>
