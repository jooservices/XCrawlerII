<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import VueApexCharts from 'vue3-apexcharts';

const loading = ref(false);
const error = ref('');
const payload = ref(null);

const filters = ref({
    window_minutes: 60,
    site: '',
    job_name: '',
    limit: 30,
});

const summary = computed(() => payload.value?.overview || {});
const jobPerformance = computed(() => payload.value?.job_performance || []);
const failures = computed(() => payload.value?.failures || []);
const slowJobs = computed(() => payload.value?.slow_jobs || []);
const throughput = computed(() => payload.value?.throughput || { labels: [], series: [], alerts: [] });

const availableSites = computed(() => payload.value?.available_sites || []);
const availableJobs = computed(() => payload.value?.available_jobs || []);

const throughputChartOptions = computed(() => ({
    chart: {
        type: 'line',
        toolbar: { show: false },
        zoom: { enabled: false },
    },
    stroke: { width: 2, curve: 'smooth' },
    xaxis: {
        categories: throughput.value.labels || [],
        labels: {
            rotate: -35,
            hideOverlappingLabels: true,
        },
    },
    yaxis: {
        min: 0,
        forceNiceScale: true,
    },
    dataLabels: { enabled: false },
    legend: { position: 'top' },
    tooltip: {
        y: {
            formatter: (value) => Number(value || 0).toLocaleString(),
        },
    },
}));

const throughputSeries = computed(() => (throughput.value.series || []).map((item) => ({
    name: item.site,
    data: item.points || [],
})));

