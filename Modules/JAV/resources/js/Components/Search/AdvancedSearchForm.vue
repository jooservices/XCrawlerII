<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    filterForm: Object,
    bioFilters: Array,
    availableBioKeys: Object,
    actorSuggestions: Array,
    tagSuggestions: Array,
    bioValueSuggestions: Object,
    context: {
        type: String,
        default: 'movies',
    },
    resetRouteName: {
        type: String,
        default: 'jav.vue.dashboard',
    },
});

defineEmits(['submit', 'add-bio-filter', 'remove-bio-filter']);
const isMoviesContext = computed(() => props.context === 'movies');
const isActorsContext = computed(() => props.context === 'actors');
const showActorField = computed(() => isMoviesContext.value);
const showTagFields = computed(() => isMoviesContext.value || isActorsContext.value);
const showAgeFields = computed(() => isMoviesContext.value || isActorsContext.value);
const showBioFields = computed(() => isMoviesContext.value || isActorsContext.value);

const bioValueListId = (bioKey) => {
    const normalized = String(bioKey || '').trim().toLowerCase().replace(/\s+/g, '_');
    return Object.prototype.hasOwnProperty.call(props.bioValueSuggestions || {}, normalized)
        ? `bio-values-${normalized}`
        : 'bio-values-all';
};
</script>

<template>
    <div class="ui-card mb-3">
        <div class="ui-card-body">
            <form id="advancedSearchForm" class="ui-row ui-g-2 u-items-end" @submit.prevent="$emit('submit')">
                <div class="ui-col-md-3">
                    <label class="ui-form-label">Keyword</label>
                    <input v-model="filterForm.q" type="text" name="q" class="ui-form-control" placeholder="Code, title, description">
                </div>
                <div v-if="showActorField" class="ui-col-md-2">
                    <label class="ui-form-label">Actor</label>
                    <input v-model="filterForm.actor" type="text" name="actor" list="actor-suggestions" class="ui-form-control" placeholder="Name or names, comma-separated">
                </div>
                <div v-if="showTagFields" class="ui-col-md-3">
                    <label class="ui-form-label">Tags (multi)</label>
                    <input v-model="filterForm.tag" type="text" name="tag" list="tag-suggestions" class="ui-form-control" placeholder="Tag A, Tag B, Tag C">
                </div>
                <div v-if="showTagFields" class="ui-col-md-2">
                    <label class="ui-form-label">Tags Mode</label>
                    <select v-model="filterForm.tags_mode" name="tags_mode" class="ui-form-select">
                        <option value="any">Match Any</option>
                        <option value="all">Match All</option>
                    </select>
                </div>
                <div v-if="showAgeFields" class="ui-col-md-2">
                    <label class="ui-form-label">Exact Age</label>
                    <input v-model="filterForm.age" type="number" min="18" max="99" name="age" class="ui-form-control" placeholder="e.g. 25">
                </div>
                <div v-if="showAgeFields" class="ui-col-md-2">
                    <label class="ui-form-label">Age Min</label>
                    <input v-model="filterForm.age_min" type="number" min="18" max="99" name="age_min" class="ui-form-control">
                </div>
                <div v-if="showAgeFields" class="ui-col-md-2">
                    <label class="ui-form-label">Age Max</label>
                    <input v-model="filterForm.age_max" type="number" min="18" max="99" name="age_max" class="ui-form-control">
                </div>
                <div v-if="showBioFields" class="ui-col-md-3">
                    <label class="ui-form-label">Bio Filters</label>
                    <button type="button" class="ui-btn ui-btn-outline-primary u-w-full search-control-btn" @click="$emit('add-bio-filter')">
                        <i class="fas fa-plus mr-1"></i>Add Bio Filter
                    </button>
                </div>
                <div v-if="showBioFields" class="ui-col-md-12">
                    <div id="bioFilterContainer">
                        <div
                            v-for="(bioFilter, bioIndex) in bioFilters"
                            :key="`bio-row-${bioIndex}`"
                            class="ui-row ui-g-2 u-items-end bio-filter-row mb-2"
                        >
                            <div class="ui-col-md-4">
                                <label class="ui-form-label mb-1">Bio Key</label>
                                <input
                                    v-model="bioFilter.key"
                                    type="text"
                                    class="ui-form-control bio-key-input"
                                    :name="`bio_filters[${bioIndex}][key]`"
                                    list="bio-keys"
                                    placeholder="e.g. blood_type"
                                >
                            </div>
                            <div class="ui-col-md-6">
                                <label class="ui-form-label mb-1">Bio Value</label>
                                <input
                                    v-model="bioFilter.value"
                                    type="text"
                                    class="ui-form-control bio-value-input"
                                    :name="`bio_filters[${bioIndex}][value]`"
                                    :list="bioValueListId(bioFilter.key)"
                                    placeholder="e.g. A, Tokyo"
                                >
                            </div>
                            <div class="ui-col-md-2">
                                <button
                                    type="button"
                                    class="ui-btn ui-btn-outline-danger u-w-full remove-bio-filter-btn search-control-btn"
                                    :disabled="bioFilters.length === 1 && bioIndex === 0"
                                    title="Remove bio filter"
                                    aria-label="Remove bio filter"
                                    @click="$emit('remove-bio-filter', bioIndex)"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ui-col-md-2">
                    <button type="submit" class="ui-btn ui-btn-primary u-w-full search-control-btn" title="Apply filters" aria-label="Apply filters">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div class="ui-col-md-2">
                    <Link :href="route(resetRouteName)" class="ui-btn ui-btn-outline-secondary u-w-full search-control-btn" title="Reset filters" aria-label="Reset filters">
                        <i class="fas fa-rotate-left"></i>
                    </Link>
                </div>
            </form>

            <datalist v-if="showActorField" id="actor-suggestions">
                <option v-for="actorName in (actorSuggestions || [])" :key="`actor-suggestion-${actorName}`" :value="actorName"></option>
            </datalist>

            <datalist v-if="showTagFields" id="tag-suggestions">
                <option v-for="tagName in (tagSuggestions || [])" :key="`tag-suggestion-${tagName}`" :value="tagName"></option>
            </datalist>

            <datalist v-if="showBioFields" id="bio-keys">
                <option v-for="(label, key) in (availableBioKeys || {})" :key="`bio-key-${key}`" :value="key">{{ label }}</option>
            </datalist>

            <datalist v-if="showBioFields" id="bio-values-all">
                <template v-for="(valueList, bioKey) in (bioValueSuggestions || {})" :key="`bio-values-all-${bioKey}`">
                    <option v-for="value in valueList" :key="`bio-values-all-${bioKey}-${value}`" :value="value"></option>
                </template>
            </datalist>

            <datalist v-if="showBioFields" v-for="(valueList, bioKey) in (bioValueSuggestions || {})" :id="`bio-values-${bioKey}`" :key="`bio-values-${bioKey}`">
                <option v-for="value in valueList" :key="`bio-values-${bioKey}-${value}`" :value="value"></option>
            </datalist>
        </div>
    </div>
</template>

<style scoped>
.search-control-btn {
    min-height: 2.6rem;
}
</style>
