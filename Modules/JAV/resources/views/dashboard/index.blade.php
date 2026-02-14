@extends('jav::layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2>Movies</h2>
            @if(!empty($filters['actor']))
                <span class="badge bg-primary fs-6">Actor: {{ $filters['actor'] }} <a href="{{ route('jav.blade.dashboard') }}"
                        class="text-white ms-2"><i class="fas fa-times"></i></a></span>
            @endif
            @if(!empty($filters['tags']))
                <span class="badge bg-info fs-6">Tags: {{ implode(', ', $filters['tags']) }} <a href="{{ route('jav.blade.dashboard') }}"
                        class="text-white ms-2"><i class="fas fa-times"></i></a></span>
            @endif
            @if(!empty($filters['age']))
                <span class="badge bg-secondary fs-6">Age: {{ $filters['age'] }}</span>
            @elseif(!empty($filters['age_min']) || !empty($filters['age_max']))
                <span class="badge bg-secondary fs-6">Age Range: {{ $filters['age_min'] ?? 'Any' }} - {{ $filters['age_max'] ?? 'Any' }}</span>
            @endif
            @foreach(($filters['bio_filters'] ?? []) as $bioFilter)
                @if(!empty($bioFilter['key']) || !empty($bioFilter['value']))
                    <span class="badge bg-dark fs-6">
                        Bio: {{ $bioFilter['key'] ?? 'Any' }} = {{ $bioFilter['value'] ?? 'Any' }}
                    </span>
                @endif
            @endforeach
        </div>
        <div class="col-md-4 text-md-end">
            @auth
                <button class="btn btn-outline-success btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#savePresetBox">
                    <i class="fas fa-save me-1"></i>Save Current As Preset
                </button>
            @endauth
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @auth
    <div class="collapse mb-3" id="savePresetBox">
        <div class="card card-body">
            <form method="POST" action="{{ route('jav.presets.save') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Preset Name</label>
                    <input type="text" name="name" class="form-control" maxlength="60" required>
                </div>
                <input type="hidden" name="q" value="{{ $query }}">
                <input type="hidden" name="actor" value="{{ $filters['actor'] ?? '' }}">
                <input type="hidden" name="tag" value="{{ $tagsInput }}">
                @foreach($filters['tags'] ?? [] as $selectedTag)
                    <input type="hidden" name="tags[]" value="{{ $selectedTag }}">
                @endforeach
                <input type="hidden" name="tags_mode" value="{{ $filters['tags_mode'] ?? 'any' }}">
                <input type="hidden" name="age" value="{{ $filters['age'] ?? '' }}">
                <input type="hidden" name="age_min" value="{{ $filters['age_min'] ?? '' }}">
                <input type="hidden" name="age_max" value="{{ $filters['age_max'] ?? '' }}">
                <input type="hidden" name="bio_key" value="{{ $filters['bio_key'] ?? '' }}">
                <input type="hidden" name="bio_value" value="{{ $filters['bio_value'] ?? '' }}">
                @foreach(($filters['bio_filters'] ?? []) as $bioIndex => $bioFilter)
                    <input type="hidden" name="bio_filters[{{ $bioIndex }}][key]" value="{{ $bioFilter['key'] ?? '' }}">
                    <input type="hidden" name="bio_filters[{{ $bioIndex }}][value]" value="{{ $bioFilter['value'] ?? '' }}">
                @endforeach
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <input type="hidden" name="preset" value="{{ $preset }}">
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" type="submit">Save Preset</button>
                </div>
            </form>
        </div>
    </div>
    @endauth

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-2">
                @foreach($builtInPresets as $presetKey => $presetLabel)
                    <a href="{{ route('jav.blade.dashboard', array_filter(['preset' => $presetKey, 'q' => $query])) }}"
                        class="btn btn-sm {{ $preset === $presetKey && $savedPresetIndex === null ? 'btn-primary' : 'btn-outline-primary' }}">
                        {{ $presetLabel }}
                    </a>
                @endforeach
            </div>

            @if(!empty($savedPresets))
                <div class="d-flex flex-wrap gap-2">
                    @foreach($savedPresets as $index => $saved)
                        <a href="{{ route('jav.blade.dashboard', ['saved_preset' => $index]) }}"
                            class="btn btn-sm {{ $savedPresetIndex === $index ? 'btn-success' : 'btn-outline-success' }}">
                            {{ $saved['name'] ?? ('Preset ' . ($index + 1)) }}
                        </a>
                        <form method="POST" action="{{ route('jav.presets.delete', $index) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete preset">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form id="advancedSearchForm" action="{{ route('jav.blade.dashboard') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Keyword</label>
                    <input type="text" name="q" class="form-control" value="{{ $query }}" placeholder="Code, title, description">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Actor</label>
                    <input type="text" name="actor" list="actor-suggestions" class="form-control" value="{{ $filters['actor'] ?? '' }}" placeholder="Name or names, comma-separated">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tags (multi)</label>
                    <input type="text" name="tag" list="tag-suggestions" class="form-control" value="{{ $tagsInput }}" placeholder="Tag A, Tag B, Tag C">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tags Mode</label>
                    <select name="tags_mode" class="form-select">
                        <option value="any" {{ ($filters['tags_mode'] ?? 'any') === 'any' ? 'selected' : '' }}>Match Any</option>
                        <option value="all" {{ ($filters['tags_mode'] ?? 'any') === 'all' ? 'selected' : '' }}>Match All</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Exact Age</label>
                    <input type="number" min="18" max="99" name="age" class="form-control" value="{{ $filters['age'] ?? '' }}" placeholder="e.g. 25">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Age Min</label>
                    <input type="number" min="18" max="99" name="age_min" class="form-control" value="{{ $filters['age_min'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Age Max</label>
                    <input type="number" min="18" max="99" name="age_max" class="form-control" value="{{ $filters['age_max'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bio Filters</label>
                    <button type="button" class="btn btn-sm btn-outline-primary w-100" id="addBioFilterBtn">
                        <i class="fas fa-plus me-1"></i>Add Bio Filter
                    </button>
                </div>
                <div class="col-md-12">
                    <div id="bioFilterContainer">
                        @php
                            $renderBioFilters = !empty($filters['bio_filters']) ? $filters['bio_filters'] : [['key' => '', 'value' => '']];
                        @endphp
                        @foreach($renderBioFilters as $bioIndex => $bioFilter)
                            <div class="row g-2 align-items-end bio-filter-row mb-2" data-index="{{ $bioIndex }}">
                                <div class="col-md-4">
                                    <label class="form-label mb-1">Bio Key</label>
                                    <input
                                        type="text"
                                        class="form-control bio-key-input"
                                        name="bio_filters[{{ $bioIndex }}][key]"
                                        list="bio-keys"
                                        value="{{ $bioFilter['key'] ?? '' }}"
                                        placeholder="e.g. blood_type"
                                    >
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label mb-1">Bio Value</label>
                                    @php
                                        $currentBioKey = (string) ($bioFilter['key'] ?? '');
                                        $currentValueList = 'bio-values-all';
                                        if ($currentBioKey !== '' && array_key_exists($currentBioKey, $bioValueSuggestions ?? [])) {
                                            $currentValueList = 'bio-values-' . $currentBioKey;
                                        }
                                    @endphp
                                    <input
                                        type="text"
                                        class="form-control bio-value-input"
                                        name="bio_filters[{{ $bioIndex }}][value]"
                                        list="{{ $currentValueList }}"
                                        value="{{ $bioFilter['value'] ?? '' }}"
                                        placeholder="e.g. A, Tokyo"
                                    >
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger w-100 remove-bio-filter-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <input type="hidden" name="sort" value="{{ $sort }}" id="advancedSortInput">
                <input type="hidden" name="direction" value="{{ $direction }}" id="advancedDirectionInput">
                <input type="hidden" name="preset" value="{{ $preset }}" id="advancedPresetInput">
                <input type="hidden" name="bio_key" value="{{ $filters['bio_key'] ?? '' }}">
                <input type="hidden" name="bio_value" value="{{ $filters['bio_value'] ?? '' }}">
                @if($savedPresetIndex !== null)
                    <input type="hidden" name="saved_preset" value="{{ $savedPresetIndex }}">
                @endif

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Apply</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('jav.blade.dashboard') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>

            <datalist id="actor-suggestions">
                @foreach(($actorSuggestions ?? []) as $actorName)
                    <option value="{{ $actorName }}"></option>
                @endforeach
            </datalist>

            <datalist id="tag-suggestions">
                @foreach(($tagSuggestions ?? []) as $tagName)
                    <option value="{{ $tagName }}"></option>
                @endforeach
            </datalist>

            <datalist id="bio-keys">
                @foreach($availableBioKeys as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </datalist>

            <datalist id="bio-values-all">
                @foreach(($bioValueSuggestions ?? []) as $valueList)
                    @foreach($valueList as $value)
                        <option value="{{ $value }}"></option>
                    @endforeach
                @endforeach
            </datalist>

            @foreach(($bioValueSuggestions ?? []) as $bioKey => $valueList)
                <datalist id="bio-values-{{ $bioKey }}">
                    @foreach($valueList as $value)
                        <option value="{{ $value }}"></option>
                    @endforeach
                </datalist>
            @endforeach
        </div>
    </div>

    @auth
    @if($continueWatching->isNotEmpty())
        <div class="mb-4">
            <h5 class="mb-3">Continue Watching</h5>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-3">
                @foreach($continueWatching as $record)
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body">
                                <a href="{{ route('jav.blade.movies.show', $record->jav) }}" class="text-decoration-none">
                                    <h6 class="mb-1">{{ $record->jav->formatted_code }}</h6>
                                    <div class="text-muted small">{{ Str::limit($record->jav->title, 55) }}</div>
                                </a>
                                <div class="mt-2">
                                    <span class="badge {{ $record->action === 'download' ? 'bg-success' : 'bg-info' }}">
                                        {{ ucfirst($record->action) }}
                                    </span>
                                    <small class="text-muted ms-2">Last activity: {{ $record->updated_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    @endauth

    <div class="row mb-3 justify-content-end">
        <div class="col-auto">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    Sort By: {{ ucfirst($sort ?? 'date') }}
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item sort-option" href="#" data-sort="created_at" data-direction="desc">Date (Newest)</a></li>
                    <li><a class="dropdown-item sort-option" href="#" data-sort="created_at" data-direction="asc">Date (Oldest)</a></li>
                    <li><a class="dropdown-item sort-option" href="#" data-sort="views" data-direction="desc">Most Viewed</a></li>
                    <li><a class="dropdown-item sort-option" href="#" data-sort="views" data-direction="asc">Least Viewed</a></li>
                    <li><a class="dropdown-item sort-option" href="#" data-sort="downloads" data-direction="desc">Most Downloaded</a></li>
                    <li><a class="dropdown-item sort-option" href="#" data-sort="downloads" data-direction="asc">Least Downloaded</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4" id="lazy-container">
        @forelse($items as $item)
            @include('jav::dashboard.partials.movie_card', ['item' => $item, 'preferences' => $preferences])
        @empty
            <div class="col-12">
                <div class="alert alert-warning text-center">
                    No movies found.
                </div>
            </div>
        @endforelse
    </div>

    <div id="sentinel" data-next-url="{{ $items->withQueryString()->nextPageUrl() }}"></div>
    <div id="loading-spinner" class="text-center my-4" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('lazy-container');
        if (!container) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const requestJson = async (url, method = 'GET', body = null) => {
            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body ? JSON.stringify(body) : null,
            });

            const data = await response.json().catch(() => ({}));
            return { ok: response.ok, data };
        };

        document.querySelectorAll('.sort-option').forEach(item => {
            item.addEventListener('click', event => {
                event.preventDefault();
                const form = document.getElementById('advancedSearchForm') || document.getElementById('searchForm');
                if (!form) {
                    return;
                }

                const sortInput = form.querySelector('input[name="sort"]');
                const directionInput = form.querySelector('input[name="direction"]');
                if (sortInput && directionInput) {
                    sortInput.value = item.getAttribute('data-sort');
                    directionInput.value = item.getAttribute('data-direction');
                    form.submit();
                }
            });
        });

        const advancedSearchForm = document.getElementById('advancedSearchForm');
        const bioFilterContainer = document.getElementById('bioFilterContainer');
        const addBioFilterBtn = document.getElementById('addBioFilterBtn');
        const bioValueSuggestions = @json($bioValueSuggestions ?? []);
        let bioIndexCounter = bioFilterContainer ? bioFilterContainer.querySelectorAll('.bio-filter-row').length : 0;

        const normalizeBioKey = (value) => String(value || '').trim().toLowerCase().replace(/\s+/g, '_');

        const resolveBioValueListId = (bioKey) => {
            const normalized = normalizeBioKey(bioKey);
            return Object.prototype.hasOwnProperty.call(bioValueSuggestions, normalized)
                ? `bio-values-${normalized}`
                : 'bio-values-all';
        };

        const syncBioValueInputList = (row) => {
            const keyInput = row.querySelector('.bio-key-input');
            const valueInput = row.querySelector('.bio-value-input');
            if (!keyInput || !valueInput) {
                return;
            }
            valueInput.setAttribute('list', resolveBioValueListId(keyInput.value));
        };

        const updateRemoveBioButtons = () => {
            if (!bioFilterContainer) {
                return;
            }
            const rows = bioFilterContainer.querySelectorAll('.bio-filter-row');
            rows.forEach((row, rowIndex) => {
                const removeBtn = row.querySelector('.remove-bio-filter-btn');
                if (!removeBtn) {
                    return;
                }
                removeBtn.disabled = rows.length <= 1 && rowIndex === 0;
            });
        };

        const attachBioRowEvents = (row) => {
            const keyInput = row.querySelector('.bio-key-input');
            if (keyInput) {
                keyInput.addEventListener('input', () => syncBioValueInputList(row));
                keyInput.addEventListener('change', () => syncBioValueInputList(row));
            }

            const removeBtn = row.querySelector('.remove-bio-filter-btn');
            if (removeBtn) {
                removeBtn.addEventListener('click', () => {
                    row.remove();
                    updateRemoveBioButtons();
                });
            }

            syncBioValueInputList(row);
        };

        if (bioFilterContainer) {
            bioFilterContainer.querySelectorAll('.bio-filter-row').forEach((row) => attachBioRowEvents(row));
            updateRemoveBioButtons();
        }

        if (addBioFilterBtn && bioFilterContainer) {
            addBioFilterBtn.addEventListener('click', () => {
                const row = document.createElement('div');
                row.className = 'row g-2 align-items-end bio-filter-row mb-2';
                row.dataset.index = String(bioIndexCounter);
                row.innerHTML = `
                    <div class="col-md-4">
                        <label class="form-label mb-1">Bio Key</label>
                        <input type="text" class="form-control bio-key-input" name="bio_filters[${bioIndexCounter}][key]" list="bio-keys" placeholder="e.g. blood_type">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label mb-1">Bio Value</label>
                        <input type="text" class="form-control bio-value-input" name="bio_filters[${bioIndexCounter}][value]" list="bio-values-all" placeholder="e.g. A, Tokyo">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger w-100 remove-bio-filter-btn">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;

                bioFilterContainer.appendChild(row);
                attachBioRowEvents(row);
                updateRemoveBioButtons();
                bioIndexCounter += 1;
            });
        }

        if (advancedSearchForm) {
            advancedSearchForm.addEventListener('submit', () => {
                const rows = advancedSearchForm.querySelectorAll('.bio-filter-row');
                const firstKey = rows[0]?.querySelector('.bio-key-input')?.value || '';
                const firstValue = rows[0]?.querySelector('.bio-value-input')?.value || '';
                const singleKeyInput = advancedSearchForm.querySelector('input[name="bio_key"]');
                const singleValueInput = advancedSearchForm.querySelector('input[name="bio_value"]');
                if (singleKeyInput) {
                    singleKeyInput.value = firstKey;
                }
                if (singleValueInput) {
                    singleValueInput.value = firstValue;
                }
            });
        }

        container.addEventListener('click', async function (e) {
            const card = e.target.closest('.movie-card');
            if (card && !e.target.closest('a') && !e.target.closest('button')) {
                window.location.href = `/jav/movies/${card.getAttribute('data-uuid')}`;
                return;
            }

            const likeBtn = e.target.closest('.quick-like-btn');
            if (likeBtn) {
                e.preventDefault();
                const javId = likeBtn.dataset.javId;
                const result = await requestJson("{{ route('jav.toggle-like') }}", 'POST', { id: javId, type: 'jav' });
                if (!result.ok || !result.data.success) {
                    return;
                }

                const liked = !!result.data.liked;
                likeBtn.classList.toggle('btn-danger', liked);
                likeBtn.classList.toggle('btn-outline-danger', !liked);
                likeBtn.innerHTML = liked ? '<i class="fas fa-heart"></i>' : '<i class="far fa-heart"></i>';
                return;
            }

            const watchlistBtn = e.target.closest('.quick-watchlist-btn');
            if (watchlistBtn) {
                e.preventDefault();
                const javId = watchlistBtn.dataset.javId;
                const watchlistId = watchlistBtn.dataset.watchlistId;

                if (watchlistId) {
                    const removeResult = await requestJson(`/watchlist/${watchlistId}`, 'DELETE');
                    if (!removeResult.ok || !removeResult.data.success) {
                        return;
                    }
                    watchlistBtn.dataset.watchlistId = '';
                    watchlistBtn.classList.remove('btn-warning');
                    watchlistBtn.classList.add('btn-outline-warning');
                    watchlistBtn.innerHTML = '<i class="far fa-bookmark"></i>';
                    return;
                }

                const addResult = await requestJson("{{ route('watchlist.store') }}", 'POST', { jav_id: javId, status: 'to_watch' });
                if (!addResult.ok || !addResult.data.success) {
                    return;
                }
                watchlistBtn.dataset.watchlistId = addResult.data.watchlist.id;
                watchlistBtn.classList.remove('btn-outline-warning');
                watchlistBtn.classList.add('btn-warning');
                watchlistBtn.innerHTML = '<i class="fas fa-bookmark"></i>';
                return;
            }

            const rateBtn = e.target.closest('.quick-rate-btn');
            if (rateBtn) {
                e.preventDefault();
                const javId = rateBtn.dataset.javId;
                const ratingValue = parseInt(rateBtn.dataset.rating, 10);
                const ratingId = rateBtn.dataset.ratingId || null;

                let result;
                if (ratingId) {
                    result = await requestJson(`/ratings/${ratingId}`, 'PUT', { rating: ratingValue, review: '' });
                } else {
                    result = await requestJson("{{ route('ratings.store') }}", 'POST', { jav_id: javId, rating: ratingValue, review: '' });
                    if (!result.ok && result.data && String(result.data.message || '').includes('already rated')) {
                        const check = await requestJson(`/ratings/check/${javId}`);
                        if (check.ok && check.data.id) {
                            result = await requestJson(`/ratings/${check.data.id}`, 'PUT', { rating: ratingValue, review: '' });
                        }
                    }
                }

                if (!result.ok || !result.data.success) {
                    return;
                }

                const group = card.querySelector('.quick-rating-group');
                const newRatingId = result.data.data ? result.data.data.id : ratingId;
                group.querySelectorAll('.quick-rate-btn').forEach((btn) => {
                    btn.dataset.ratingId = newRatingId || '';
                    const isActive = parseInt(btn.dataset.rating, 10) <= ratingValue;
                    btn.classList.toggle('text-warning', isActive);
                    btn.classList.toggle('text-secondary', !isActive);
                });
            }
        });
    });
</script>
@endpush
