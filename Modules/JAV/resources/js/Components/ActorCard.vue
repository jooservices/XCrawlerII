<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import { useUIStore } from '@core/Stores/ui';
import BaseCard from '@jav/Components/BaseCard.vue';

const emit = defineEmits(['filter-age']);

const props = defineProps({
    actor: {
        type: Object,
        required: true,
    },
    hasAuthUser: {
        type: Boolean,
        default: false,
    },
    liked: {
        type: Boolean,
        default: false,
    },
    likeProcessing: {
        type: Boolean,
        default: false,
    },
    actorAge: {
        type: Number,
        default: null,
    },
    ageFilterActive: {
        type: Boolean,
        default: false,
    },
    actorRate: {
        type: [String, Number],
        default: null,
    },
    actorStarCount: {
        type: Number,
        default: 0,
    },
});

const page = usePage();
const uiStore = useUIStore();
// Featured only for admin; match backend HandleInertiaRequests (roles = pluck('slug')->values())
const canManageCurations = computed(() => {
    if (!props.hasAuthUser) return false;
    const raw = page.props.auth?.user?.roles;
    if (raw == null) return false;
    let roles = [];
    if (Array.isArray(raw)) {
        roles = raw;
    } else if (typeof raw === 'object' && raw !== null) {
        roles = Object.values(raw);
    }
    return roles.some((r) => r === 'admin' || (r && (r.slug === 'admin' || r.name === 'admin')));
});

const detailRoute = computed(() => route('jav.vue.actors.bio', props.actor.uuid || props.actor.id));

const formatCount = (value) => {
    const number = Number(value || 0);
    return Number.isFinite(number) ? number.toLocaleString() : '0';
};

// Release date and age (same position as movie's release date)
const releaseDateAndAgeText = computed(() => {
    const parts = [];
    const birthDate = props.actor.birth_date || props.actor.xcity_birth_date;
    if (birthDate) {
        const d = new Date(birthDate);
        if (!Number.isNaN(d.getTime())) {
            parts.push(d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }));
        }
    }
    if (props.actorAge != null && props.actorAge > 0) {
        parts.push(parts.length ? `· ${props.actorAge}` : `Age ${props.actorAge}`);
    }
    return parts.length ? parts.join(' ') : '';
});

// Bio lines: one per attribute, max 4. Each item is { key, value }.
const bioLines = computed(() => {
    const lines = [];
    const seen = new Set();
    const add = (key, value) => {
        if (value && String(value).trim() && !seen.has(key)) {
            seen.add(key);
            lines.push({ key, value: String(value).trim() });
        }
    };
    const a = props.actor;
    add('Height', a.xcity_height);
    add('Size', a.xcity_size);
    add('From', a.xcity_city_of_birth);
    add('Blood', a.xcity_blood_type);
    add('Hobby', a.xcity_hobby);
    add('Skill', a.xcity_special_skill);
    add('Other', a.xcity_other);
    if (a.xcity_profile && typeof a.xcity_profile === 'object') {
        const p = a.xcity_profile;
        add('Rate', p.rate ? `${p.rate}/5` : '');
        add('From', p.birth_place);
        add('Height', p.height);
        add('Size', p.size);
        add('Hobby', p.hobby);
    }
    return lines.slice(0, 4);
});

const bioSummaryText = computed(() => {
    if (bioLines.value.length === 0) {
        return 'No profile details available.';
    }

    return bioLines.value.map((line) => `${line.key}: ${line.value}`).join(' • ');
});

const localIsLiked = ref(props.liked);
const localIsFeatured = ref(Boolean(props.actor?.is_featured));
const localFeaturedCurationUuid = ref(props.actor?.featured_curation_uuid || null);
const isProcessing = ref(props.likeProcessing);
const featuredProcessing = ref(false);

watch(() => props.liked, (v) => { localIsLiked.value = v; });
watch(() => props.likeProcessing, (v) => { isProcessing.value = v; });

