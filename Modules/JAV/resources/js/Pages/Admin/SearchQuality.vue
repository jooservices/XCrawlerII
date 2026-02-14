<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';

const entityType = ref('jav');
const identifierMode = ref('auto');
const identifier = ref('');
const reindexRelated = ref(false);

const message = ref('');
const messageType = ref('info');
const qualityStatus = ref('--');
const qualityScore = ref('--');
const qualityWarnings = ref([]);
const payloadPreview = ref('{}');

const currentPayload = ref({
    entity_type: null,
    identifier: null,
    identifier_mode: null,
});

const showMessage = (text, type = 'info') => {
    message.value = text;
    messageType.value = type;
};

const readPayload = () => ({
    entity_type: entityType.value,
    identifier: identifier.value.trim(),
    identifier_mode: identifierMode.value,
    reindex_related: reindexRelated.value,
});

const setQuality = (quality) => {
    qualityStatus.value = quality?.status ?? '--';
    qualityScore.value = quality?.score ?? '--';
    qualityWarnings.value = quality?.warnings || [];
};

const requestJson = async (url, payload) => {
    const response = await axios.post(url, payload, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    return response.data;
};

const preview = async () => {
    const payload = readPayload();
    if (!payload.identifier) {
        showMessage('Identifier is required.', 'warning');
        return;
    }

    try {
        const body = await requestJson(route('jav.admin.search-quality.preview'), payload);

        currentPayload.value = {
            entity_type: payload.entity_type,
            identifier: payload.identifier,
            identifier_mode: payload.identifier_mode,
        };

        payloadPreview.value = JSON.stringify(body.payload || {}, null, 2);
        setQuality(body.quality || null);

        const warningCount = (body.quality?.warnings || []).length;
        showMessage(
            warningCount > 0 ? `Preview generated with ${warningCount} warning(s).` : 'Preview generated with no warnings.',
            warningCount > 0 ? 'warning' : 'success'
        );
    } catch (error) {
        showMessage(error.response?.data?.message || 'Preview failed.', 'danger');
    }
};

const publish = async () => {
    const payload = readPayload();
    if (!payload.identifier) {
        showMessage('Identifier is required.', 'warning');
        return;
    }

    if (
        currentPayload.value.entity_type !== payload.entity_type
        || currentPayload.value.identifier !== payload.identifier
        || currentPayload.value.identifier_mode !== payload.identifier_mode
    ) {
        showMessage('Run Preview first for the same record before publishing.', 'warning');
        return;
    }

    try {
        const body = await requestJson(route('jav.admin.search-quality.publish'), payload);
        showMessage(
            `${body.message || 'Published.'} Reindexed ${body.reindexed_count || 0} record(s).`,
            'success'
        );
    } catch (error) {
        showMessage(error.response?.data?.message || 'Publish failed.', 'danger');
    }
};
</script>

<template>
    <Head title="Search Quality Controls" />

    <DashboardLayout>
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">Search Quality Controls</h2>
                <small class="text-muted">Admin only</small>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="entity-type" class="form-label">Entity Type</label>
                            <select id="entity-type" v-model="entityType" class="form-select">
                                <option value="jav">Video (JAV)</option>
                                <option value="actor">Actor</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="identifier-mode" class="form-label">Identifier Mode</label>
                            <select id="identifier-mode" v-model="identifierMode" class="form-select">
                                <option value="auto">Auto</option>
                                <option value="id">ID</option>
                                <option value="uuid">UUID</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="identifier" class="form-label">Identifier</label>
                            <input id="identifier" v-model="identifier" type="text" class="form-control" placeholder="ID or UUID">
                        </div>
                    </div>

                    <div class="form-check mt-3">
                        <input id="reindex-related" v-model="reindexRelated" class="form-check-input" type="checkbox">
                        <label class="form-check-label" for="reindex-related">Reindex related records on publish</label>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="button" class="btn btn-outline-primary" @click="preview">Preview Document</button>
                        <button type="button" class="btn btn-primary" @click="publish">Publish to Search Index</button>
                    </div>
                </div>
            </div>

            <div v-if="message" class="alert" :class="`alert-${messageType}`">{{ message }}</div>

            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Quality</h5>
                            <p class="mb-1"><strong>Status:</strong> <span>{{ qualityStatus }}</span></p>
                            <p class="mb-3"><strong>Score:</strong> <span>{{ qualityScore }}</span></p>
                            <ul class="mb-0 text-danger">
                                <li v-if="qualityWarnings.length === 0" class="text-success">No warnings.</li>
                                <li v-for="warning in qualityWarnings" :key="warning">{{ warning }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Search Payload Preview</h5>
                            <pre class="bg-light p-3 border rounded mb-0" style="max-height: 420px; overflow: auto;">{{ payloadPreview }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
