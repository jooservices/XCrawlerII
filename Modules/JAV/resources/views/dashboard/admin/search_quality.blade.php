@extends('jav::layouts.dashboard')

@section('content')
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
                    <select id="entity-type" class="form-select">
                        <option value="jav">Video (JAV)</option>
                        <option value="actor">Actor</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="identifier-mode" class="form-label">Identifier Mode</label>
                    <select id="identifier-mode" class="form-select">
                        <option value="auto">Auto</option>
                        <option value="id">ID</option>
                        <option value="uuid">UUID</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="identifier" class="form-label">Identifier</label>
                    <input id="identifier" type="text" class="form-control" placeholder="ID or UUID">
                </div>
            </div>

            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="reindex-related">
                <label class="form-check-label" for="reindex-related">
                    Reindex related records on publish
                </label>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button id="preview-btn" class="btn btn-outline-primary">Preview Document</button>
                <button id="publish-btn" class="btn btn-primary">Publish to Search Index</button>
            </div>
        </div>
    </div>

    <div class="alert alert-info d-none" id="message-box"></div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Quality</h5>
                    <p class="mb-1"><strong>Status:</strong> <span id="quality-status">--</span></p>
                    <p class="mb-3"><strong>Score:</strong> <span id="quality-score">--</span></p>
                    <ul id="quality-warnings" class="mb-0 text-danger"></ul>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Search Payload Preview</h5>
                    <pre id="payload-preview" class="bg-light p-3 border rounded mb-0" style="max-height: 420px; overflow: auto;">{}</pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const previewEndpoint = "{{ route('jav.admin.search-quality.preview') }}";
        const publishEndpoint = "{{ route('jav.admin.search-quality.publish') }}";
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const entityTypeEl = document.getElementById('entity-type');
        const identifierModeEl = document.getElementById('identifier-mode');
        const identifierEl = document.getElementById('identifier');
        const reindexRelatedEl = document.getElementById('reindex-related');
        const previewBtn = document.getElementById('preview-btn');
        const publishBtn = document.getElementById('publish-btn');
        const messageBox = document.getElementById('message-box');
        const qualityStatusEl = document.getElementById('quality-status');
        const qualityScoreEl = document.getElementById('quality-score');
        const qualityWarningsEl = document.getElementById('quality-warnings');
        const payloadPreviewEl = document.getElementById('payload-preview');

        const currentPayload = {
            entity_type: null,
            identifier: null,
            identifier_mode: null,
        };

        const showMessage = (message, type = 'info') => {
            messageBox.className = `alert alert-${type}`;
            messageBox.textContent = message;
            messageBox.classList.remove('d-none');
        };

        const readPayload = () => ({
            entity_type: entityTypeEl.value,
            identifier: identifierEl.value.trim(),
            identifier_mode: identifierModeEl.value,
            reindex_related: reindexRelatedEl.checked,
        });

        const setQuality = (quality) => {
            qualityStatusEl.textContent = quality?.status ?? '--';
            qualityScoreEl.textContent = quality?.score ?? '--';
            qualityWarningsEl.innerHTML = '';

            const warnings = quality?.warnings ?? [];
            if (warnings.length === 0) {
                const item = document.createElement('li');
                item.className = 'text-success';
                item.textContent = 'No warnings.';
                qualityWarningsEl.appendChild(item);
                return;
            }

            warnings.forEach((warning) => {
                const item = document.createElement('li');
                item.textContent = warning;
                qualityWarningsEl.appendChild(item);
            });
        };

        const requestJson = async (url, payload) => {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const body = await response.json().catch(() => ({}));
            return { ok: response.ok, body };
        };

        previewBtn.addEventListener('click', async () => {
            const payload = readPayload();
            if (!payload.identifier) {
                showMessage('Identifier is required.', 'warning');
                return;
            }

            const { ok, body } = await requestJson(previewEndpoint, payload);
            if (!ok) {
                showMessage(body.message ?? 'Preview failed.', 'danger');
                return;
            }

            currentPayload.entity_type = payload.entity_type;
            currentPayload.identifier = payload.identifier;
            currentPayload.identifier_mode = payload.identifier_mode;

            payloadPreviewEl.textContent = JSON.stringify(body.payload ?? {}, null, 2);
            setQuality(body.quality ?? null);

            const warningCount = body.quality?.warnings?.length ?? 0;
            const message = warningCount > 0
                ? `Preview generated with ${warningCount} warning(s).`
                : 'Preview generated with no warnings.';
            showMessage(message, warningCount > 0 ? 'warning' : 'success');
        });

        publishBtn.addEventListener('click', async () => {
            const payload = readPayload();
            if (!payload.identifier) {
                showMessage('Identifier is required.', 'warning');
                return;
            }

            if (
                currentPayload.entity_type !== payload.entity_type
                || currentPayload.identifier !== payload.identifier
                || currentPayload.identifier_mode !== payload.identifier_mode
            ) {
                showMessage('Run Preview first for the same record before publishing.', 'warning');
                return;
            }

            const { ok, body } = await requestJson(publishEndpoint, payload);
            if (!ok) {
                showMessage(body.message ?? 'Publish failed.', 'danger');
                return;
            }

            showMessage(
                `${body.message ?? 'Published.'} Reindexed ${body.reindexed_count ?? 0} record(s).`,
                'success'
            );
        });
    });
</script>
@endpush