const toggleLike = async () => {
    if (isProcessing.value) return;
    isProcessing.value = true;
    try {
        const response = await axios.post(route('jav.api.toggle-like'), {
            id: props.actor.id,
            type: 'actor',
        });
        if (response.data?.success) {
            localIsLiked.value = response.data.liked;
            uiStore.showToast(
                response.data.liked ? 'Added actor to favorites' : 'Removed from favorites',
                'success'
            );
        }
    } catch (error) {
        console.error(error);
        uiStore.showToast('Failed to update favorite status', 'error');
    } finally {
        isProcessing.value = false;
    }
};

const findFeaturedCurationUuid = async () => {
    if (localFeaturedCurationUuid.value) return localFeaturedCurationUuid.value;
    const response = await axios.get(route('api.curations.index'), {
        params: {
            curation_type: 'featured',
            item_type: 'actor',
            item_id: props.actor.id,
            active: true,
            per_page: 1,
        },
    });
    const uuid = response?.data?.data?.[0]?.uuid || null;
    localFeaturedCurationUuid.value = uuid;
    return uuid;
};

const toggleFeatured = async () => {
    if (!canManageCurations.value || featuredProcessing.value) return;
    featuredProcessing.value = true;
    const previousValue = localIsFeatured.value;
    const previousUuid = localFeaturedCurationUuid.value;
    const nextValue = !previousValue;
    localIsFeatured.value = nextValue;
    try {
        if (nextValue) {
            const response = await axios.post(route('api.curations.store'), {
                curation_type: 'featured',
                item_type: 'actor',
                item_id: props.actor.id,
            });
            const ok = response?.data?.success === true && response?.data?.data?.uuid;
            if (!ok) {
                throw new Error(response?.data?.message || 'Save failed');
            }
            localFeaturedCurationUuid.value = response.data.data.uuid;
            uiStore.showToast('Actor marked as featured', 'success');
        } else {
            const uuid = await findFeaturedCurationUuid();
            if (!uuid) {
                throw new Error('Featured curation not found.');
            }
            const response = await axios.delete(route('api.curations.destroy', uuid));
            if (response?.data?.success !== true) {
                throw new Error(response?.data?.message || 'Remove failed');
            }
            localFeaturedCurationUuid.value = null;
            uiStore.showToast('Actor removed from featured', 'success');
        }
    } catch (error) {
        console.error(error);
        localIsFeatured.value = previousValue;
        localFeaturedCurationUuid.value = previousUuid;
        uiStore.showToast('Failed to update featured state', 'error');
    } finally {
        featuredProcessing.value = false;
    }
};

const cover = computed(() => {
    return {
        src: props.actor?.cover || '',
        alt: props.actor?.name || 'Actor',
        href: detailRoute.value,
        className: 'ui-card-img-top u-h-300 u-object-cover',
        onError: (event) => {
            event.target.src = 'https://placehold.co/300x400?text=No+Image';
        },
    };
});

const cornerStart = computed(() => {
    const items = [
        {
            key: `actor-javs-${props.actor?.id || 'actor'}`,
            icon: 'fas fa-film',
            text: `${formatCount(props.actor?.javs_count || 0)} JAVs`,
            tooltip: `${formatCount(props.actor?.javs_count || 0)} JAVs`,
        },
    ];

    if (props.actorAge !== null) {
        items.push({
            key: `actor-age-${props.actor?.id || 'actor'}`,
            icon: 'fas fa-user-clock',
            text: `${props.actorAge}`,
            tooltip: props.ageFilterActive
                ? `Active age filter: ${props.actorAge}`
                : `Age: ${props.actorAge}`,
        });
    }

    return items;
});

const cornerEnd = computed(() => {
    const items = [
        {
            key: `actor-favorites-${props.actor?.id || 'actor'}`,
            icon: 'fas fa-heart',
            text: formatCount(props.actor?.favorites_count || 0),
            tooltip: `Favorites: ${formatCount(props.actor?.favorites_count || 0)}`,
        },
        {
            key: `actor-views-${props.actor?.id || 'actor'}`,
            icon: 'fas fa-eye',
            text: formatCount(props.actor?.jav_views || 0),
            tooltip: `Views: ${formatCount(props.actor?.jav_views || 0)}`,
        },
    ];

    if (props.actorRate !== null) {
        items.push({
            key: `actor-rate-${props.actor?.id || 'actor'}`,
            icon: 'fas fa-star',
            text: String(props.actorRate),
            tooltip: `Average rate: ${props.actorRate} of 5`,
        });
    }

    return items;
});

