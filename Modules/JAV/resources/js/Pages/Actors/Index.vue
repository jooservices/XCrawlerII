<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import OrderingBar from '@jav/Components/Search/OrderingBar.vue';
import AdvancedSearchForm from '@jav/Components/Search/AdvancedSearchForm.vue';
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
const page = usePage();
const hasAuthUser = computed(() => Boolean(page.props.auth?.user));

const visibleActors = ref([...(props.actors?.data || [])]);
const nextPageUrl = ref(props.actors?.next_page_url || null);
const loadingMore = ref(false);
const sentinelRef = ref(null);
const filterForm = ref({
    q: props.query || '',
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
const normalizedTags = computed(() => {
    return String(filterForm.value.tag || '')
        .split(',')
        .map((value) => value.trim())
        .filter((value) => value !== '');
});
const filteredBioFilters = computed(() => {
    return bioFilters.value.filter((row) => row.key || row.value);
});
const resolveActorRate = (actor) => {
    const directRate = Number(actor?.rate);
    if (Number.isFinite(directRate) && directRate > 0) {
        return directRate;
    }

    const profileRate = Number(actor?.xcity_profile?.rate ?? actor?.xcity_profile?.rating);
    if (Number.isFinite(profileRate) && profileRate > 0) {
        return profileRate;
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
const actorStarCount = (actor) => {
    const rate = resolveActorRate(actor);
    if (rate === null) {
        return 0;
    }

    return Math.max(0, Math.min(5, Math.round(Number(rate))));
};
const resolveActorAge = (actor) => {
    const directAge = Number(actor?.age);
    if (Number.isFinite(directAge) && directAge > 0) {
        return directAge;
    }

    const rawBirthDate = actor?.birth_date || actor?.xcity_birth_date || null;
    if (!rawBirthDate) {
        return null;
    }

    const birthDate = new Date(rawBirthDate);
    if (Number.isNaN(birthDate.getTime())) {
        return null;
    }

    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const hasNotHadBirthday =
        today.getMonth() < birthDate.getMonth()
        || (today.getMonth() === birthDate.getMonth() && today.getDate() < birthDate.getDate());

    if (hasNotHadBirthday) {
        age -= 1;
    }

    return age >= 0 ? age : null;
};
const isAgeFilterActive = (age) => {
    return String(filterForm.value.age || '') === String(age);
};
const filterByAge = (age) => {
    if (age === null || age === undefined) {
        return;
    }

    filterForm.value.age = String(age);
    filterForm.value.age_min = '';
    filterForm.value.age_max = '';

    router.get(route('jav.vue.actors'), paramsForSearch(), {
        preserveScroll: true,
    });
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
        q: filterForm.value.q || '',
        tag: filterForm.value.tag || '',
        tags: normalizedTags.value,
        tags_mode: filterForm.value.tags_mode || 'any',
        age: filterForm.value.age || '',
        age_min: filterForm.value.age_min || '',
        age_max: filterForm.value.age_max || '',
        bio_filters: filteredBioFilters.value,
        bio_key: filteredBioFilters.value[0]?.key || '',
        bio_value: filteredBioFilters.value[0]?.value || '',
        sort: props.sort || 'javs_count',
        direction: props.direction || 'desc',
    };
};

const submitSearch = () => {
    router.get(route('jav.vue.actors'), paramsForSearch(), {
        preserveScroll: true,
    });
};
const applySort = (sort, direction) => {
    const params = paramsForSearch();
    params.sort = sort;
    params.direction = direction;

    router.get(route('jav.vue.actors'), params, {
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

watch(
    () => props.query,
    (incoming) => {
        filterForm.value.q = incoming || '';
    }
);
watch(
    () => [props.query, props.filters, props.tagsInput],
    () => {
        filterForm.value.q = props.query || '';
        filterForm.value.tag = props.tagsInput || props.filters?.tag || '';
        filterForm.value.tags_mode = props.filters?.tags_mode || 'any';
        filterForm.value.age = props.filters?.age ?? '';
        filterForm.value.age_min = props.filters?.age_min ?? '';
        filterForm.value.age_max = props.filters?.age_max ?? '';
        bioFilters.value = (props.filters?.bio_filters && props.filters.bio_filters.length > 0)
            ? props.filters.bio_filters.map((row) => ({
                key: row?.key || '',
                value: row?.value || '',
            }))
            : [{ key: '', value: '' }];
    },
    { deep: true }
);
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

        <AdvancedSearchForm
            :filter-form="filterForm"
            :bio-filters="bioFilters"
            :available-bio-keys="availableBioKeys"
            :tag-suggestions="tagSuggestions"
            :bio-value-suggestions="bioValueSuggestions"
            context="actors"
            reset-route-name="jav.vue.actors"
            @submit="submitSearch"
            @add-bio-filter="addBioFilter"
            @remove-bio-filter="removeBioFilter"
        />

        <div class="actor-masonry-grid">
            <ActorCard
                v-for="actor in visibleActors"
                :key="actor.id"
                :actor="actor"
                :has-auth-user="hasAuthUser"
                :liked="Boolean(actor.is_liked)"
                :actor-age="resolveActorAge(actor)"
                :age-filter-active="isAgeFilterActive(resolveActorAge(actor))"
                :actor-rate="formatRate(resolveActorRate(actor))"
                :actor-star-count="actorStarCount(actor)"
                @filter-age="filterByAge"
            />

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
.actor-masonry-grid {
    column-count: 1;
    column-gap: 1rem;
}

.actor-masonry-grid > .ui-col,
.actor-masonry-grid > .ui-col-12 {
    break-inside: avoid;
    margin-bottom: 1rem;
}

.actor-masonry-grid > .ui-col-12 {
    column-span: all;
}

@media (min-width: 768px) {
    .actor-masonry-grid {
        column-count: 4;
    }
}
</style>
