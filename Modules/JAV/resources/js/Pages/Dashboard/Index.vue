<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';
import MovieCard from '@jav/Components/MovieCard.vue';
import { useUIStore } from '@jav/Stores/ui';

const props = defineProps({
    items: Object,
    query: String,
    filters: Object,
    sort: String,
    direction: String,
    preset: String,
    builtInPresets: Object,
    savedPresets: Array,
    savedPresetIndex: Number,
    continueWatching: Array,
    preferences: Object,
    tagsInput: String,
    availableBioKeys: Object,
    actorSuggestions: Array,
    tagSuggestions: Array,
    bioValueSuggestions: Object,
});

const uiStore = useUIStore();
const page = usePage();
const hasAuthUser = computed(() => Boolean(page.props.auth?.user));
const visibleItems = ref([...(props.items?.data || [])]);
const nextPageUrl = ref(props.items?.next_page_url || null);
const loadingMore = ref(false);
const sentinelRef = ref(null);
let observer = null;

const presetName = ref('');
const filterForm = ref({
    q: props.query || '',
    actor: props.filters?.actor || '',
    tag: props.tagsInput || props.filters?.tag || '',
    tags_mode: props.filters?.tags_mode || 'any',
    age: props.filters?.age ?? '',
    age_min: props.filters?.age_min ?? '',
    age_max: props.filters?.age_max ?? '',
});

const bioFilters = ref(
    (props.filters?.bio_filters && props.filters.bio_filters.length > 0)
        ? props.filters.bio_filters.map((row) => ({
            key: row?.key || '',
            value: row?.value || '',
        }))
        : [{ key: '', value: '' }]
);

const sortOptions = [
    { label: 'Date (Newest)', sort: 'created_at', direction: 'desc' },
    { label: 'Date (Oldest)', sort: 'created_at', direction: 'asc' },
    { label: 'Most Viewed', sort: 'views', direction: 'desc' },
    { label: 'Least Viewed', sort: 'views', direction: 'asc' },
    { label: 'Most Downloaded', sort: 'downloads', direction: 'desc' },
    { label: 'Least Downloaded', sort: 'downloads', direction: 'asc' },
];

const currentSortLabel = computed(() => {
    const current = sortOptions.find((opt) => opt.sort === props.sort && opt.direction === props.direction);
    return current ? current.label : 'Date';
});

const normalizedTags = computed(() => {
    return String(filterForm.value.tag || '')
        .split(',')
        .map((value) => value.trim())
        .filter((value) => value !== '');
});

const filteredBioFilters = computed(() => {
    return bioFilters.value.filter((row) => row.key || row.value);
});

const paramsForSearch = () => {
    const params = {
        q: filterForm.value.q || '',
        actor: filterForm.value.actor || '',
        tag: filterForm.value.tag || '',
        tags: normalizedTags.value,
        tags_mode: filterForm.value.tags_mode || 'any',
        age: filterForm.value.age || '',
        age_min: filterForm.value.age_min || '',
        age_max: filterForm.value.age_max || '',
        bio_filters: filteredBioFilters.value,
        bio_key: filteredBioFilters.value[0]?.key || '',
        bio_value: filteredBioFilters.value[0]?.value || '',
        sort: props.sort || '',
        direction: props.direction || 'desc',
        preset: props.preset || 'default',
    };

    if (props.savedPresetIndex !== null && props.savedPresetIndex !== undefined) {
        params.saved_preset = props.savedPresetIndex;
    }

    return params;
};

const submitSearch = () => {
    router.get(route('jav.vue.dashboard'), paramsForSearch(), {
        preserveScroll: true,
    });
};

const applySort = (sort, direction) => {
    const params = paramsForSearch();
    params.sort = sort;
    params.direction = direction;

    router.get(route('jav.vue.dashboard'), params, {
        preserveScroll: true,
    });
};

const addBioFilter = () => {
    bioFilters.value.push({ key: '', value: '' });
};

const removeBioFilter = (index) => {
    if (bioFilters.value.length <= 1) {
        return;
    }
    bioFilters.value.splice(index, 1);
};