const heading = computed(() => {
    return {
        code: props.actor?.name || '',
        codeHref: detailRoute.value,
        date: releaseDateAndAgeText.value,
        dateTitle: releaseDateAndAgeText.value,
        title: '',
    };
});

const meta = computed(() => {
    if (props.actorRate === null) {
        return [];
    }

    return [
        {
            key: `actor-meta-rate-${props.actor?.id || 'actor'}`,
            text: `Rate ${props.actorRate}/5`,
            className: 'base-card-tone-muted',
        },
    ];
});

const groupTop = computed(() => []);

const groupA = computed(() => []);

const groupB = computed(() => []);

const primaryAction = computed(() => {
    return {
        href: detailRoute.value,
        label: 'View Profile',
        icon: 'fas fa-user',
        title: 'Open actor profile',
        className: 'ui-btn-outline-secondary',
        native: false,
    };
});

const ageTool = () => {
    if (props.actorAge === null) {
        return [];
    }

    return [{
        key: `actor-age-tool-${props.actor?.id || 'actor'}`,
        className: props.ageFilterActive ? 'ui-btn-primary' : 'ui-btn-outline-primary',
        iconClass: 'fas fa-user-clock',
        disabled: false,
        title: props.ageFilterActive ? `Clear age ${props.actorAge} filter` : `Filter by age ${props.actorAge}`,
        onClick: () => emit('filter-age', props.actorAge),
    }];
};

const authTools = () => {
    if (!props.hasAuthUser) {
        return [];
    }

    const result = [{
        key: `actor-like-${props.actor?.id || 'actor'}`,
        className: localIsLiked.value ? 'ui-btn-danger' : 'ui-btn-outline-danger',
        iconClass: localIsLiked.value ? 'fas fa-heart' : 'far fa-heart',
        disabled: isProcessing.value,
        title: localIsLiked.value ? 'Remove from favorites' : 'Add to favorites',
        onClick: toggleLike,
    }];

    if (canManageCurations.value) {
        result.push({
            key: `actor-featured-${props.actor?.id || 'actor'}`,
            className: localIsFeatured.value ? 'ui-btn-primary' : 'ui-btn-outline-primary',
            iconClass: localIsFeatured.value ? 'fas fa-star' : 'far fa-star',
            disabled: featuredProcessing.value,
            title: localIsFeatured.value ? 'Remove from featured list' : 'Add to featured list',
            onClick: toggleFeatured,
        });
    }

    result.push({
        key: `actor-rating-${props.actor?.id || 'actor'}`,
        kind: 'rating',
        value: Number(props.actorStarCount || 0),
        max: 5,
        disabled: true,
        title: props.actorRate === null ? 'No rating yet' : `Average rating ${props.actorRate}/5`,
    });

    return result;
};

const tools = computed(() => {
    return [...ageTool(), ...authTools()];
});

const summary = computed(() => {
    const lines = bioLines.value.map((line, index) => ({
        key: `bio-summary-${props.actor?.id || 'actor'}-${index}`,
        label: line.key,
        value: line.value,
    }));

    return {
        showDivider: true,
        text: lines.length === 0 ? bioSummaryText.value : '',
        lines,
    };
});
</script>

<template>
    <div class="ui-col">
        <BaseCard
            mode="structured"
            card-class="u-shadow-sm actor-card"
            body-class="actor-card-body"
            :data-uuid="actor.uuid"
            :cover="cover"
            :corner-start="cornerStart"
            :corner-end="cornerEnd"
            :heading="heading"
            :group-top="groupTop"
            :meta="meta"
            :group-a="groupA"
            :group-b="groupB"
            :primary-action="primaryAction"
            :tools="tools"
            :summary="summary"
        />
    </div>
</template>
