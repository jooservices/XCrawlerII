<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';
import { useUIStore } from '@jav/Stores/ui';
import BaseCard from '@jav/Components/BaseCard.vue';

const props = defineProps({
    item: Object,
    activeTags: {
        type: Array,
        default: () => [],
    },
    recommendationReasons: {
        type: Object,
        default: () => ({
            actors: [],
            tags: [],
        }),
    },
});
const page = usePage();
const uiStore = useUIStore();
const hasAuthUser = computed(() => Boolean(page.props.auth?.user));
const canManageCurations = computed(() => {
    if (!hasAuthUser.value) {
        return false;
    }

    const roles = page.props.auth?.user?.roles;
    if (!Array.isArray(roles)) {
        return false;
    }

    return roles.includes('admin');
});
const preferences = computed(() => page.props.auth?.user?.preferences || {});
const textPreference = computed(() => preferences.value.text_preference || 'detailed');

// Helper for date formatting
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

// Helper for fallback image
const handleImageError = (e) => {
    e.target.src = 'https://placehold.co/300x400?text=No+Image';
};

// Resolvers for Actor/Tag names (handling object or string variants from backend)
const resolveName = (obj) => {
    if (!obj) return '';
    if (typeof obj === 'string') return obj;
    return obj.name || '';
};
const resolveActorIdentifier = (actor) => {
    if (!actor || typeof actor === 'string') {
        return null;
    }

    return actor.uuid || actor.id || null;
};
const actorLink = (actor) => {
    const identifier = resolveActorIdentifier(actor);
    if (identifier) {
        return route('jav.vue.actors.bio', identifier);
    }

    return route('jav.vue.dashboard', { actor: resolveName(actor) });
};
const titleText = computed(() => {
    const title = String(props.item?.title || '');
    if (textPreference.value !== 'concise') {
        return title;
    }
    return title.length > 45 ? `${title.slice(0, 45)}...` : title;
});
const descriptionText = computed(() => {
    const description = String(props.item?.description || 'No description available.');
    if (textPreference.value !== 'concise') {
        return description;
    }
    return description.length > 120 ? `${description.slice(0, 120)}...` : description;
});
const normalizeTagLabel = (value) => {
    return String(value || '')
        .toLowerCase()
        .trim()
    .replaceAll(/[^a-z0-9]+/g, ' ')
    .replaceAll(/\s+/g, ' ');
};
const normalizedActiveTags = computed(() => {
    return [...new Set(
        (props.activeTags || [])
            .map((tag) => normalizeTagLabel(tag))
            .filter((tag) => tag !== '')
    )];
});
const reasonActors = computed(() => {
    const actors = props.recommendationReasons?.actors;
    return Array.isArray(actors) ? actors.filter((name) => String(name || '').trim() !== '') : [];
});
const reasonTags = computed(() => {
    const tags = props.recommendationReasons?.tags;
    return Array.isArray(tags) ? tags.filter((name) => String(name || '').trim() !== '') : [];
});
const hasRecommendationReasons = computed(() => {
    return reasonActors.value.length > 0 || reasonTags.value.length > 0;
});
const reasonActorsToShow = computed(() => reasonActors.value.slice(0, 1));
const reasonTagsToShow = computed(() => reasonTags.value.slice(0, 1));
const movieActors = computed(() => (Array.isArray(props.item?.actors) ? props.item.actors : []));
const movieTags = computed(() => (Array.isArray(props.item?.tags) ? props.item.tags : []));
const visibleActors = computed(() => movieActors.value);
const visibleTags = computed(() => movieTags.value);
const activeTagSet = computed(() => {
    return new Set(normalizedActiveTags.value);
});
const isActiveTag = (tag) => {
    const normalizedTag = normalizeTagLabel(resolveName(tag));
    if (!normalizedTag) {
        return false;
    }

    if (activeTagSet.value.has(normalizedTag)) {
        return true;
    }

    return normalizedActiveTags.value.some((selectedTag) => {
        return normalizedTag.includes(selectedTag) || selectedTag.includes(normalizedTag);
    });
};

const downloadRoute = computed(() => {
    return route('jav.movies.download', props.item.uuid || props.item.id);
});

const detailRoute = computed(() => {
    return route('jav.vue.movies.show', props.item.uuid || props.item.id);
});

