<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import { useUIStore } from '@core/Stores/ui';
import BaseCard from '@jav/Components/BaseCard.vue';

const props = defineProps({
    tag: {
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
    tagRate: {
        type: [String, Number],
        default: null,
    },
    tagStarCount: {
        type: Number,
        default: 0,
    },
});

const page = usePage();
const uiStore = useUIStore();
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

    return roles.some((role) => role === 'admin' || (role && (role.slug === 'admin' || role.name === 'admin')));
});

const detailRoute = computed(() => route('jav.vue.dashboard', { tag: props.tag.name }));

const formatCount = (value) => {
    const number = Number(value || 0);
    return Number.isFinite(number) ? number.toLocaleString() : '0';
};

const localIsLiked = ref(props.liked);
const localIsFeatured = ref(Boolean(props.tag?.is_featured));
const localFeaturedCurationUuid = ref(props.tag?.featured_curation_uuid || null);
const localUserRating = ref(Number(props.tag?.user_rating || 0));
const localUserRatingId = ref(props.tag?.user_rating_id || null);
const isProcessing = ref(props.likeProcessing);
const featuredProcessing = ref(false);
const ratingProcessing = ref(false);

watch(() => props.liked, (value) => {
    localIsLiked.value = value;
});
watch(() => props.likeProcessing, (value) => {
    isProcessing.value = value;
});

