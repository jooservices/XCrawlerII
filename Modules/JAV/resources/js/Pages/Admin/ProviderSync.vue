<script setup>
import { Head } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import axios from 'axios';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import { useUIStore } from '@core/Stores/ui';
import PageShell from '@core/Components/UI/PageShell.vue';
import SectionHeader from '@core/Components/UI/SectionHeader.vue';
import StatCard from '@jav/Components/UI/StatCard.vue';
import '@vuepic/vue-datepicker/dist/main.css';

const dailyDate = ref(null);
const message = ref('');
const messageType = ref('info');
const loadingKey = ref(null);
const statusLoading = ref(false);
const uiStore = useUIStore();

const progress = ref(null);
const providerStatus = ref({
    onejav: { new: 0, popular: 0 },
    '141jav': { new: 0, popular: 0 },
    ffjav: { new: 0, popular: 0 },
});

const providers = [
    { key: 'onejav', label: 'OneJav', types: ['new', 'popular', 'daily', 'tags'] },
    { key: '141jav', label: '141Jav', types: ['new', 'popular', 'daily', 'tags'] },
    { key: 'ffjav', label: 'FfJav', types: ['new', 'popular', 'daily', 'tags'] },
    { key: 'xcity', label: 'XCity', types: ['idols'] },
];

let intervalId = null;
let previousPhase = null;
let previousPendingJobs = null;
let lastCompletedSyncKey = null;

const setMessage = (text, type = 'info') => {
    message.value = text;
    messageType.value = type;
};