const localIsLiked = ref(props.item.is_liked);
const localInWatchlist = ref(props.item.in_watchlist);
const localWatchlistId = ref(props.item.watchlist_id);
const localUserRating = ref(props.item.user_rating || 0);
const localUserRatingId = ref(props.item.user_rating_id);
const localIsFeatured = ref(Boolean(props.item?.is_featured));
const localFeaturedCurationUuid = ref(props.item?.featured_curation_uuid || null);
const isProcessing = ref(false);
const isWatchlistProcessing = ref(false);
const ratingProcessing = ref(false);
const featuredProcessing = ref(false);

const toggleLike = async () => {
    if (isProcessing.value) return;
    isProcessing.value = true;

    try {
        const response = await axios.post(route('jav.api.toggle-like'), {
            id: props.item.id,
            type: 'jav'
        });

        if (response.data.success) {
            localIsLiked.value = response.data.liked;
            uiStore.showToast(
                response.data.liked ? 'Added to favorites' : 'Removed from favorites',
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

const toggleWatchlist = async () => {
    if (!hasAuthUser.value || isWatchlistProcessing.value) return;
    isWatchlistProcessing.value = true;

    try {
        if (localInWatchlist.value && localWatchlistId.value) {
            const response = await axios.delete(route('jav.api.watchlist.destroy', localWatchlistId.value));
            if (response.data?.success) {
                localInWatchlist.value = false;
                localWatchlistId.value = null;
                uiStore.showToast('Removed from watchlist', 'success');
            }
        } else {
            const response = await axios.post(route('jav.api.watchlist.store'), {
                jav_id: props.item.id,
                status: 'to_watch',
            });
            if (response.data?.success) {
                localInWatchlist.value = true;
                localWatchlistId.value = response.data?.watchlist?.id || null;
                uiStore.showToast('Added to watchlist', 'success');
            }
        }
    } catch (error) {
        console.error(error);
        uiStore.showToast('Failed to update watchlist', 'error');
    } finally {
        isWatchlistProcessing.value = false;
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
        jav_id: props.item.id,
        rating,
    });

    applyRatingFromResponse(response, rating, 'Rating saved');
};

const resolveDuplicateRatingAndUpdate = async (storeError, rating) => {
    const message = String(storeError?.response?.data?.message || '');
    if (!message.toLowerCase().includes('already rated')) {
        throw storeError;
    }

    const checkResponse = await axios.get(route('jav.api.ratings.check', props.item.id));
    const existingId = checkResponse?.data?.id || null;
    if (!existingId) {
        throw storeError;
    }

    await updateExistingRating(existingId, rating);
};

const rate = async (rating) => {
    if (!hasAuthUser.value || ratingProcessing.value) return;
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

const findFeaturedCurationUuid = async () => {
    if (localFeaturedCurationUuid.value) {
        return localFeaturedCurationUuid.value;
    }

    const response = await axios.get(route('api.curations.index'), {
        params: {
            curation_type: 'featured',
            item_type: 'jav',
            item_id: props.item.id,
            active: true,
            per_page: 1,
        },
    });

    const uuid = response?.data?.data?.[0]?.uuid || null;
    localFeaturedCurationUuid.value = uuid;

    return uuid;
};

const toggleFeatured = async () => {
    if (!canManageCurations.value || featuredProcessing.value) {
        return;
    }

    featuredProcessing.value = true;
    const previousValue = localIsFeatured.value;
    const previousUuid = localFeaturedCurationUuid.value;
    const nextValue = !previousValue;
    localIsFeatured.value = nextValue;

    try {
        if (nextValue) {
            const response = await axios.post(route('api.curations.store'), {
                curation_type: 'featured',
                item_type: 'jav',
                item_id: props.item.id,
            });

            localFeaturedCurationUuid.value = response?.data?.data?.uuid || null;
            uiStore.showToast('Movie marked as featured', 'success');
        } else {
            const uuid = await findFeaturedCurationUuid();
            if (!uuid) {
                throw new Error('Featured curation not found.');
            }

            await axios.delete(route('api.curations.destroy', uuid));
            localFeaturedCurationUuid.value = null;
            uiStore.showToast('Movie removed from featured', 'success');
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
        src: props.item?.cover || '',
        alt: props.item?.code || '',
        href: detailRoute.value,
        className: 'ui-card-img-top u-h-300 u-object-cover',
        onError: handleImageError,
    };
});

const cornerEnd = computed(() => {
    const views = Number(props.item?.views ?? 0);
    const downloads = Number(props.item?.downloads ?? 0);

    return [
        {
            key: `views-${props.item?.id || 'movie'}`,
            icon: 'fas fa-eye',
            text: String(views),
            tooltip: `Views: ${views}`,
        },
        {
            key: `downloads-${props.item?.id || 'movie'}`,
            icon: 'fas fa-download',
            text: String(downloads),
            tooltip: `Downloads: ${downloads}`,
        },
    ];
});

const heading = computed(() => {
    const dateText = props.item?.date ? formatDate(props.item.date) : '';

    return {
        code: props.item?.formatted_code || props.item?.code || '',
        codeHref: detailRoute.value,
        date: dateText,
        dateTitle: dateText ? `Release date: ${dateText}` : '',
        title: titleText.value,
    };
});

const meta = computed(() => {
    if (!props.item?.size) {
        return [];
    }

    return [
        {
            key: `size-${props.item?.id || 'movie'}`,
            text: `${props.item.size} GB`,
            className: 'base-card-tone-muted',
        },
    ];
});

const groupTop = computed(() => {
    const actorItems = reasonActorsToShow.value.map((actorName) => ({
        key: `reason-actor-${props.item?.id || 'movie'}-${actorName}`,
        text: `Because you liked actor: ${actorName}`,
        className: 'base-card-tone-positive',
    }));
    const tagItems = reasonTagsToShow.value.map((tagName) => ({
        key: `reason-tag-${props.item?.id || 'movie'}-${tagName}`,
        text: `Because you liked tag: ${tagName}`,
        className: 'base-card-tone-info',
    }));

    return [...actorItems, ...tagItems];
});

const groupA = computed(() => {
    return visibleActors.value.map((actor, index) => ({
        key: `actor-${props.item?.id || 'movie'}-${index}`,
        text: resolveName(actor),
        href: actorLink(actor),
        className: 'base-card-tone-positive',
    }));
});

const groupB = computed(() => {
    return visibleTags.value.map((tag, index) => ({
        key: `tag-${props.item?.id || 'movie'}-${index}`,
        text: resolveName(tag),
        href: route('jav.vue.dashboard', { tag: resolveName(tag) }),
        className: isActiveTag(tag) ? 'base-card-tone-active' : 'base-card-tone-info',
    }));
});

const primaryAction = computed(() => {
    if (hasAuthUser.value) {
        return {
            href: downloadRoute.value,
            label: props.item?.size ? `Download (${props.item.size} GB)` : 'Download',
            icon: 'fas fa-download',
            title: props.item?.size ? `Download torrent (${props.item.size} GB)` : 'Download torrent',
            className: 'ui-btn-primary download-btn',
            native: true,
        };
    }

    return {
        href: route('jav.vue.login'),
        label: 'Login to Download',
        icon: 'fas fa-right-to-bracket',
        title: 'Login is required before downloading',
        className: 'ui-btn-outline-secondary download-btn',
        native: false,
    };
});

const tools = computed(() => {
    if (!hasAuthUser.value) {
        return [];
    }

    const result = [
        {
            key: `like-${props.item?.id || 'movie'}`,
            className: localIsLiked.value ? 'ui-btn-danger' : 'ui-btn-outline-danger',
            iconClass: localIsLiked.value ? 'fas fa-heart' : 'far fa-heart',
            disabled: isProcessing.value,
            title: localIsLiked.value ? 'Remove from favorites' : 'Add to favorites',
            onClick: toggleLike,
        },
        {
            key: `watchlist-${props.item?.id || 'movie'}`,
            className: localInWatchlist.value ? 'ui-btn-warning' : 'ui-btn-outline-warning',
            iconClass: localInWatchlist.value ? 'fas fa-bookmark' : 'far fa-bookmark',
            disabled: isWatchlistProcessing.value,
            title: localInWatchlist.value ? 'Remove from watchlist' : 'Add to watchlist',
            onClick: toggleWatchlist,
        },
    ];

    if (canManageCurations.value) {
        result.push({
            key: `featured-${props.item?.id || 'movie'}`,
            className: localIsFeatured.value ? 'ui-btn-primary' : 'ui-btn-outline-primary',
            iconClass: localIsFeatured.value ? 'fas fa-star' : 'far fa-star',
            disabled: featuredProcessing.value,
            title: localIsFeatured.value ? 'Remove from featured list' : 'Add to featured list',
            onClick: toggleFeatured,
        });
    }

    result.push({
        key: `rating-${props.item?.id || 'movie'}`,
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
        showDivider: true,
        text: descriptionText.value,
    };
});
</script>

<template>
    <div class="ui-col">
        <BaseCard
            mode="structured"
            card-class="u-shadow-sm movie-card"
            body-class="movie-card-body"
            :data-uuid="item.uuid"
            :cover="cover"
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