const toggleLike = async () => {
    if (isProcessing.value) return;
    isProcessing.value = true;

    try {
        const response = await axios.post(route('jav.api.toggle-like'), {
            id: props.tag.id,
            type: 'tag',
        });

        if (response.data?.success) {
            localIsLiked.value = response.data.liked;
            uiStore.showToast(
                response.data.liked ? 'Added tag to favorites' : 'Removed tag from favorites',
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
            item_type: 'tag',
            item_id: props.tag.id,
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
                item_type: 'tag',
                item_id: props.tag.id,
            });

            const ok = response?.data?.success === true && response?.data?.data?.uuid;
            if (!ok) {
                throw new Error(response?.data?.message || 'Save failed');
            }

            localFeaturedCurationUuid.value = response.data.data.uuid;
            uiStore.showToast('Tag marked as featured', 'success');
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
            uiStore.showToast('Tag removed from featured', 'success');
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

const applyRatingFromResponse = (response, fallbackRating, toastMessage) => {
    if (!response?.data?.success) {
        return;
    }

    localUserRating.value = Number(response.data?.data?.rating || fallbackRating);
    localUserRatingId.value = response.data?.data?.id || localUserRatingId.value;
    uiStore.showToast(toastMessage, 'success');
};

const updateExistingRating = async (ratingId, rating) => {
    const response = await axios.put(route('jav.api.ratings.update', ratingId), {
        rating,
    });

    applyRatingFromResponse(response, rating, 'Rating updated');
};

const createNewRating = async (rating) => {
    const response = await axios.post(route('jav.api.ratings.store'), {
        tag_id: props.tag.id,
        rating,
    });

    applyRatingFromResponse(response, rating, 'Rating saved');
};

const resolveDuplicateRatingAndUpdate = async (storeError, rating) => {
    const message = String(storeError?.response?.data?.message || '');
    if (!message.toLowerCase().includes('already rated')) {
        throw storeError;
    }

    const checkResponse = await axios.get(route('jav.api.ratings.check-tag', props.tag.id));
    const existingId = checkResponse?.data?.id || null;
    if (!existingId) {
        throw storeError;
    }

    await updateExistingRating(existingId, rating);
};

const rate = async (rating) => {
    if (!props.hasAuthUser || ratingProcessing.value) {
        return;
    }

    ratingProcessing.value = true;

    try {
        if (localUserRatingId.value) {
            await updateExistingRating(localUserRatingId.value, rating);
        } else {
            try {
                await createNewRating(rating);
            } catch (storeError) {
                await resolveDuplicateRatingAndUpdate(storeError, rating);
            }
        }
    } catch (error) {
        console.error(error);
        uiStore.showToast('Failed to save rating', 'error');
    } finally {
        ratingProcessing.value = false;
    }
};

const loadExistingUserRating = async () => {
    if (!props.hasAuthUser) {
        return;
    }

    try {
        const response = await axios.get(route('jav.api.ratings.check-tag', props.tag.id));
        if (!response?.data?.has_rated) {
            return;
        }

        localUserRating.value = Number(response.data?.rating || 0);
        localUserRatingId.value = response.data?.id || null;
    } catch (error) {
        console.error(error);
    }
};

onMounted(() => {
    loadExistingUserRating();
});

const cover = computed(() => {
    const source = String(props.tag?.cover || '').trim();
    const fallbackSource = 'https://placehold.co/300x400?text=Tag';

    return {
        src: source === '' ? fallbackSource : source,
        alt: props.tag?.name || 'Tag',
        href: detailRoute.value,
        className: 'ui-card-img-top u-h-300 u-object-cover',
    };
});

const cornerStart = computed(() => {
    return [{
        key: `tag-javs-${props.tag?.id || 'tag'}`,
        icon: 'fas fa-film',
        text: `${formatCount(props.tag?.javs_count || 0)} JAVs`,
        tooltip: `${formatCount(props.tag?.javs_count || 0)} JAVs`,
    }];
});

const cornerEnd = computed(() => []);

const heading = computed(() => {
    return {
        code: props.tag?.name || '',
        codeHref: detailRoute.value,
        date: '',
        dateTitle: '',
        title: '',
    };
});

const meta = computed(() => {
    if (props.tagRate === null) {
        return [];
    }

    return [{
        key: `tag-rate-${props.tag?.id || 'tag'}`,
        text: `Rate ${props.tagRate}/5`,
        className: 'base-card-tone-muted',
    }];
});

const groupTop = computed(() => []);
const groupA = computed(() => []);
const groupB = computed(() => []);

const primaryAction = computed(() => {
    return {
        href: detailRoute.value,
        label: 'Browse Tag',
        icon: 'fas fa-tag',
        title: 'Open tag page',
        className: 'ui-btn-outline-secondary',
        native: false,
    };
});

const tools = computed(() => {
    if (!props.hasAuthUser) {
        return [];
    }

    const result = [{
        key: `tag-like-${props.tag?.id || 'tag'}`,
        className: localIsLiked.value ? 'ui-btn-danger' : 'ui-btn-outline-danger',
        iconClass: localIsLiked.value ? 'fas fa-heart' : 'far fa-heart',
        disabled: isProcessing.value,
        title: localIsLiked.value ? 'Remove from favorites' : 'Add to favorites',
        onClick: toggleLike,
    }];

    if (canManageCurations.value) {
        result.push({
            key: `tag-featured-${props.tag?.id || 'tag'}`,
            className: localIsFeatured.value ? 'ui-btn-primary' : 'ui-btn-outline-primary',
            iconClass: localIsFeatured.value ? 'fas fa-star' : 'far fa-star',
            disabled: featuredProcessing.value,
            title: localIsFeatured.value ? 'Remove from featured list' : 'Add to featured list',
            onClick: toggleFeatured,
        });
    }

    result.push({
        key: `tag-rating-${props.tag?.id || 'tag'}`,
        kind: 'rating',
        value: Number(localUserRating.value || 0),
        max: 5,
        disabled: ratingProcessing.value,
        title: `Current rating: ${localUserRating.value || 0} of 5`,
        onRate: rate,
    });

    return result;
});

const summary = computed(() => {
    return {
        showDivider: false,
        text: '',
        lines: [],
    };
});
</script>

<template>
    <div class="ui-col">
        <BaseCard
            mode="structured"
            card-class="u-shadow-sm tag-card"
            body-class="tag-card-body"
            :data-uuid="tag.uuid || tag.id"
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