const fetchSummary = async () => {
    loading.value = true;
    error.value = '';

    try {
        const response = await axios.get(route('admin.job-telemetry.summary'), {
            params: {
                window_minutes: Number(filters.value.window_minutes || 60),
                site: filters.value.site || undefined,
                job_name: filters.value.job_name || undefined,
                limit: Number(filters.value.limit || 30),
            },
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        payload.value = response.data || {};

        if (!filters.value.site && Array.isArray(payload.value.available_sites)) {
            filters.value.site = '';
        }
        if (!filters.value.job_name && Array.isArray(payload.value.available_jobs)) {
            filters.value.job_name = '';
        }
    } catch (requestError) {
        error.value = requestError.response?.data?.message || 'Failed to load telemetry summary.';
    } finally {
        loading.value = false;
    }
};

const resetFilters = async () => {
    filters.value.window_minutes = 60;
    filters.value.site = '';
    filters.value.job_name = '';
    filters.value.limit = 30;
    await fetchSummary();
};

onMounted(fetchSummary);
</script>

<template>
    <Head>
        <title>Job Telemetry</title>
    </Head>

    <div class="ui-container-fluid">
        <div class="u-flex u-justify-between u-items-center mb-3">
            <h2 class="mb-0">Job Telemetry</h2>
            <small class="u-text-muted">Admin-only queue analytics</small>
        </div>

        <div class="ui-card mb-3">
            <div class="ui-card-body">
                <form class="ui-row ui-g-3 u-items-end" @submit.prevent="fetchSummary">
                    <div class="ui-col-md-2">
                        <label for="telemetry_window" class="ui-form-label">Window (min)</label>
                        <select id="telemetry_window" v-model.number="filters.window_minutes" class="ui-form-select">
                            <option :value="15">15</option>
                            <option :value="60">60</option>
                            <option :value="180">180</option>
                            <option :value="360">360</option>
                            <option :value="1440">1440</option>
                        </select>
                    </div>

                    <div class="ui-col-md-3">
                        <label for="telemetry_site" class="ui-form-label">Site</label>
                        <select id="telemetry_site" v-model="filters.site" class="ui-form-select">
                            <option value="">All sites</option>
                            <option v-for="site in availableSites" :key="site" :value="site">{{ site }}</option>
                        </select>
                    </div>

                    <div class="ui-col-md-4">
                        <label for="telemetry_job" class="ui-form-label">Job</label>
                        <select id="telemetry_job" v-model="filters.job_name" class="ui-form-select">
                            <option value="">All jobs</option>
                            <option v-for="job in availableJobs" :key="job" :value="job">{{ job }}</option>
                        </select>
                    </div>

                    <div class="ui-col-md-1">
                        <label for="telemetry_limit" class="ui-form-label">Rows</label>
                        <input id="telemetry_limit" v-model.number="filters.limit" type="number" min="5" max="200" class="ui-form-control">
                    </div>

                    <div class="ui-col-md-2 u-flex">
                        <button type="submit" class="ui-btn ui-btn-primary mr-2" :disabled="loading">
                            {{ loading ? 'Loading...' : 'Apply' }}
                        </button>
                        <button type="button" class="ui-btn ui-btn-outline-secondary" :disabled="loading" @click="resetFilters">
                            Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="error" class="ui-alert ui-alert-danger">{{ error }}</div>

        <div class="ui-row ui-g-3 mb-3">
            <div class="ui-col-md-3">
                <div class="ui-card u-h-full">
                    <div class="ui-card-body">
                        <p class="u-text-muted mb-1">Completed</p>
                        <h4 class="mb-0">{{ Number(summary.total_completed || 0).toLocaleString() }}</h4>
                    </div>
                </div>
            </div>
            <div class="ui-col-md-3">
                <div class="ui-card u-h-full">
                    <div class="ui-card-body">
                        <p class="u-text-muted mb-1">Fail Rate</p>
                        <h4 class="mb-0">{{ Number(summary.fail_rate || 0).toFixed(2) }}%</h4>
                    </div>
                </div>
            </div>
            <div class="ui-col-md-3">
                <div class="ui-card u-h-full">
                    <div class="ui-card-body">
                        <p class="u-text-muted mb-1">Timeout Rate</p>
                        <h4 class="mb-0">{{ Number(summary.timeout_rate || 0).toFixed(2) }}%</h4>
                    </div>
                </div>
            </div>
            <div class="ui-col-md-3">
                <div class="ui-card u-h-full">
                    <div class="ui-card-body">
                        <p class="u-text-muted mb-1">Throughput / sec</p>
                        <h4 class="mb-0">{{ Number(summary.throughput_per_sec || 0).toFixed(3) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="ui-row ui-g-3 mb-3">
            <div class="ui-col-md-4">
                <div class="ui-card">
                    <div class="ui-card-body">
                        <p class="u-text-muted mb-1">p50</p>
                        <h5 class="mb-0">{{ summary.p50_ms ?? '--' }} ms</h5>
                    </div>
                </div>
            </div>
            <div class="ui-col-md-4">
                <div class="ui-card">
                    <div class="ui-card-body">
                        <p class="u-text-muted mb-1">p95</p>
                        <h5 class="mb-0">{{ summary.p95_ms ?? '--' }} ms</h5>
                    </div>
                </div>
            </div>
            <div class="ui-col-md-4">
                <div class="ui-card">
                    <div class="ui-card-body">
                        <p class="u-text-muted mb-1">p99</p>
                        <h5 class="mb-0">{{ summary.p99_ms ?? '--' }} ms</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="ui-card mb-3">
            <div class="ui-card-body">
                <h5 class="ui-card-title">Site Throughput</h5>
                <div v-if="throughputSeries.length === 0" class="u-text-muted">No throughput data in selected window.</div>
                <VueApexCharts
                    v-else
                    type="line"
                    height="320"
                    :options="throughputChartOptions"
                    :series="throughputSeries"
                />
            </div>
        </div>

        <div class="ui-card mb-3">
            <div class="ui-card-body">
                <h5 class="ui-card-title">Job Performance</h5>
                <div class="ui-table-responsive">
                    <table class="ui-table ui-table-sm ui-table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Job</th>
                                <th class="u-text-end">Total</th>
                                <th class="u-text-end">Fail %</th>
                                <th class="u-text-end">p50 (ms)</th>
                                <th class="u-text-end">p95 (ms)</th>
                                <th class="u-text-end">p99 (ms)</th>
                                <th class="u-text-end">Max (ms)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="jobPerformance.length === 0">
                                <td colspan="7" class="u-text-muted">No job performance data.</td>
                            </tr>
                            <tr v-for="row in jobPerformance" :key="`perf-${row.job_name}`">
                                <td>{{ row.job_name }}</td>
                                <td class="u-text-end">{{ Number(row.total || 0).toLocaleString() }}</td>
                                <td class="u-text-end">{{ Number(row.fail_rate || 0).toFixed(2) }}%</td>
                                <td class="u-text-end">{{ row.p50_ms ?? '--' }}</td>
                                <td class="u-text-end">{{ row.p95_ms ?? '--' }}</td>
                                <td class="u-text-end">{{ row.p99_ms ?? '--' }}</td>
                                <td class="u-text-end">{{ row.max_ms ?? '--' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="ui-row ui-g-3">
            <div class="ui-col-lg-6">
                <div class="ui-card">
                    <div class="ui-card-body">
                        <h5 class="ui-card-title">Latest Failures</h5>
                        <div class="ui-table-responsive">
                            <table class="ui-table ui-table-sm ui-table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Site</th>
                                        <th>Job</th>
                                        <th>Error</th>
                                        <th class="u-text-end">Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="failures.length === 0">
                                        <td colspan="5" class="u-text-muted">No failures in selected window.</td>
                                    </tr>
                                    <tr v-for="(row, index) in failures" :key="`failure-${index}`">
                                        <td>{{ row.timestamp }}</td>
                                        <td>{{ row.site || 'unknown' }}</td>
                                        <td>{{ row.job_name }}</td>
                                        <td>{{ row.error_class || row.error_message_short || 'Unknown error' }}</td>
                                        <td class="u-text-end">{{ row.duration_ms ?? '--' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ui-col-lg-6">
                <div class="ui-card">
                    <div class="ui-card-body">
                        <h5 class="ui-card-title">Slow Jobs</h5>
                        <div class="ui-table-responsive">
                            <table class="ui-table ui-table-sm ui-table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Site</th>
                                        <th>Job</th>
                                        <th>Status</th>
                                        <th class="u-text-end">Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="slowJobs.length === 0">
                                        <td colspan="5" class="u-text-muted">No slow-job data in selected window.</td>
                                    </tr>
                                    <tr v-for="(row, index) in slowJobs" :key="`slow-${index}`">
                                        <td>{{ row.timestamp }}</td>
                                        <td>{{ row.site || 'unknown' }}</td>
                                        <td>{{ row.job_name }}</td>
                                        <td class="u-capitalize">{{ row.status }}</td>
                                        <td class="u-text-end">{{ row.duration_ms ?? '--' }}</td>
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
