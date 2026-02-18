<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useInfiniteQuery } from '@tanstack/vue-query';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Navigation, Pagination, A11y } from 'swiper/modules';
import axios from 'axios';
import MovieCard from '@jav/Components/MovieCard.vue';
import ActorCard from '@jav/Components/ActorCard.vue';
import TagCard from '@jav/Components/TagCard.vue';
import OrderingBar from '@jav/Components/Search/OrderingBar.vue';
import PageShell from '@jav/Components/UI/PageShell.vue';
import SectionHeader from '@jav/Components/UI/SectionHeader.vue';
import EmptyState from '@jav/Components/UI/EmptyState.vue';
import { useUIStore } from '@jav/Stores/ui';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

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
    featuredGroups: Array,
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
const sentinelRef = ref(null);
const showSavePreset = ref(false);
let observer = null;
const continueWatchingModules = [Navigation, Pagination, A11y];
const continueWatchingBreakpoints = {
    0: { slidesPerView: 1.1, spaceBetween: 12 },
    576: { slidesPerView: 2.1, spaceBetween: 12 },
    992: { slidesPerView: 3.1, spaceBetween: 16 },
    1200: { slidesPerView: 4.1, spaceBetween: 16 },
};

const groupTitleMap = {
    recent: 'Recent Updates',
    trending: 'Trending',
    top: 'Top Views',
    staff_pick: 'Staff Picks',
};

const featuredSections = computed(() => {
    const groups = Array.isArray(props.featuredGroups) ? props.featuredGroups : [];

    return groups.map((group) => {
        const items = Array.isArray(group.items) ? group.items : [];
        const movieItems = items
            .filter((entry) => entry.item_type === 'movie' && entry.item)
            .map((entry) => entry.item);
        const actorItems = items
            .filter((entry) => entry.item_type === 'actor' && entry.item)
            .map((entry) => entry.item);
        const tagItems = items
            .filter((entry) => entry.item_type === 'tag' && entry.item)
            .map((entry) => entry.item);

        return {
            key: group.key,
            title: groupTitleMap[group.key] || String(group.key || 'Featured'),
            movieItems,
            actorItems,
            tagItems,
        };
    });
});


const presetName = ref('');

// Helper to normalized tags from props
const normalizedTags = computed(() => {
    return String(props.tagsInput || props.filters?.tag || '')
        .split(',')
        .map((value) => value.trim())
        .filter((value) => value !== '');
});

const filteredBioFilters = computed(() => {
    return (props.filters?.bio_filters || []).filter((row) => row.key || row.value);
});

const normalizeTagLabel = (value) => {
    const raw = String(value || '');
    let decoded = raw;
    try {
        decoded = decodeURIComponent(raw);
    } catch (error) {
        decoded = raw;
    }

    return decoded.trim().replace(/\s+/g, ' ').toLowerCase();
};

const selectedDashboardTags = computed(() => {
    const fromArray = Array.isArray(props.filters?.tags) ? props.filters.tags : [];
    const fromCsv = String(props.tagsInput || props.filters?.tag || '')
        .split(',')
        .map((value) => normalizeTagLabel(value))
        .filter((value) => value !== '');
    const fromUrl = [];

    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);

        for (const [key, rawValue] of params.entries()) {
            if (key === 'tag') {
                String(rawValue || '')
                    .split(',')
                    .map((value) => normalizeTagLabel(value))
                    .filter((value) => value !== '')
                    .forEach((value) => fromUrl.push(value));
                continue;
            }

            if (key === 'tags' || key === 'tags[]' || /^tags\[\d+\]$/.test(key)) {
                const normalized = normalizeTagLabel(rawValue);
                if (normalized !== '') {
                    fromUrl.push(normalized);
                }
            }
        }
    }

    const normalizedFromArray = fromArray
        .map((value) => normalizeTagLabel(value))
        .filter((value) => value !== '');

    return [...new Set([...normalizedFromArray, ...fromCsv, ...fromUrl])];
});

