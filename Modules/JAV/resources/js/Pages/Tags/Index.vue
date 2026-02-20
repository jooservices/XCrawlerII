<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import OrderingBar from '@jav/Components/Search/OrderingBar.vue';
import PageShell from '@jav/Components/UI/PageShell.vue';
import SectionHeader from '@jav/Components/UI/SectionHeader.vue';
import EmptyState from '@jav/Components/UI/EmptyState.vue';
import TagCard from '@jav/Components/TagCard.vue';

const props = defineProps({
    tags: Object,
    query: String,
    sort: String,
    direction: String,
});

const page = usePage();
const hasAuthUser = computed(() => Boolean(page.props.auth?.user));

const visibleTags = ref([...(props.tags?.data || [])]);
const nextPageUrl = ref(props.tags?.next_page_url || null);
const loadingMore = ref(false);
const sentinelRef = ref(null);
const filterForm = ref({
    q: props.query || '',
});

const sortOptions = [
    { label: 'Most JAVs', sort: 'javs_count', direction: 'desc' },
    { label: 'Fewest JAVs', sort: 'javs_count', direction: 'asc' },
    { label: 'Name (A-Z)', sort: 'name', direction: 'asc' },
    { label: 'Name (Z-A)', sort: 'name', direction: 'desc' },
    { label: 'Newest', sort: 'created_at', direction: 'desc' },
    { label: 'Oldest', sort: 'created_at', direction: 'asc' },
];
let observer = null;

const resolveTagRate = (tag) => {
    const directRate = Number(tag?.rate);
    if (Number.isFinite(directRate) && directRate > 0) {
        return directRate;
    }

    return null;
};

const formatRate = (value) => {
    const number = Number(value);
    if (!Number.isFinite(number) || number <= 0) {
        return null;
    }

    return Number.isInteger(number) ? String(number) : number.toFixed(1);
};

const tagStarCount = (tag) => {
    const rate = resolveTagRate(tag);
    if (rate === null) {
        return 0;
    }

    return Math.max(0, Math.min(5, Math.round(Number(rate))));
};

const parseUrlParams = (url) => {
    try {
        const parsed = new URL(url, globalThis.location.origin);
        return Object.fromEntries(parsed.searchParams.entries());
    } catch {
        return {};
    }
};

const loadMore = () => {
    if (loadingMore.value || !nextPageUrl.value) {
        return;
    }

    loadingMore.value = true;
    const params = parseUrlParams(nextPageUrl.value);

    router.get(route('jav.vue.tags'), params, {
        preserveState: true,
        preserveScroll: true,
        only: ['tags'],
        onSuccess: (visit) => {
            const incoming = visit?.props?.tags;
            if (incoming?.data) {
                visibleTags.value = [...visibleTags.value, ...incoming.data];
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

const paramsForSearch = () => {
    return {
        q: filterForm.value.q || '',
        sort: props.sort || 'javs_count',
        direction: props.direction || 'desc',
    };
};

const submitSearch = () => {
    router.get(route('jav.vue.tags'), paramsForSearch(), {
        preserveScroll: true,
    });
};

const handleSortSelected = (option) => {
    router.get(route('jav.vue.tags'), {
        q: filterForm.value.q || '',
        sort: option.sort,
        direction: option.direction,
    }, {
        preserveScroll: true,
    });
};

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                loadMore();
            }
        });
    }, { rootMargin: '200px' });

    if (sentinelRef.value) {
        observer.observe(sentinelRef.value);
    }
});

onBeforeUnmount(() => {
    if (observer) {
        observer.disconnect();
        observer = null;
    }
});

watch(
    () => props.tags,
    (incoming) => {
        if (!incoming) {
            visibleTags.value = [];
            nextPageUrl.value = null;
            return;
        }

        if (Number(incoming.current_page || 1) <= 1) {
            visibleTags.value = [...(incoming.data || [])];
        }
        nextPageUrl.value = incoming.next_page_url || null;
    },
    { deep: true }
);

watch(
    () => props.query,
    (value) => {
        filterForm.value.q = value || '';
    }
);
</script>

<template>
    <Head>
        <title>Tags</title>
    </Head>

    <PageShell>
        <template #header>
            <SectionHeader title="Tags" subtitle="Browse and filter by tag" />
        </template>

            <OrderingBar
                :has-auth-user="false"
                :query="filterForm.q"
                :sort="props.sort || 'javs_count'"
                :direction="props.direction || 'desc'"
                :total-matches="Number(props.tags?.total || 0)"
                :loaded-matches="visibleTags.length"
                :show-save-button="false"
                :show-save-form="false"
                :show-preset-section="false"
                :show-sort-section="true"
                :options="sortOptions"
                @sort-selected="handleSortSelected"
            />

            <div class="ui-card mb-3">
                <div class="ui-card-body">
                    <form class="u-flex u-items-end" @submit.prevent="submitSearch">
                        <div class="u-flex-grow-1 mr-2">
                            <input
                                id="tags_search_q"
                                v-model="filterForm.q"
                                type="text"
                                name="q"
                                class="ui-form-control"
                                placeholder="Search ..."
                            >
                        </div>
                        <button type="submit" class="ui-btn ui-btn-primary" title="Search" aria-label="Search">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>

        <div class="tag-masonry-grid">
            <TagCard
                v-for="tag in visibleTags"
                :key="tag.id"
                :tag="tag"
                :has-auth-user="hasAuthUser"
                :liked="Boolean(tag.is_liked)"
                :tag-rate="formatRate(resolveTagRate(tag))"
                :tag-star-count="tagStarCount(tag)"
            />
            <div v-if="visibleTags.length === 0" class="ui-col-12">
                <EmptyState tone="warning" icon="fas fa-tags" message="No tags found." />
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
.tag-masonry-grid {
    column-count: 2;
    column-gap: 0.75rem;
}

.tag-masonry-grid > .ui-col,
.tag-masonry-grid > .ui-col-12 {
    break-inside: avoid;
    margin-bottom: 0.75rem;
}

.tag-masonry-grid > .ui-col-12 {
    column-span: all;
}

@media (min-width: 768px) {
    .tag-masonry-grid {
        column-count: 6;
    }
}

@media (min-width: 1200px) {
    .tag-masonry-grid {
        column-count: 6;
    }
}
</style>