const bioValueListId = (bioKey) => {
    const normalized = String(bioKey || '').trim().toLowerCase().replace(/\s+/g, '_');
    return Object.prototype.hasOwnProperty.call(props.bioValueSuggestions || {}, normalized)
        ? `bio-values-${normalized}`
        : 'bio-values-all';
};

const savePreset = () => {
    if (!presetName.value.trim()) {
        uiStore.showToast('Preset name is required', 'error');
        return;
    }

    router.post(route('jav.presets.save'), {
        name: presetName.value.trim(),
        q: filterForm.value.q || '',
        actor: filterForm.value.actor || '',
        tag: filterForm.value.tag || '',
        tags: normalizedTags.value,
        tags_mode: filterForm.value.tags_mode || 'any',
        age: filterForm.value.age || '',
        age_min: filterForm.value.age_min || '',
        age_max: filterForm.value.age_max || '',
        bio_key: filteredBioFilters.value[0]?.key || '',
        bio_value: filteredBioFilters.value[0]?.value || '',
        bio_filters: filteredBioFilters.value,
        sort: props.sort || '',
        direction: props.direction || 'desc',
        preset: props.preset || 'default',
    }, {
        preserveScroll: true,
        onSuccess: () => {
            presetName.value = '';
        },
    });
};

const deletePreset = (index) => {
    router.delete(route('jav.presets.delete', index), {
        preserveScroll: true,
    });
};

const continueWatchingTitle = (title) => {
    const text = String(title || '');
    if (text.length <= 55) {
        return text;
    }

    return `${text.slice(0, 55)}...`;
};

const parseUrlParams = (url) => {
    try {
        const parsed = new URL(url, window.location.origin);
        return Object.fromEntries(parsed.searchParams.entries());
    } catch (error) {
        return {};
    }
};

const loadMore = () => {
    if (loadingMore.value || !nextPageUrl.value) {
        return;
    }

    loadingMore.value = true;
    const params = parseUrlParams(nextPageUrl.value);

    router.get(route('jav.vue.dashboard'), params, {
        preserveState: true,
        preserveScroll: true,
        only: ['items'],
        onSuccess: (visit) => {
            const incoming = visit?.props?.items;
            if (incoming?.data) {
                visibleItems.value = [...visibleItems.value, ...incoming.data];
                nextPageUrl.value = incoming.next_page_url || null;
            } else {
                nextPageUrl.value = null;
            }
        },
        onFinish: () => {
            loadingMore.value = false;
        },
    });
};

const setupObserver = () => {
    if (!sentinelRef.value) {
        return;
    }

    observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                loadMore();
            }
        });
    }, { rootMargin: '200px' });

    observer.observe(sentinelRef.value);
};

watch(
    () => props.items,
    (incoming) => {
        if (!incoming) {
            visibleItems.value = [];
            nextPageUrl.value = null;
            return;
        }

        if (Number(incoming.current_page || 1) <= 1) {
            visibleItems.value = [...(incoming.data || [])];
        }
        nextPageUrl.value = incoming.next_page_url || null;
    },
    { deep: true }
);

onMounted(() => {
    setupObserver();
});

onBeforeUnmount(() => {
    if (observer) {
        observer.disconnect();
        observer = null;
    }
});
</script>