const dashboardParams = computed(() => {
    const params = {
        q: props.query || '',
        actor: props.filters?.actor || '',
        tag: props.tagsInput || props.filters?.tag || '',
        tags: props.filters?.tags || [],
        tags_mode: props.filters?.tags_mode || 'any',
        age: props.filters?.age || '',
        age_min: props.filters?.age_min || '',
        age_max: props.filters?.age_max || '',
        bio_filters: props.filters?.bio_filters || [],
        bio_key: props.filters?.bio_key || '',
        bio_value: props.filters?.bio_value || '',
        sort: props.sort || '',
        direction: props.direction || 'desc',
        preset: props.preset || 'default',
    };

    if (props.savedPresetIndex !== null && props.savedPresetIndex !== undefined) {
        params.saved_preset = props.savedPresetIndex;
    }

    return params;
});

const dashboardQueryKey = computed(() => ['dashboard-items', dashboardParams.value]);

const parsePageFromUrl = (url) => {
    if (!url) {
        return undefined;
    }

    try {
        const parsed = new URL(url, window.location.origin);
        const page = Number(parsed.searchParams.get('page'));
        return Number.isFinite(page) && page > 0 ? page : undefined;
    } catch (error) {
        return undefined;
    }
};

const dashboardItemsQuery = useInfiniteQuery({
    queryKey: dashboardQueryKey,
    initialPageParam: Number(props.items?.current_page || 1),
    queryFn: async ({ pageParam }) => {
        const response = await axios.get(route('jav.api.dashboard.items'), {
            params: {
                ...dashboardParams.value,
                page: pageParam,
            },
        });

        return response.data;
    },
    getNextPageParam: (lastPage) => parsePageFromUrl(lastPage?.next_page_url),
    initialData: () => ({
        pageParams: [Number(props.items?.current_page || 1)],
        pages: [props.items || { data: [], current_page: 1, next_page_url: null }],
    }),
});

const visibleItems = computed(() => {
    return (dashboardItemsQuery.data.value?.pages || []).flatMap((pageData) => pageData?.data || []);
});
const matchedItemsTotal = computed(() => {
    const firstPage = dashboardItemsQuery.data.value?.pages?.[0];
    const total = Number(firstPage?.total);
    return Number.isFinite(total) ? total : visibleItems.value.length;
});

const loadingMore = computed(() => dashboardItemsQuery.isFetchingNextPage.value);
const isRefreshingItems = computed(() => dashboardItemsQuery.isFetching.value && !dashboardItemsQuery.isFetchingNextPage.value);
const hasItemsQueryError = computed(() => dashboardItemsQuery.isError.value);
const itemsQueryErrorMessage = computed(() => dashboardItemsQuery.error.value?.message || 'Could not refresh dashboard items.');

const paramsForSearch = () => {
    const params = {
        q: props.query || '',
        actor: props.filters?.actor || '',
        tag: props.tagsInput || props.filters?.tag || '',
        tags: normalizedTags.value,
        tags_mode: props.filters?.tags_mode || 'any',
        age: props.filters?.age || '',
        age_min: props.filters?.age_min || '',
        age_max: props.filters?.age_max || '',
        bio_filters: props.filters?.bio_filters || [],
        bio_key: props.filters?.bio_key || '',
        bio_value: props.filters?.bio_value || '',
        sort: props.sort || '',
        direction: props.direction || 'desc',
        preset: props.preset || 'default',
    };

    if (props.savedPresetIndex !== null && props.savedPresetIndex !== undefined) {
        params.saved_preset = props.savedPresetIndex;
    }

    return params;
};

const applySort = (sort, direction) => {
    const params = paramsForSearch();
    params.sort = sort;
    params.direction = direction;

    router.get(route('jav.vue.dashboard'), params, {
        preserveScroll: true,
    });
};

