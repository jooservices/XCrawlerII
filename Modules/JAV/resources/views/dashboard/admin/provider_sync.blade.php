@extends('jav::layouts.dashboard')

@section('content')
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
                    <input id="daily-date" type="date" class="form-control">
                    <small class="text-muted">Used only when type is <code>daily</code>.</small>
                </div>
            </div>
        </div>
    </div>

    <div id="sync-message" class="alert d-none"></div>

    <div class="row g-3">
        @foreach(['onejav' => 'OneJav', '141jav' => '141Jav', 'ffjav' => 'FfJav'] as $providerKey => $providerLabel)
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $providerLabel }}</h5>
                        <p class="text-muted mb-3">Dispatch provider sync jobs by type.</p>
                        <div class="d-grid gap-2">
                            @foreach(['new', 'popular', 'daily', 'tags'] as $type)
                                <button
                                    type="button"
                                    class="btn btn-outline-primary sync-btn"
                                    data-provider="{{ $providerKey }}"
                                    data-type="{{ $type }}"
                                >
                                    Sync {{ ucfirst($type) }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const endpoint = "{{ route('jav.admin.provider-sync.dispatch') }}";
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const messageEl = document.getElementById('sync-message');
        const dateEl = document.getElementById('daily-date');

        const setMessage = (text, type = 'info') => {
            messageEl.className = `alert alert-${type}`;
            messageEl.textContent = text;
            messageEl.classList.remove('d-none');
        };

        const setLoading = (button, loading) => {
            if (loading) {
                button.dataset.originalText = button.textContent;
                button.disabled = true;
                button.textContent = 'Dispatching...';
                return;
            }

            button.disabled = false;
            button.textContent = button.dataset.originalText || button.textContent;
        };

        document.querySelectorAll('.sync-btn').forEach((button) => {
            button.addEventListener('click', async () => {
                const source = button.dataset.provider;
                const type = button.dataset.type;
                const date = dateEl.value;

                const payload = { source, type };
                if (type === 'daily' && date) {
                    payload.date = date;
                }

                setLoading(button, true);

                try {
                    const response = await fetch(endpoint, {
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
                    if (!response.ok) {
                        setMessage(body.message || 'Dispatch failed.', 'danger');
                        return;
                    }

                    const dateText = body.date ? ` (${body.date})` : '';
                    setMessage(
                        `Queued: ${body.source} ${body.type}${dateText}. You can monitor progress in Sync Progress.`,
                        'success'
                    );
                } catch (error) {
                    setMessage('Dispatch failed due to a network or server error.', 'danger');
                } finally {
                    setLoading(button, false);
                }
            });
        });
    });
</script>
@endpush