<template>
    <Head title="JAV Dashboard" />

    <DashboardLayout>
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-md-8">
                    <h2>Movies</h2>
                    <span v-if="filters?.actor" class="badge bg-primary fs-6">
                        Actor: {{ filters.actor }}
                        <Link :href="route('jav.vue.dashboard')" class="text-white ms-2"><i class="fas fa-times"></i></Link>
                    </span>
                    <span v-if="filters?.tags && filters.tags.length > 0" class="badge bg-info fs-6">
                        Tags: {{ filters.tags.join(', ') }}
                        <Link :href="route('jav.vue.dashboard')" class="text-white ms-2"><i class="fas fa-times"></i></Link>
                    </span>
                    <span v-if="filters?.age" class="badge bg-secondary fs-6">Age: {{ filters.age }}</span>
                    <span v-else-if="filters?.age_min || filters?.age_max" class="badge bg-secondary fs-6">
                        Age Range: {{ filters.age_min || 'Any' }} - {{ filters.age_max || 'Any' }}
                    </span>
                    <span v-for="(bioFilter, index) in (filters?.bio_filters || [])" :key="`bio-badge-${index}`" class="badge bg-dark fs-6">
                        <template v-if="bioFilter?.key || bioFilter?.value">
                            Bio: {{ bioFilter?.key || 'Any' }} = {{ bioFilter?.value || 'Any' }}
                        </template>
                    </span>
                </div>
                <div class="col-md-4 text-md-end">
                    <button v-if="hasAuthUser" class="btn btn-outline-success btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#savePresetBox">
                        <i class="fas fa-save me-1"></i>Save Current As Preset
                    </button>
                </div>
            </div>

            <div v-if="hasAuthUser" class="collapse mb-3" id="savePresetBox">
                <div class="card card-body">
                    <form class="row g-2 align-items-end" @submit.prevent="savePreset">
                        <div class="col-md-4">
                            <label class="form-label">Preset Name</label>
                            <input v-model="presetName" type="text" class="form-control" maxlength="60" required>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" type="submit">Save Preset</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <Link
                            v-for="(presetLabel, presetKey) in (builtInPresets || {})"
                            :key="`built-in-${presetKey}`"
                            :href="route('jav.vue.dashboard', { preset: presetKey, q: query || '' })"
                            class="btn btn-sm"
                            :class="preset === presetKey && (savedPresetIndex === null || savedPresetIndex === undefined) ? 'btn-primary' : 'btn-outline-primary'"
                        >
                            {{ presetLabel }}
                        </Link>
                    </div>

                    <div v-if="savedPresets && savedPresets.length > 0" class="d-flex flex-wrap gap-2">
                        <template v-for="(saved, index) in savedPresets" :key="`saved-preset-${index}`">
                            <Link
                                :href="route('jav.vue.dashboard', { saved_preset: index })"
                                class="btn btn-sm"
                                :class="savedPresetIndex === index ? 'btn-success' : 'btn-outline-success'"
                            >
                                {{ saved?.name || `Preset ${index + 1}` }}
                            </Link>
                            <button type="button" class="btn btn-sm btn-outline-danger" title="Delete preset" @click="deletePreset(index)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form id="advancedSearchForm" class="row g-2 align-items-end" @submit.prevent="submitSearch">
                        <div class="col-md-3">
                            <label class="form-label">Keyword</label>
                            <input v-model="filterForm.q" type="text" name="q" class="form-control" placeholder="Code, title, description">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Actor</label>
                            <input v-model="filterForm.actor" type="text" name="actor" list="actor-suggestions" class="form-control" placeholder="Name or names, comma-separated">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tags (multi)</label>
                            <input v-model="filterForm.tag" type="text" name="tag" list="tag-suggestions" class="form-control" placeholder="Tag A, Tag B, Tag C">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tags Mode</label>
                            <select v-model="filterForm.tags_mode" name="tags_mode" class="form-select">
                                <option value="any">Match Any</option>
                                <option value="all">Match All</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Exact Age</label>
                            <input v-model="filterForm.age" type="number" min="18" max="99" name="age" class="form-control" placeholder="e.g. 25">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Age Min</label>
                            <input v-model="filterForm.age_min" type="number" min="18" max="99" name="age_min" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Age Max</label>
                            <input v-model="filterForm.age_max" type="number" min="18" max="99" name="age_max" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Bio Filters</label>
                            <button type="button" class="btn btn-sm btn-outline-primary w-100" @click="addBioFilter">
                                <i class="fas fa-plus me-1"></i>Add Bio Filter
                            </button>
                        </div>
                        <div class="col-md-12">
                            <div id="bioFilterContainer">
                                <div
                                    v-for="(bioFilter, bioIndex) in bioFilters"
                                    :key="`bio-row-${bioIndex}`"
                                    class="row g-2 align-items-end bio-filter-row mb-2"
                                >
                                    <div class="col-md-4">
                                        <label class="form-label mb-1">Bio Key</label>
                                        <input
                                            v-model="bioFilter.key"
                                            type="text"
                                            class="form-control bio-key-input"
                                            :name="`bio_filters[${bioIndex}][key]`"
                                            list="bio-keys"
                                            placeholder="e.g. blood_type"
                                        >
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label mb-1">Bio Value</label>
                                        <input
                                            v-model="bioFilter.value"
                                            type="text"
                                            class="form-control bio-value-input"
                                            :name="`bio_filters[${bioIndex}][value]`"
                                            :list="bioValueListId(bioFilter.key)"
                                            placeholder="e.g. A, Tokyo"
                                        >
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger w-100 remove-bio-filter-btn" :disabled="bioFilters.length === 1 && bioIndex === 0" @click="removeBioFilter(bioIndex)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Apply</button>
                        </div>
                        <div class="col-md-2">
                            <Link :href="route('jav.vue.dashboard')" class="btn btn-outline-secondary w-100">Reset</Link>
                        </div>
                    </form>

                    <datalist id="actor-suggestions">
                        <option v-for="actorName in (actorSuggestions || [])" :key="`actor-suggestion-${actorName}`" :value="actorName"></option>
                    </datalist>

                    <datalist id="tag-suggestions">
                        <option v-for="tagName in (tagSuggestions || [])" :key="`tag-suggestion-${tagName}`" :value="tagName"></option>
                    </datalist>

                    <datalist id="bio-keys">
                        <option v-for="(label, key) in (availableBioKeys || {})" :key="`bio-key-${key}`" :value="key">{{ label }}</option>
                    </datalist>

                    <datalist id="bio-values-all">
                        <template v-for="(valueList, bioKey) in (bioValueSuggestions || {})" :key="`bio-values-all-${bioKey}`">
                            <option v-for="value in valueList" :key="`bio-values-all-${bioKey}-${value}`" :value="value"></option>
                        </template>
                    </datalist>

                    <datalist v-for="(valueList, bioKey) in (bioValueSuggestions || {})" :id="`bio-values-${bioKey}`" :key="`bio-values-${bioKey}`">
                        <option v-for="value in valueList" :key="`bio-values-${bioKey}-${value}`" :value="value"></option>
                    </datalist>
                </div>
            </div>

            <div v-if="continueWatching && continueWatching.length > 0" class="mb-4">
                <h5 class="mb-3">Continue Watching</h5>
                <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-3">
                    <div v-for="record in continueWatching" :key="`continue-${record.id}`" class="col">
                        <div class="card h-100">
                            <div class="card-body">
                                <Link :href="route('jav.vue.movies.show', record.jav.uuid || record.jav.id)" class="text-decoration-none">
                                    <h6 class="mb-1">{{ record.jav.formatted_code }}</h6>
                                    <div class="text-muted small">{{ continueWatchingTitle(record.jav.title) }}</div>
                                </Link>
                                <div class="mt-2">
                                    <span class="badge" :class="record.action === 'download' ? 'bg-success' : 'bg-info'">
                                        {{ String(record.action || '').charAt(0).toUpperCase() + String(record.action || '').slice(1) }}
                                    </span>
                                    <small class="text-muted ms-2">Last activity: {{ record.updated_at_human || record.updated_at }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3 justify-content-end">
                <div class="col-auto">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            Sort By: {{ currentSortLabel }}
                        </button>
                        <ul class="dropdown-menu">
                            <li v-for="opt in sortOptions" :key="`sort-${opt.sort}-${opt.direction}`">
                                <a class="dropdown-item" href="#" @click.prevent="applySort(opt.sort, opt.direction)">{{ opt.label }}</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div id="lazy-container" class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <MovieCard v-for="item in visibleItems" :key="item.id" :item="item" />

                <div v-if="visibleItems.length === 0" class="col-12">
                    <div class="alert alert-warning text-center">
                        No movies found.
                    </div>
                </div>
            </div>

            <div ref="sentinelRef" id="sentinel"></div>
            <div v-if="loadingMore" id="loading-spinner" class="text-center my-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
