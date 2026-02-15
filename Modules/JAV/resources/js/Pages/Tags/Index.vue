<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import OrderingBar from '@jav/Components/Search/OrderingBar.vue';

const props = defineProps({
    tags: Object,
    query: String,
    sort: String,
    direction: String,
});

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

    
        <div class="ui-container-fluid">
            <div class="ui-row mb-4">
                <div class="ui-col-md-12">
                    <h2>Tags</h2>
                </div>
            </div>

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

            <div class="ui-row ui-row-cols-2 ui-row-cols-md-4 ui-row-cols-lg-6 ui-g-4">
                <div v-for="tag in visibleTags" :key="tag.id" class="ui-col">
                    <Link :href="route('jav.vue.dashboard', { tag: tag.name })" class="u-no-underline u-text-dark">
                        <div class="ui-card u-h-full u-shadow-sm hover-shadow">
                            <div class="ui-card-body u-text-center">
                                <i class="fas fa-tag fa-2x u-text-info mb-3"></i>
                                <h5 class="ui-card-title u-truncate" :title="tag.name">{{ tag.name }}</h5>
                                <span class="ui-badge u-bg-secondary">{{ tag.javs_count || 0 }} JAVs</span>
                            </div>
                        </div>
                    </Link>
                </div>
                <div v-if="visibleTags.length === 0" class="ui-col-12">
                    <div class="ui-alert ui-alert-warning u-text-center">
                        No tags found.
                    </div>
                </div>
            </div>

            <div ref="sentinelRef" id="sentinel"></div>
            <div v-if="loadingMore" id="loading-spinner" class="u-text-center my-4">
                <output class="ui-spinner u-text-primary" aria-live="polite">
                    <span class="visually-hidden">Loading...</span>
                </output>
            </div>
        </div>
    
</template>

<style scoped>
.hover-shadow {
    transition: box-shadow 0.2s ease-in-out;
}

.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
</style>