const savePreset = () => {
    if (!presetName.value.trim()) {
        uiStore.showToast('Preset name is required', 'error');
        return;
    }

    router.post(route('jav.presets.save'), {
        name: presetName.value.trim(),
        q: props.query || '',
        actor: props.filters?.actor || '',
        tag: props.tagsInput || props.filters?.tag || '',
        tags: props.filters?.tags || [],
        tags_mode: props.filters?.tags_mode || 'any',
        age: props.filters?.age || '',
        age_min: props.filters?.age_min || '',
        age_max: props.filters?.age_max || '',
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

const loadMore = () => {
    if (loadingMore.value || !dashboardItemsQuery.hasNextPage.value) {
        return;
    }

    dashboardItemsQuery.fetchNextPage();
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

    <PageShell>
        <template #header>
            <div class="ui-row mb-0 u-w-full">
                <div class="ui-col-md-12">
                    <SectionHeader class="dashboard-header-single-line" title="Movies" subtitle="Discover and explore content">
                        <template #actions>
                            <span v-if="isRefreshingItems" class="ui-badge u-bg-light u-text-dark u-border ml-2">Refreshing...</span>
                        </template>
                    </SectionHeader>
                    <span v-if="filters?.actor" class="ui-badge u-bg-primary fs-6">
                        Actor: {{ filters.actor }}
                        <Link :href="route('jav.vue.dashboard')" class="u-text-white ml-2"><i class="fas fa-times"></i></Link>
                    </span>
                    <span v-if="filters?.tags && filters.tags.length > 0" class="ui-badge u-bg-info fs-6">
                        Tags: {{ filters.tags.join(', ') }}
                        <Link :href="route('jav.vue.dashboard')" class="u-text-white ml-2"><i class="fas fa-times"></i></Link>
                    </span>
                    <span v-if="filters?.age" class="ui-badge u-bg-secondary fs-6">Age: {{ filters.age }}</span>
                    <span v-else-if="filters?.age_min || filters?.age_max" class="ui-badge u-bg-secondary fs-6">
                        Age Range: {{ filters.age_min || 'Any' }} - {{ filters.age_max || 'Any' }}
                    </span>
                    <span v-for="(bioFilter, index) in (filters?.bio_filters || [])" :key="`bio-badge-${index}`" class="ui-badge u-bg-dark fs-6">
                        <template v-if="bioFilter?.key || bioFilter?.value">
                            Bio: {{ bioFilter?.key || 'Any' }} = {{ bioFilter?.value || 'Any' }}
                        </template>
                    </span>
                </div>
            </div>
        </template>

        <OrderingBar
            :built-in-presets="builtInPresets"
            :preset="preset"
            :saved-presets="savedPresets"
            :saved-preset-index="savedPresetIndex"
            :query="query"
            :has-auth-user="hasAuthUser"
            :show-save-preset="showSavePreset"
            :preset-name="presetName"
            :sort="sort"
            :direction="direction"
            :total-matches="matchedItemsTotal"
            :loaded-matches="visibleItems.length"
            @toggle-save-preset="showSavePreset = !showSavePreset"
            @update:preset-name="presetName = $event"
            @save-preset="savePreset"
            @delete-preset="deletePreset"
            @sort-selected="applySort($event.sort, $event.direction)"
        />

        <div v-if="featuredSections.length > 0" class="mb-4">
            <div v-for="group in featuredSections" :key="group.key" class="mb-4">
                <h5 class="mb-3">{{ group.title }}</h5>

                <div v-if="group.movieItems.length" class="mb-3">
                    <h6 class="u-text-muted mb-2">Movies</h6>
                    <div class="ui-row ui-row-cols-1 ui-row-cols-md-3 ui-row-cols-lg-5 ui-g-4">
                        <MovieCard v-for="item in group.movieItems" :key="`movie-${item.id}`" :item="item" :active-tags="selectedDashboardTags" />
                    </div>
                </div>

                <div v-if="group.actorItems.length" class="mb-3">
                    <h6 class="u-text-muted mb-2">Actors</h6>
                    <div class="ui-row ui-row-cols-1 ui-row-cols-md-3 ui-row-cols-lg-5 ui-g-4">
                        <ActorCard v-for="actor in group.actorItems" :key="`actor-${actor.id}`" :actor="actor" />
                    </div>
                </div>

                <div v-if="group.tagItems.length" class="mb-3">
                    <h6 class="u-text-muted mb-2">Tags</h6>
                    <div class="ui-row ui-row-cols-1 ui-row-cols-md-3 ui-row-cols-lg-5 ui-g-4">
                        <TagCard v-for="tag in group.tagItems" :key="`tag-${tag.id}`" :tag="tag" />
                    </div>
                </div>
            </div>
        </div>



        <div v-if="continueWatching && continueWatching.length > 0" class="mb-4">
            <h5 class="mb-3">Continue Watching</h5>
            <Swiper
                :modules="continueWatchingModules"
                :breakpoints="continueWatchingBreakpoints"
                :slides-per-view="1.1"
                :space-between="12"
                :navigation="true"
                :pagination="{ clickable: true }"
                class="continue-watching-swiper pb-4"
            >
                <SwiperSlide v-for="record in continueWatching" :key="`continue-${record.id}`">
                    <div class="ui-card u-h-full">
                        <div class="ui-card-body">
                            <Link :href="route('jav.vue.movies.show', record.jav.uuid || record.jav.id)" class="u-no-underline">
                                <h6 class="mb-1">{{ record.jav.formatted_code }}</h6>
                                <div class="u-text-muted small">{{ continueWatchingTitle(record.jav.title) }}</div>
                            </Link>
                            <div class="mt-2">
                                <span class="ui-badge" :class="record.action === 'download' ? 'u-bg-success' : 'u-bg-info'">
                                    {{ String(record.action || '').charAt(0).toUpperCase() + String(record.action || '').slice(1) }}
                                </span>
                                <small class="u-text-muted ml-2">Last activity: {{ record.updated_at_human || record.updated_at }}</small>
                            </div>
                        </div>
                    </div>
                </SwiperSlide>
            </Swiper>
        </div>

        <div id="lazy-container" class="ui-row ui-row-cols-1 ui-row-cols-md-3 ui-row-cols-lg-5 ui-g-4">
            <div v-if="hasItemsQueryError" class="ui-col-12">
                <div class="ui-alert ui-alert-danger u-flex u-justify-between u-items-center mb-0">
                    <span>{{ itemsQueryErrorMessage }}</span>
                    <button type="button" class="ui-btn ui-btn-sm ui-btn-outline-danger" title="Retry loading items" aria-label="Retry loading items" @click="dashboardItemsQuery.refetch()">
                        <i class="fas fa-rotate-right"></i>
                    </button>
                </div>
            </div>

            <MovieCard
                v-for="item in visibleItems"
                :key="item.id"
                :item="item"
                :active-tags="selectedDashboardTags"
            />

            <div v-if="visibleItems.length === 0" class="ui-col-12">
                <EmptyState tone="warning" icon="fas fa-film" message="No movies found." />
            </div>
        </div>

        <div ref="sentinelRef" id="sentinel"></div>
        <div v-if="loadingMore" id="loading-spinner" class="u-text-center my-4">
            <output class="ui-spinner u-text-primary" aria-live="polite">
                <span class="visually-hidden">Loading...</span>
            </output>
        </div>
    </PageShell>
</template>

<style scoped>
.dashboard-header-single-line :deep(.ui-page-subtitle) {
    white-space: nowrap;
}

.continue-watching-swiper {
    --swiper-theme-color: var(--primary-strong);
}

.continue-watching-swiper :deep(.swiper-slide) {
    height: auto;
}

.continue-watching-swiper :deep(.swiper-button-next),
.continue-watching-swiper :deep(.swiper-button-prev) {
    width: 36px;
    height: 36px;
    margin-top: -22px;
    border-radius: 999px;
    background-color: var(--overlay-strong);
    color: var(--text-1);
    transition: background-color 0.2s ease;
}

.continue-watching-swiper :deep(.swiper-button-next:hover),
.continue-watching-swiper :deep(.swiper-button-prev:hover) {
    background-color: var(--overlay-primary);
}

.continue-watching-swiper :deep(.swiper-button-next::after),
.continue-watching-swiper :deep(.swiper-button-prev::after) {
    font-size: 13px;
    font-weight: 700;
}

.continue-watching-swiper :deep(.swiper-pagination-bullet) {
    width: 9px;
    height: 9px;
    background: var(--bullet-muted);
    opacity: 1;
}

.continue-watching-swiper :deep(.swiper-pagination-bullet-active) {
    background: var(--primary-strong);
}

.continue-watching-swiper .ui-card {
    border: 1px solid var(--border);
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.continue-watching-swiper .ui-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--card-hover-shadow);
}

@media (max-width: 575.98px) {
    .continue-watching-swiper :deep(.swiper-button-next),
    .continue-watching-swiper :deep(.swiper-button-prev) {
        display: none;
    }
}
</style>