const loadStatus = async () => {
    statusLoading.value = true;

    try {
        const response = await axios.get(route('jav.admin.provider-sync.status'), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = response.data || {};
        const nextProgress = data.progress || null;
        const nextPhase = nextProgress?.phase || null;
        const nextPendingJobs = Number(nextProgress?.pending_jobs || 0);
        const nextActiveSync = nextProgress?.active_sync || null;
        const nextSyncKey = nextActiveSync
            ? [nextActiveSync.provider, nextActiveSync.type, nextActiveSync.started_at].join(':')
            : null;
        const queueDrained = previousPendingJobs !== null && previousPendingJobs > 0 && nextPendingJobs === 0;

        if (
            ((previousPhase === 'processing' && nextPhase === 'completed') || queueDrained)
            && nextSyncKey
            && nextSyncKey !== lastCompletedSyncKey
        ) {
            uiStore.showToast(`Queue completed: ${nextActiveSync.provider} ${nextActiveSync.type}.`, 'success');
            lastCompletedSyncKey = nextSyncKey;
        }

        providerStatus.value = {
            onejav: data.onejav || { new: 0, popular: 0 },
            '141jav': data['141jav'] || { new: 0, popular: 0 },
            ffjav: data.ffjav || { new: 0, popular: 0 },
        };
        progress.value = nextProgress;
        previousPhase = nextPhase;
        previousPendingJobs = nextPendingJobs;
    } catch (error) {
        setMessage(error.response?.data?.message || 'Failed to load sync status.', 'danger');
    } finally {
        statusLoading.value = false;
    }
};

const dispatchSync = async (source, type) => {
    const syncKey = `${source}:${type}`;
    loadingKey.value = syncKey;

    const payload = { source, type };
    if (type === 'daily' && dailyDate.value) {
        const year = dailyDate.value.getFullYear();
        const month = String(dailyDate.value.getMonth() + 1).padStart(2, '0');
        const day = String(dailyDate.value.getDate()).padStart(2, '0');
        payload.date = `${year}-${month}-${day}`;
    }

    try {
        const response = await axios.post(route('jav.admin.provider-sync.dispatch'), payload, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const body = response.data || {};
        const dateText = body.date ? ` (${body.date})` : '';
        setMessage(
            `Queued: ${body.source} ${body.type}${dateText}.`,
            'success'
        );

        await loadStatus();
    } catch (error) {
        setMessage(error.response?.data?.message || 'Dispatch failed due to a network or server error.', 'danger');
    } finally {
        loadingKey.value = null;
    }
};

onMounted(() => {
    loadStatus();
    intervalId = window.setInterval(loadStatus, 10000);
});

onBeforeUnmount(() => {
    if (intervalId) {
        window.clearInterval(intervalId);
        intervalId = null;
    }
});
</script>

<template>
    <Head title="Provider Sync" />

    <PageShell>
        <template #header>
            <SectionHeader title="Provider Sync" subtitle="Dispatch and monitor provider jobs" />
        </template>

        <template #actions>
            <button type="button" class="ui-btn ui-btn-outline-secondary ui-btn-sm" :disabled="statusLoading" @click="loadStatus">
                <i class="fas fa-rotate mr-1"></i>Refresh
            </button>
        </template>

        <div class="ui-card mb-3">
            <div class="ui-card-body">
                <div class="ui-row ui-g-3 u-items-end">
                    <div class="ui-col-md-4">
                        <label for="daily-date" class="ui-form-label">Daily Sync Date (optional)</label>
                        <VueDatePicker
                            id="daily-date"
                            v-model="dailyDate"
                            :enable-time-picker="false"
                            auto-apply
                            format="yyyy-MM-dd"
                            model-type="Date"
                            placeholder="Select date"
                        />
                        <small class="u-text-muted">Used only when type is <code>daily</code>.</small>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="message" class="ui-alert" :class="`ui-alert-${messageType}`">{{ message }}</div>

        <div class="ui-row ui-g-3 mb-3">
            <div class="ui-col-md-4" v-for="provider in ['onejav', '141jav', 'ffjav']" :key="provider">
                <StatCard :label="provider.toUpperCase()" :value="`new ${providerStatus[provider]?.new ?? 0}`">
                    <div class="u-text-muted small mt-1">
                        <p class="mb-1"><strong>new page:</strong> {{ providerStatus[provider]?.new ?? 0 }}</p>
                        <p class="mb-0"><strong>popular page:</strong> {{ providerStatus[provider]?.popular ?? 0 }}</p>
                    </div>
                </StatCard>
            </div>
        </div>

        <div class="mb-3">
            <h5 class="ui-card-title mb-2">Progress Snapshot</h5>
            <div v-if="progress" class="ui-row ui-g-3">
                <div class="ui-col-md-3">
                    <StatCard label="Phase" :value="progress.phase || '--'" />
                </div>
                <div class="ui-col-md-3">
                    <StatCard label="Pending Jobs" :value="progress.pending_jobs || 0" />
                </div>
                <div class="ui-col-md-3">
                    <StatCard label="Failed (24h)" :value="progress.failed_jobs_24h || 0" />
                </div>
                <div class="ui-col-md-3">
                    <StatCard label="ETA" :value="progress.eta_human || '--'" />
                </div>
            </div>
            <div v-else class="u-text-muted">No progress data yet.</div>
        </div>

        <div class="ui-row ui-g-3">
            <div v-for="provider in providers" :key="provider.key" class="ui-col-lg-4">
                <div class="ui-card u-h-full">
                    <div class="ui-card-body">
                        <h5 class="ui-card-title">{{ provider.label }}</h5>
                        <p class="u-text-muted mb-3">Dispatch provider sync jobs by type.</p>
                        <div class="u-grid gap-2">
                            <button
                                v-for="type in provider.types"
                                :key="`${provider.key}:${type}`"
                                type="button"
                                class="ui-btn ui-btn-outline-primary"
                                :disabled="loadingKey === `${provider.key}:${type}`"
                                @click="dispatchSync(provider.key, type)"
                            >
                                {{ loadingKey === `${provider.key}:${type}` ? 'Dispatching...' : `Sync ${type.charAt(0).toUpperCase()}${type.slice(1)}` }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </PageShell>
</template>
