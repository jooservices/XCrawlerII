<script setup>
import { Head } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import axios from 'axios';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';

const phase = ref('--');
const pendingJobs = ref(0);
const throughput = ref('--');
const eta = ref('--');
const updatedAt = ref('--');
const failedCount = ref(0);
const activeSync = ref(null);
const recentFailures = ref([]);

let intervalId = null;

const poll = async () => {
    try {
        const response = await axios.get(route('jav.admin.sync-progress.data'), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        });

        const data = response.data || {};
        phase.value = data.phase || '--';
        pendingJobs.value = data.pending_jobs || 0;
        throughput.value = data.throughput_per_min ? `${data.throughput_per_min}/min` : '--';
        eta.value = data.eta_human || '--';
        updatedAt.value = data.updated_at || '--';
        failedCount.value = data.failed_jobs_24h || 0;
        activeSync.value = data.active_sync || null;
        recentFailures.value = data.recent_failures || [];
    } catch (error) {
        // Ignore transient polling errors.
    }
};

onMounted(() => {
    poll();
    intervalId = window.setInterval(poll, 5000);
});

onBeforeUnmount(() => {
    if (intervalId) {
        window.clearInterval(intervalId);
        intervalId = null;
    }
});
</script>

<template>
    <Head title="Sync Progress" />

    <DashboardLayout>
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">Sync Progress</h2>
                <small class="text-muted">Updated: {{ updatedAt }}</small>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <p class="text-muted mb-1">Current Phase</p>
                            <h4 class="mb-0 text-capitalize">{{ phase }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <p class="text-muted mb-1">Pending Jobs</p>
                            <h4 class="mb-0">{{ pendingJobs }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <p class="text-muted mb-1">Throughput</p>
                            <h4 class="mb-0">{{ throughput }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <p class="text-muted mb-1">ETA</p>
                            <h4 class="mb-0">{{ eta }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Active Request</h5>
                    <div v-if="activeSync" class="text-muted">
                        <strong>Provider:</strong> {{ activeSync.provider }} |
                        <strong>Type:</strong> {{ activeSync.type }} |
                        <strong>Started:</strong> {{ activeSync.started_at }}
                    </div>
                    <div v-else class="text-muted">No active sync request.</div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title mb-0">Recent Failures</h5>
                        <span class="badge bg-danger">{{ failedCount }} in last 24h</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Failed At</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="recentFailures.length === 0">
                                    <td colspan="3" class="text-muted">No failures.</td>
                                </tr>
                                <tr v-for="failure in recentFailures" :key="failure.id">
                                    <td>{{ failure.id }}</td>
                                    <td>{{ failure.failed_at }}</td>
                                    <td>{{ failure.message }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
