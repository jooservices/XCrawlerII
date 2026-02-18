<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();

const context = ref('movies');
const advancedOpen = ref(false);

const currentPageType = computed(() => {
    if (route().current('jav.vue.actors*')) {
        return 'actors';
    }
    if (route().current('jav.vue.tags*')) {
        return 'tags';
    }
    return 'movies';
});

onMounted(() => {
    context.value = currentPageType.value;
});

const isMoviesContext = computed(() => context.value === 'movies');
const isActorsContext = computed(() => context.value === 'actors');
const isTagsContext = computed(() => context.value === 'tags');

const props = computed(() => page.props);

const filterForm = ref({
    q: '',
    actor: '',
    tag: '',
    tags_mode: 'any',
    age: '',
    age_min: '',
    age_max: '',
});

const bioFilters = ref([{ key: '', value: '' }]);

watch(() => props.value, (newProps) => {
    const pageType = currentPageType.value;

    if (context.value !== pageType) {
        return;
    }

    filterForm.value.q = newProps.query || '';

    if (pageType === 'tags') {
        return;
    }

    filterForm.value.actor = newProps.filters?.actor || '';
    filterForm.value.tag = newProps.tagsInput || newProps.filters?.tag || '';
    filterForm.value.tags_mode = newProps.filters?.tags_mode || 'any';
    filterForm.value.age = newProps.filters?.age ?? '';
    filterForm.value.age_min = newProps.filters?.age_min ?? '';
    filterForm.value.age_max = newProps.filters?.age_max ?? '';

    bioFilters.value = (newProps.filters?.bio_filters && newProps.filters.bio_filters.length > 0)
        ? newProps.filters.bio_filters.map((row) => ({
            key: row?.key || '',
            value: row?.value || '',
        }))
        : [{ key: '', value: '' }];
}, { deep: true, immediate: true });

const actorSuggestions = computed(() => props.value.actorSuggestions || []);
const tagSuggestions = computed(() => props.value.tagSuggestions || []);
const availableBioKeys = computed(() => props.value.availableBioKeys || {});
const bioValueSuggestions = computed(() => props.value.bioValueSuggestions || {});

const bioValueListId = (bioKey) => {
    const normalized = String(bioKey || '').trim().toLowerCase().replace(/\s+/g, '_');
    return Object.prototype.hasOwnProperty.call(bioValueSuggestions.value || {}, normalized)
        ? `bio-values-${normalized}`
        : 'bio-values-all';
};

const processedTags = computed(() => {
    return String(filterForm.value.tag || '')
        .split(',')
        .map((value) => value.trim())
        .filter((value) => value !== '');
});

const filteredBioFilters = computed(() => {
    return bioFilters.value.filter((row) => row.key || row.value);
});

const addBioFilter = () => {
    bioFilters.value.push({ key: '', value: '' });
};

const removeBioFilter = (index) => {
    if (bioFilters.value.length <= 1) {
        return;
    }
    bioFilters.value.splice(index, 1);
};

const formRoute = computed(() => {
    if (isActorsContext.value) {
        return 'jav.vue.actors';
    }
    if (isTagsContext.value) {
        return 'jav.vue.tags';
    }
    return 'jav.vue.dashboard';
});

const submitSearch = () => {
    if (isTagsContext.value) {
        router.get(route(formRoute.value), { q: filterForm.value.q || '' }, {
            preserveScroll: true,
            preserveState: true,
        });
        return;
    }

    const params = {
        q: filterForm.value.q || '',
        tag: filterForm.value.tag || '',
        tags_mode: filterForm.value.tags_mode || 'any',
        age: filterForm.value.age || '',
        age_min: filterForm.value.age_min || '',
        age_max: filterForm.value.age_max || '',
        bio_filters: filteredBioFilters.value,
        bio_key: filteredBioFilters.value[0]?.key || '',
        bio_value: filteredBioFilters.value[0]?.value || '',
    };

    if (isMoviesContext.value) {
        params.actor = filterForm.value.actor || '';
        params.tags = processedTags.value;
    }

    router.get(route(formRoute.value), params, {
        preserveScroll: true,
        preserveState: true,
    });
};

const resetSearch = () => {
    router.visit(route(formRoute.value));
};

const toggleAdvanced = () => {
    advancedOpen.value = !advancedOpen.value;
};
</script>

