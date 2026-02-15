<script setup>
import { Head } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import axios from 'axios';
import { useUIStore } from '@jav/Stores/ui';
import PageShell from '@jav/Components/UI/PageShell.vue';
import SectionHeader from '@jav/Components/UI/SectionHeader.vue';
import StatCard from '@jav/Components/UI/StatCard.vue';
import DataTableShell from '@jav/Components/UI/DataTableShell.vue';

const phase = ref('--');
const pendingJobs = ref(0);
const throughput = ref('--');
const eta = ref('--');
const updatedAt = ref('--');
const failedCount = ref(0);
const activeSync = ref(null);
const recentFailures = ref([]);
const uiStore = useUIStore();

let previousPhase = null;
let previousPendingJobs = null;
let lastCompletedSyncKey = null;

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
        const nextPhase = data.phase || '--';
        const nextPendingJobs = Number(data.pending_jobs || 0);
        const nextActiveSync = data.active_sync || null;
        const nextSyncKey = nextActiveSync
            ? [nextActiveSync.provider, nextActiveSync.type, nextActiveSync.started_at].join(':')
            : null;
        const queueDrained = previousPendingJobs !== null && previousPendingJobs > 0 && nextPendingJobs === 0;

        if (
            (previousPhase === 'processing' && nextPhase === 'completed')
            || queueDrained
        ) {
            if (
                nextSyncKey
                && nextSyncKey !== lastCompletedSyncKey
            ) {
                uiStore.showToast(`Queue completed: ${nextActiveSync.provider} ${nextActiveSync.type}.`, 'success');
                lastCompletedSyncKey = nextSyncKey;
            }
        }

        phase.value = data.phase || '--';
        pendingJobs.value = nextPendingJobs;
        throughput.value = data.throughput_per_min ? `${data.throughput_per_min}/min` : '--';
        eta.value = data.eta_human || '--';
        updatedAt.value = data.updated_at || '--';
        failedCount.value = data.failed_jobs_24h || 0;
        activeSync.value = nextActiveSync;
        recentFailures.value = data.recent_failures || [];
        previousPhase = nextPhase;
        previousPendingJobs = nextPendingJobs;
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

    <PageShell>
        <template #header>
            <SectionHeader title="Sync Progress" subtitle="Monitor active sync status and failures" />
        </template>

        <div class="u-text-muted small mb-3">Updated: {{ updatedAt }}</div>

        <div class="ui-row ui-g-3 mb-3">
            <div class="ui-col-md-3">
                <StatCard label="Current Phase" :value="phase" />
            </div>
            <div class="ui-col-md-3">
                <StatCard label="Pending Jobs" :value="pendingJobs" />
            </div>
            <div class="ui-col-md-3">
                <StatCard label="Throughput" :value="throughput" />
            </div>
            <div class="ui-col-md-3">
                <StatCard label="ETA" :value="eta" />
            </div>
        </div>

        <div class="ui-card mb-3">
            <div class="ui-card-body">
                <h5 class="ui-card-title">Active Request</h5>
                <div v-if="activeSync" class="u-text-muted">
                    <strong>Provider:</strong> {{ activeSync.provider }} |
                    <strong>Type:</strong> {{ activeSync.type }} |
                    <strong>Started:</strong> {{ activeSync.started_at }}
                </div>
                <div v-else class="u-text-muted">No active sync request.</div>
            </div>
        </div>

        <DataTableShell>
            <template #header>
                <div class="u-flex u-justify-between u-items-center u-w-full">
                    <h5 class="ui-card-title mb-0">Recent Failures</h5>
                    <span class="ui-badge u-bg-danger">{{ failedCount }} in last 24h</span>
                </div>
            </template>

            <div class="ui-table-responsive">
                <table class="ui-table ui-table-sm ui-table-striped mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Failed At</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="recentFailures.length === 0">
                            <td colspan="3" class="u-text-muted">No failures.</td>
                        </tr>
                        <tr v-for="failure in recentFailures" :key="failure.id">
                            <td>{{ failure.id }}</td>
                            <td>{{ failure.failed_at }}</td>
                            <td>{{ failure.message }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </DataTableShell>
    </PageShell>
</template>
