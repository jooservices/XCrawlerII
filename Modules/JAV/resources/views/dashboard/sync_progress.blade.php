@extends('jav::layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Sync Progress</h2>
        <small class="text-muted" id="updated-at">Updated: --</small>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Current Phase</p>
                    <h4 class="mb-0 text-capitalize" id="phase">--</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Pending Jobs</p>
                    <h4 class="mb-0" id="pending-jobs">0</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Throughput</p>
                    <h4 class="mb-0" id="throughput">--</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">ETA</p>
                    <h4 class="mb-0" id="eta">--</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">Active Request</h5>
            <div id="active-sync" class="text-muted">No active sync request.</div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="card-title mb-0">Recent Failures</h5>
                <span class="badge bg-danger" id="failed-count">0 in last 24h</span>
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
                    <tbody id="failures-body">
                        <tr>
                            <td colspan="3" class="text-muted">No failures.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const endpoint = "{{ route('jav.admin.sync-progress.data') }}";

        const phaseEl = document.getElementById('phase');
        const pendingEl = document.getElementById('pending-jobs');
        const throughputEl = document.getElementById('throughput');
        const etaEl = document.getElementById('eta');
        const updatedEl = document.getElementById('updated-at');
        const activeSyncEl = document.getElementById('active-sync');
        const failedCountEl = document.getElementById('failed-count');
        const failuresBodyEl = document.getElementById('failures-body');

        const renderFailures = (failures) => {
            if (!failures || failures.length === 0) {
                failuresBodyEl.innerHTML = '<tr><td colspan="3" class="text-muted">No failures.</td></tr>';
                return;
            }

            failuresBodyEl.innerHTML = failures.map(failure => `
                <tr>
                    <td>${failure.id}</td>
                    <td>${failure.failed_at}</td>
                    <td>${failure.message}</td>
                </tr>
            `).join('');
        };

        const renderActiveSync = (activeSync) => {
            if (!activeSync) {
                activeSyncEl.textContent = 'No active sync request.';
                return;
            }

            activeSyncEl.innerHTML = `
                <strong>Provider:</strong> ${activeSync.provider} |
                <strong>Type:</strong> ${activeSync.type} |
                <strong>Started:</strong> ${activeSync.started_at}
            `;
        };

        const poll = async () => {
            try {
                const response = await fetch(endpoint, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                phaseEl.textContent = data.phase ?? '--';
                pendingEl.textContent = data.pending_jobs ?? 0;
                throughputEl.textContent = data.throughput_per_min ? `${data.throughput_per_min}/min` : '--';
                etaEl.textContent = data.eta_human ?? '--';
                updatedEl.textContent = `Updated: ${data.updated_at ?? '--'}`;
                failedCountEl.textContent = `${data.failed_jobs_24h ?? 0} in last 24h`;

                renderActiveSync(data.active_sync);
                renderFailures(data.recent_failures ?? []);
            } catch (error) {
                // Ignore transient network errors for polling.
            }
        };

        poll();
        setInterval(poll, 5000);
    });
</script>
@endpush
