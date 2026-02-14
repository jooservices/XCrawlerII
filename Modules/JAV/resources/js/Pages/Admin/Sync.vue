<script setup>
import { Head } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import axios from 'axios';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';
import { useUIStore } from '@jav/Stores/ui';

const uiStore = useUIStore();

const source = ref('onejav');
const type = ref('new');
const loading = ref(false);
const statusLoading = ref(false);

const progress = ref(null);
const providerStatus = ref({
    onejav: { new: 0, popular: 0 },
    '141jav': { new: 0, popular: 0 },
    ffjav: { new: 0, popular: 0 },
});

let intervalId = null;

const loadStatus = async () => {
    statusLoading.value = true;
    try {
        const response = await axios.get(route('jav.status'), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = response.data || {};
        providerStatus.value = {
            onejav: data.onejav || { new: 0, popular: 0 },
            '141jav': data['141jav'] || { new: 0, popular: 0 },
            ffjav: data.ffjav || { new: 0, popular: 0 },
        };
        progress.value = data.progress || null;
    } catch (error) {
        uiStore.showToast('Failed to load sync status', 'error');
    } finally {
        statusLoading.value = false;
    }
};

const submitRequest = async () => {
    loading.value = true;
    try {
        const response = await axios.post(route('jav.request'), {
            source: source.value,
            type: type.value,
        }, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        progress.value = response.data?.progress || null;
        uiStore.showToast(response.data?.message || 'Sync request queued successfully.', 'success');
        await loadStatus();
    } catch (error) {
        uiStore.showToast(error.response?.data?.message || 'Failed to queue sync request', 'error');
    } finally {
        loading.value = false;
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
    <Head title="Quick Sync" />

    <DashboardLayout>
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">Quick Sync</h2>
                <button type="button" class="btn btn-outline-secondary btn-sm" :disabled="statusLoading" @click="loadStatus">
                    <i class="fas fa-rotate me-1"></i>Refresh
                </button>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Request Sync</h5>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Source</label>
                            <select v-model="source" class="form-select">
                                <option value="onejav">onejav</option>
                                <option value="141jav">141jav</option>
                                <option value="ffjav">ffjav</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select v-model="type" class="form-select">
                                <option value="new">new</option>
                                <option value="popular">popular</option>
                                <option value="daily">daily</option>
                                <option value="tags">tags</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-grid">
                            <button type="button" class="btn btn-primary" :disabled="loading" @click="submitRequest">
                                <i class="fas fa-play me-1"></i>{{ loading ? 'Queueing...' : 'Queue Sync' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4" v-for="provider in ['onejav', '141jav', 'ffjav']" :key="provider">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-uppercase">{{ provider }}</h6>
                            <p class="mb-1"><strong>new page:</strong> {{ providerStatus[provider]?.new ?? 0 }}</p>
                            <p class="mb-0"><strong>popular page:</strong> {{ providerStatus[provider]?.popular ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Progress Snapshot</h5>
                    <div v-if="progress" class="row g-3">
                        <div class="col-md-3">
                            <p class="text-muted mb-1">Phase</p>
                            <h5 class="mb-0 text-capitalize">{{ progress.phase || '--' }}</h5>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted mb-1">Pending Jobs</p>
                            <h5 class="mb-0">{{ progress.pending_jobs || 0 }}</h5>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted mb-1">Failed (24h)</p>
                            <h5 class="mb-0">{{ progress.failed_jobs_24h || 0 }}</h5>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted mb-1">ETA</p>
                            <h5 class="mb-0">{{ progress.eta_human || '--' }}</h5>
                        </div>
                    </div>
                    <div v-else class="text-muted">No progress data yet.</div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
