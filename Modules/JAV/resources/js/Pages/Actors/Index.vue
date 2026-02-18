<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import OrderingBar from '@jav/Components/Search/OrderingBar.vue';
import PageShell from '@jav/Components/UI/PageShell.vue';
import SectionHeader from '@jav/Components/UI/SectionHeader.vue';
import EmptyState from '@jav/Components/UI/EmptyState.vue';
import ActorCard from '@jav/Components/ActorCard.vue';

const props = defineProps({
    actors: Object,
    query: String,
    filters: Object,
    sort: String,
    direction: String,
    tagsInput: String,
    availableBioKeys: Object,
    tagSuggestions: Array,
    bioValueSuggestions: Object,
});
const visibleActors = ref([...(props.actors?.data || [])]);
const nextPageUrl = ref(props.actors?.next_page_url || null);
const loadingMore = ref(false);
const sentinelRef = ref(null);

const normalizedTags = computed(() => {
    return String(props.tagsInput || props.filters?.tag || '')
        .split(',')
        .map((value) => value.trim())
        .filter((value) => value !== '');
});

const filteredBioFilters = computed(() => {
    return (props.filters?.bio_filters || []).filter((row) => row.key || row.value);
});
let observer = null;
const actorSortOptions = [
    { label: 'Most JAVs', sort: 'javs_count', direction: 'desc' },
    { label: 'Fewest JAVs', sort: 'javs_count', direction: 'asc' },
    { label: 'Name (A-Z)', sort: 'name', direction: 'asc' },
    { label: 'Name (Z-A)', sort: 'name', direction: 'desc' },
    { label: 'Newest', sort: 'created_at', direction: 'desc' },
    { label: 'Oldest', sort: 'created_at', direction: 'asc' },
];
const matchedActorsTotal = computed(() => {
    const total = Number(props.actors?.total);
    return Number.isFinite(total) ? total : visibleActors.value.length;
});


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

    router.get(route('jav.vue.actors'), params, {
        preserveState: true,
        preserveScroll: true,
        only: ['actors'],
        onSuccess: (visit) => {
            const incoming = visit?.props?.actors;
            if (incoming?.data) {
                visibleActors.value = [...visibleActors.value, ...incoming.data];
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
        q: props.query || '',
        tag: props.tagsInput || props.filters?.tag || '',
        tags: normalizedTags.value,
        tags_mode: props.filters?.tags_mode || 'any',
        age: props.filters?.age || '',
        age_min: props.filters?.age_min || '',
        age_max: props.filters?.age_max || '',
        bio_filters: filteredBioFilters.value,
        bio_key: filteredBioFilters.value[0]?.key || '',
        bio_value: filteredBioFilters.value[0]?.value || '',
        sort: props.sort || 'javs_count',
        direction: props.direction || 'desc',
    };
};

const applySort = (sort, direction) => {
    const params = paramsForSearch();
    params.sort = sort;
    params.direction = direction;

    router.get(route('jav.vue.actors'), params, {
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
    () => props.actors,
    (incoming) => {
        if (!incoming) {
            visibleActors.value = [];
            nextPageUrl.value = null;
            return;
        }

        if (Number(incoming.current_page || 1) <= 1) {
            visibleActors.value = [...(incoming.data || [])];
        }
        nextPageUrl.value = incoming.next_page_url || null;
    },
    { deep: true }
);

// Removed watchers that sync props to filterForm since we use props directly now
</script>

<template>
    <Head title="Actors" />

    <PageShell>
        <template #header>
            <SectionHeader title="Actors" subtitle="Browse profiles and filter by bio attributes" />
        </template>

        <OrderingBar
            :total-matches="matchedActorsTotal"
            :loaded-matches="visibleActors.length"
            :has-auth-user="false"
            :show-save-button="false"
            :show-save-form="false"
            :show-preset-section="false"
            :show-sort-section="true"
            :sort="sort || 'javs_count'"
            :direction="direction || 'desc'"
            :options="actorSortOptions"
            @sort-selected="applySort($event.sort, $event.direction)"
        />



        <div class="ui-row ui-row-cols-2 ui-row-cols-md-4 ui-row-cols-lg-6 ui-g-4">
            <ActorCard v-for="actor in visibleActors" :key="actor.id" :actor="actor" />

            <div v-if="visibleActors.length === 0" class="ui-col-12">
                <EmptyState tone="warning" icon="fas fa-users" message="No actors found." />
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
</style>