<template>
    <div class="sidebar-search-panel ui-card">
        <div class="ui-card-body">
            <div class="u-flex u-justify-between u-items-center mb-2">
                <h6 class="mb-0">Search</h6>
                <button
                    type="button"
                    class="ui-btn ui-btn-xs ui-btn-outline-secondary"
                    :aria-expanded="advancedOpen ? 'true' : 'false'"
                    @click="toggleAdvanced"
                >
                    {{ advancedOpen ? 'Hide' : 'Advanced' }}
                </button>
            </div>

            <div class="ui-btn-group w-100 mb-3" role="group">
                <button
                    type="button"
                    class="ui-btn ui-btn-xs"
                    :class="isMoviesContext ? 'ui-btn-primary' : 'ui-btn-outline-primary'"
                    @click="context = 'movies'"
                >
                    Movies
                </button>
                <button
                    type="button"
                    class="ui-btn ui-btn-xs"
                    :class="isActorsContext ? 'ui-btn-primary' : 'ui-btn-outline-primary'"
                    @click="context = 'actors'"
                >
                    Actors
                </button>
                <button
                    type="button"
                    class="ui-btn ui-btn-xs"
                    :class="isTagsContext ? 'ui-btn-primary' : 'ui-btn-outline-primary'"
                    @click="context = 'tags'"
                >
                    Tags
                </button>
            </div>

            <form @submit.prevent="submitSearch">
                <div class="mb-2">
                    <label class="ui-form-label">Keyword</label>
                    <input v-model="filterForm.q" type="text" class="ui-form-control" placeholder="Search...">
                </div>

                <div v-if="advancedOpen && !isTagsContext" class="advanced-search-block">
                    <div v-if="isMoviesContext" class="mb-2">
                        <label class="ui-form-label">Actor</label>
                        <input v-model="filterForm.actor" type="text" list="gs-actor-suggestions" class="ui-form-control" placeholder="Actor Name">
                        <datalist id="gs-actor-suggestions">
                            <option v-for="actorName in actorSuggestions" :key="`gs-actor-${actorName}`" :value="actorName"></option>
                        </datalist>
                    </div>

                    <div class="mb-2">
                        <label class="ui-form-label">Tags</label>
                        <input v-model="filterForm.tag" type="text" list="gs-tag-suggestions" class="ui-form-control" placeholder="Tag A, Tag B">
                        <datalist id="gs-tag-suggestions">
                            <option v-for="tagName in tagSuggestions" :key="`gs-tag-${tagName}`" :value="tagName"></option>
                        </datalist>
                    </div>

                    <div class="mb-2">
                        <label class="ui-form-label">Tags Mode</label>
                        <select v-model="filterForm.tags_mode" class="ui-form-select">
                            <option value="any">Match Any</option>
                            <option value="all">Match All</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="ui-form-label">Age</label>
                        <div class="ui-input-group">
                            <input v-model="filterForm.age_min" type="number" class="ui-form-control" placeholder="Min">
                            <span class="ui-input-group-text">-</span>
                            <input v-model="filterForm.age_max" type="number" class="ui-form-control" placeholder="Max">
                        </div>
                        <div class="mt-1">
                            <input v-model="filterForm.age" type="number" class="ui-form-control" placeholder="Exact Age">
                        </div>
                    </div>

                    <div class="mb-2">
                        <div class="u-flex u-justify-between u-items-center mb-1">
                            <label class="ui-form-label mb-0">Bio Filters</label>
                            <button type="button" class="ui-btn ui-btn-xs ui-btn-outline-primary" @click="addBioFilter">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>

                        <div v-for="(bioFilter, index) in bioFilters" :key="`gs-bio-${index}`" class="bio-filter-item mb-2 p-2 border rounded">
                            <div class="mb-2">
                                <input
                                    v-model="bioFilter.key"
                                    type="text"
                                    class="ui-form-control ui-form-control-sm mb-1"
                                    placeholder="Key (e.g. blood)"
                                    list="gs-bio-keys"
                                >
                                <input
                                    v-model="bioFilter.value"
                                    type="text"
                                    class="ui-form-control ui-form-control-sm"
                                    placeholder="Value (e.g. A)"
                                    :list="bioValueListId(bioFilter.key)"
                                >
                            </div>
                            <div class="text-end">
                                <button
                                    type="button"
                                    class="ui-btn ui-btn-xs ui-btn-ghost-danger"
                                    @click="removeBioFilter(index)"
                                    :disabled="bioFilters.length === 1 && index === 0"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <datalist id="gs-bio-keys">
                            <option v-for="(label, key) in availableBioKeys" :key="`gs-bio-key-${key}`" :value="key">{{ label }}</option>
                        </datalist>

                        <datalist id="bio-values-all">
                            <template v-for="(valueList, bioKey) in bioValueSuggestions" :key="`gs-bio-values-all-${bioKey}`">
                                <option v-for="value in valueList" :key="`gs-bio-values-all-${bioKey}-${value}`" :value="value"></option>
                            </template>
                        </datalist>

                        <datalist v-for="(valueList, bioKey) in bioValueSuggestions" :id="`bio-values-${bioKey}`" :key="`gs-bio-values-${bioKey}`">
                            <option v-for="value in valueList" :key="`gs-bio-values-${bioKey}-${value}`" :value="value"></option>
                        </datalist>
                    </div>
                </div>

                <div v-else-if="advancedOpen && isTagsContext" class="u-text-muted small mb-2">
                    Tags search uses the keyword field only.
                </div>

                <div class="d-grid gap-2 mt-3">
                    <button type="submit" class="ui-btn ui-btn-primary w-100">
                        <i class="fas fa-search mr-1"></i> Search
                    </button>
                    <button type="button" class="ui-btn ui-btn-outline-secondary w-100" @click="resetSearch">
                        <i class="fas fa-rotate-left mr-1"></i> Reset
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.sidebar-search-panel {
    background-color: var(--surface-card);
    border: 1px solid var(--border);
}
.bio-filter-item {
    background-color: var(--surface-ground);
    border-color: var(--border);
}
</style>
