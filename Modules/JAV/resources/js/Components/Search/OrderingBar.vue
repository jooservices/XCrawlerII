<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    builtInPresets: Object,
    preset: String,
    savedPresets: Array,
    savedPresetIndex: Number,
    query: String,
    hasAuthUser: Boolean,
    showSavePreset: Boolean,
    presetName: String,
    sort: String,
    direction: String,
    totalMatches: Number,
    loadedMatches: Number,
    baseRouteName: {
        type: String,
        default: 'jav.vue.dashboard',
    },
    showFoundBadge: {
        type: Boolean,
        default: true,
    },
    showSaveButton: {
        type: Boolean,
        default: true,
    },
    showSaveForm: {
        type: Boolean,
        default: true,
    },
    showPresetSection: {
        type: Boolean,
        default: true,
    },
    showSortSection: {
        type: Boolean,
        default: true,
    },
    options: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits([
    'toggle-save-preset',
    'update:presetName',
    'save-preset',
    'delete-preset',
    'sort-selected',
]);

const sortDropdownRef = ref(null);
const showSortMenu = ref(false);

const defaultSortOptions = [
    { label: 'Date (Newest)', sort: 'created_at', direction: 'desc' },
    { label: 'Date (Oldest)', sort: 'created_at', direction: 'asc' },
    { label: 'Most Viewed', sort: 'views', direction: 'desc' },
    { label: 'Least Viewed', sort: 'views', direction: 'asc' },
    { label: 'Most Downloaded', sort: 'downloads', direction: 'desc' },
    { label: 'Least Downloaded', sort: 'downloads', direction: 'asc' },
];
const sortOptions = computed(() => {
    return Array.isArray(props.options) && props.options.length > 0 ? props.options : defaultSortOptions;
});

const currentSortLabel = computed(() => {
    const current = sortOptions.value.find((opt) => opt.sort === props.sort && opt.direction === props.direction);
    return current ? current.label : 'Date';
});

const toggleSortMenu = () => {
    showSortMenu.value = !showSortMenu.value;
};

const closeSortMenu = () => {
    showSortMenu.value = false;
};

const handleClickOutside = (event) => {
    if (!sortDropdownRef.value) {
        return;
    }

    if (!sortDropdownRef.value.contains(event.target)) {
        closeSortMenu();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="u-flex u-justify-between u-items-center mb-3">
        <span v-if="showFoundBadge" class="ui-badge u-bg-light u-text-dark u-border">
            Found: {{ Number.isFinite(totalMatches) && totalMatches > 0 ? totalMatches : loadedMatches || 0 }}
        </span>

        <button v-if="hasAuthUser && showSaveButton" class="ui-btn ui-btn-outline-success ui-btn-sm" type="button" @click="$emit('toggle-save-preset')">
            <i class="fas fa-save mr-1"></i>Save Current As Preset
        </button>
    </div>

    <div v-if="hasAuthUser && showSaveForm" class="collapse mb-3" :class="{ show: showSavePreset }" id="savePresetBox">
        <div class="ui-card ui-card-body">
            <form class="ui-row ui-g-2 u-items-end" @submit.prevent="$emit('save-preset')">
                <div class="ui-col-md-4">
                    <label class="ui-form-label">Preset Name</label>
                    <input
                        :value="presetName"
                        type="text"
                        class="ui-form-control"
                        maxlength="60"
                        required
                        @input="$emit('update:presetName', $event.target.value)"
                    >
                </div>
                <div class="ui-col-md-3">
                    <button class="ui-btn ui-btn-primary ui-btn-sm u-w-full" type="submit">
                        <i class="fas fa-save mr-1"></i>Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div v-if="showPresetSection || showSortSection" class="ui-card mb-3">
        <div class="ui-card-body">
            <div class="u-flex u-flex-wrap u-justify-between u-items-start gap-2 mb-2">
                <div v-if="showPresetSection" class="u-flex u-flex-wrap gap-2">
                    <Link
                        v-for="(presetLabel, presetKey) in (builtInPresets || {})"
                        :key="`built-in-${presetKey}`"
                        :href="route(baseRouteName, { preset: presetKey, q: query || '' })"
                        class="ui-btn ui-btn-sm"
                        :class="preset === presetKey && (savedPresetIndex === null || savedPresetIndex === undefined) ? 'ui-btn-primary' : 'ui-btn-outline-primary'"
                    >
                        {{ presetLabel }}
                    </Link>
                </div>

                <div v-if="showSortSection" class="u-flex u-items-center gap-2 ml-auto">
                    <div ref="sortDropdownRef" class="ui-btn-group" role="group">
                    <button type="button" class="ui-btn ui-btn-outline-secondary ui-dropdown-toggle ui-btn-sm" :aria-expanded="showSortMenu ? 'true' : 'false'" @click.stop="toggleSortMenu">
                        Sort By: {{ currentSortLabel }}
                    </button>
                    <ul class="ui-dropdown-menu" :class="{ show: showSortMenu }">
                        <li v-for="opt in sortOptions" :key="`sort-${opt.sort}-${opt.direction}`">
                            <a class="ui-dropdown-item" href="#" @click.prevent="$emit('sort-selected', opt); closeSortMenu()">{{ opt.label }}</a>
                        </li>
                    </ul>
                    </div>
                </div>
            </div>

            <div v-if="showPresetSection && savedPresets && savedPresets.length > 0" class="u-flex u-flex-wrap gap-2">
                <template v-for="(saved, index) in savedPresets" :key="`saved-preset-${index}`">
                    <Link
                        :href="route(baseRouteName, { saved_preset: index })"
                        class="ui-btn ui-btn-sm"
                        :class="savedPresetIndex === index ? 'ui-btn-success' : 'ui-btn-outline-success'"
                    >
                        {{ saved?.name || `Preset ${index + 1}` }}
                    </Link>
                    <button type="button" class="ui-btn ui-btn-sm ui-btn-outline-danger" title="Delete preset" @click="$emit('delete-preset', index)">
                        <i class="fas fa-trash"></i>
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>
