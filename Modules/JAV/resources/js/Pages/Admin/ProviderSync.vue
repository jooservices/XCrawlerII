<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';

const dailyDate = ref('');
const message = ref('');
const messageType = ref('info');
const loadingKey = ref(null);

const providers = [
    { key: 'onejav', label: 'OneJav' },
    { key: '141jav', label: '141Jav' },
    { key: 'ffjav', label: 'FfJav' },
];

const types = ['new', 'popular', 'daily', 'tags'];

const setMessage = (text, type = 'info') => {
    message.value = text;
    messageType.value = type;
};

const dispatchSync = async (source, type) => {
    const syncKey = `${source}:${type}`;
    loadingKey.value = syncKey;

    const payload = { source, type };
    if (type === 'daily' && dailyDate.value) {
        payload.date = dailyDate.value;
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
            `Queued: ${body.source} ${body.type}${dateText}. You can monitor progress in Sync Progress.`,
            'success'
        );
    } catch (error) {
        setMessage(error.response?.data?.message || 'Dispatch failed due to a network or server error.', 'danger');
    } finally {
        loadingKey.value = null;
    }
};
</script>

<template>
    <Head title="Provider Sync" />

    <DashboardLayout>
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">Provider Sync</h2>
                <small class="text-muted">Admin only</small>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="daily-date" class="form-label">Daily Sync Date (optional)</label>
                            <input id="daily-date" v-model="dailyDate" type="date" class="form-control">
                            <small class="text-muted">Used only when type is <code>daily</code>.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="message" class="alert" :class="`alert-${messageType}`">{{ message }}</div>

            <div class="row g-3">
                <div v-for="provider in providers" :key="provider.key" class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">{{ provider.label }}</h5>
                            <p class="text-muted mb-3">Dispatch provider sync jobs by type.</p>
                            <div class="d-grid gap-2">
                                <button
                                    v-for="type in types"
                                    :key="`${provider.key}:${type}`"
                                    type="button"
                                    class="btn btn-outline-primary"
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
        </div>
    </DashboardLayout>
</template>
