<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
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
const visibleActors = computed(() => movieActors.value.slice(0, 4));
const visibleTags = computed(() => movieTags.value.slice(0, 4));
const extraActorCount = computed(() => Math.max(0, movieActors.value.length - visibleActors.value.length));
const extraTagCount = computed(() => Math.max(0, movieTags.value.length - visibleTags.value.length));
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

const openDetail = (event) => {
    const target = event.target;
    if (target.closest('a') || target.closest('button')) {
        return;
    }

    router.visit(detailRoute.value);
};

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
</script>

<template>
    <div class="ui-col">
        <BaseCard
            card-class="u-shadow-sm movie-card u-cursor-pointer"
            body-class="movie-card-body"
            :data-uuid="item.uuid"
            @click="openDetail"
        >
            <Link :href="detailRoute" class="u-relative u-block">
                <img
                    :src="item.cover"
                    :alt="item.code"
                    @error="handleImageError"
                    class="ui-card-img-top u-h-300 u-object-cover"
                >
                <div class="u-absolute u-top-0 u-right-0 u-bg-dark u-bg-opacity-75 u-text-white px-2 py-1 m-2 u-rounded">
                    <small
                        class="movie-tooltip-target movie-tooltip-target-bottom"
                        :aria-label="`Views: ${item.views ?? 0}`"
                        :data-tooltip="`Views: ${item.views ?? 0}`"
                    >
                        <i class="fas fa-eye"></i> <span>{{ item.views ?? 0 }}</span>
                    </small>
                    <small
                        class="ml-2 movie-tooltip-target movie-tooltip-target-bottom"
                        :aria-label="`Downloads: ${item.downloads ?? 0}`"
                        :data-tooltip="`Downloads: ${item.downloads ?? 0}`"
                    >
                        <i class="fas fa-download"></i> <span>{{ item.downloads ?? 0 }}</span>
                    </small>
                </div>
            </Link>

            <div class="movie-card-content">
                <div class="movie-card-heading">
                    <div class="movie-card-code-row">
                        <Link :href="detailRoute" class="u-no-underline">
                            <h5 class="ui-card-title u-text-primary mb-1">{{ item.formatted_code || item.code }}</h5>
                        </Link>
                        <small
                            v-if="item.date"
                            class="movie-card-inline-date"
                            :title="`Release date: ${formatDate(item.date)}`"
                        >
                            <i class="fas fa-calendar-alt"></i> {{ formatDate(item.date) }}
                        </small>
                    </div>
                    <p class="ui-card-text movie-card-title-line" :title="item.title">{{ titleText }}</p>
                </div>

                <div class="movie-card-meta">
                    <span v-if="item.size" class="ui-badge movie-card-badge movie-card-badge-meta">{{ item.size }} GB</span>
                </div>

                <div v-if="hasRecommendationReasons" class="movie-card-badge-row movie-card-reasons">
                    <span
                        v-for="actorName in reasonActorsToShow"
                        :key="`reason-actor-${item.id}-${actorName}`"
                        class="ui-badge movie-card-badge movie-card-badge-actor"
                    >
                        Because you liked actor: {{ actorName }}
                    </span>
                    <span
                        v-for="tagName in reasonTagsToShow"
                        :key="`reason-tag-${item.id}-${tagName}`"
                        class="ui-badge movie-card-badge movie-card-badge-tag"
                    >
                        Because you liked tag: {{ tagName }}
                    </span>
                </div>

                <div class="movie-card-badge-row movie-card-actors">
                    <Link
                        v-for="(actor, index) in visibleActors"
                        :key="index"
                        :href="actorLink(actor)"
                        class="ui-badge movie-card-badge movie-card-badge-actor u-no-underline u-z-2 u-relative"
                    >
                        {{ resolveName(actor) }}
                    </Link>
                    <span v-if="extraActorCount > 0" class="ui-badge movie-card-badge movie-card-badge-meta">+{{ extraActorCount }}</span>
                </div>

                <div class="movie-card-badge-row movie-card-tags">
                    <Link
                        v-for="(tag, index) in visibleTags"
                        :key="index"
                        :href="route('jav.vue.dashboard', { tag: resolveName(tag) })"
                        class="ui-badge movie-card-badge u-no-underline u-z-2 u-relative"
                        :class="isActiveTag(tag) ? 'movie-card-badge-tag-active' : 'movie-card-badge-tag'"
                    >
                        {{ resolveName(tag) }}
                    </Link>
                    <span v-if="extraTagCount > 0" class="ui-badge movie-card-badge movie-card-badge-meta">+{{ extraTagCount }}</span>
                </div>

                <div class="movie-card-actions">
                    <div class="u-grid gap-2">
                        <a
                            v-if="hasAuthUser"
                            :href="downloadRoute"
                            class="ui-btn ui-btn-primary ui-btn-sm download-btn"
                            :title="item.size ? `Download torrent (${item.size} GB)` : 'Download torrent'"
                        >
                            <i class="fas fa-download"></i> Download<span v-if="item.size"> ({{ item.size }} GB)</span>
                        </a>
                        <Link
                            v-else
                            :href="route('jav.vue.login')"
                            class="ui-btn ui-btn-outline-secondary ui-btn-sm download-btn"
                            title="Login is required before downloading"
                        >
                            <i class="fas fa-right-to-bracket"></i> Login to Download
                        </Link>
                    </div>

                    <div v-if="hasAuthUser" class="mt-2 u-flex gap-2">
                        <button
                            type="button"
                            class="ui-btn ui-btn-sm u-z-2 u-relative movie-tooltip-target"
                            :class="localIsLiked ? 'ui-btn-danger' : 'ui-btn-outline-danger'"
                            :disabled="isProcessing"
                            :aria-label="localIsLiked ? 'Remove from favorites' : 'Add to favorites'"
                            :data-tooltip="localIsLiked ? 'Remove from favorites' : 'Add to favorites'"
                            @click.prevent="toggleLike"
                        >
                            <i :class="localIsLiked ? 'fas fa-heart' : 'far fa-heart'"></i>
                        </button>
                        <button
                            type="button"
                            class="ui-btn ui-btn-sm u-z-2 u-relative movie-tooltip-target"
                            :class="localInWatchlist ? 'ui-btn-warning' : 'ui-btn-outline-warning'"
                            :disabled="isWatchlistProcessing"
                            :aria-label="localInWatchlist ? 'Remove from watchlist' : 'Add to watchlist'"
                            :data-tooltip="localInWatchlist ? 'Remove from watchlist' : 'Add to watchlist'"
                            @click.prevent="toggleWatchlist"
                        >
                            <i :class="localInWatchlist ? 'fas fa-bookmark' : 'far fa-bookmark'"></i>
                        </button>
                        <button
                            v-if="canManageCurations"
                            type="button"
                            class="ui-btn ui-btn-sm u-z-2 u-relative movie-tooltip-target"
                            :class="localIsFeatured ? 'ui-btn-primary' : 'ui-btn-outline-primary'"
                            :disabled="featuredProcessing"
                            :aria-label="localIsFeatured ? 'Remove from featured list' : 'Add to featured list'"
                            :data-tooltip="localIsFeatured ? 'Remove from featured list' : 'Add to featured list'"
                            @click.prevent="toggleFeatured"
                        >
                            <i :class="localIsFeatured ? 'fas fa-star' : 'far fa-star'"></i>
                        </button>
                        <div class="quick-rating-group u-flex u-items-center ml-auto" :title="`Current rating: ${localUserRating || 0} of 5`">
                            <button
                                v-for="star in 5"
                                :key="`star-${item.id}-${star}`"
                                type="button"
                                class="ui-btn ui-btn-link ui-btn-sm p-0 mx-1 quick-rate-btn u-z-2 u-relative movie-tooltip-target"
                                :class="(localUserRating || 0) >= star ? 'u-text-warning' : 'u-text-secondary'"
                                :aria-label="`Set rating to ${star} star${star > 1 ? 's' : ''}`"
                                :data-tooltip="`Set rating to ${star} star${star > 1 ? 's' : ''}`"
                                @click.prevent="rate(star)"
                            >
                                <i class="fas fa-star"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="movie-card-description">
                <p class="movie-card-description-text" :title="descriptionText">{{ descriptionText }}</p>
            </div>
        </BaseCard>
    </div>
</template>

<style scoped>
.movie-card {
    display: flex;
    flex-direction: column;
}

.movie-card-body {
    display: flex;
    flex-direction: column;
    gap: 0.7rem;
    min-height: 0;
}

.movie-card-content {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    min-height: 0;
}

.movie-card-code-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.5rem;
}

.movie-card-inline-date {
    color: var(--text-2);
    font-size: 0.75rem;
    white-space: nowrap;
    margin-top: 0.1rem;
}

.movie-tooltip-target {
    position: relative;
}

@media (hover: hover) {
    .movie-tooltip-target::after {
        content: attr(data-tooltip);
        position: absolute;
        left: 50%;
        bottom: calc(100% + 8px);
        transform: translateX(-50%) translateY(3px);
        background: rgba(15, 23, 42, 0.96);
        color: #f8fafc;
        border: 1px solid rgba(148, 163, 184, 0.45);
        border-radius: 6px;
        padding: 0.25rem 0.45rem;
        font-size: 0.72rem;
        line-height: 1.2;
        white-space: nowrap;
        z-index: 40;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.16s ease, transform 0.16s ease;
    }

    .movie-tooltip-target::before {
        content: '';
        position: absolute;
        left: 50%;
        bottom: calc(100% + 3px);
        transform: translateX(-50%);
        border-width: 5px 5px 0 5px;
        border-style: solid;
        border-color: rgba(15, 23, 42, 0.96) transparent transparent transparent;
        z-index: 39;
        opacity: 0;
        transition: opacity 0.16s ease;
        pointer-events: none;
    }

    .movie-tooltip-target.movie-tooltip-target-bottom::after {
        top: calc(100% + 8px);
        bottom: auto;
        transform: translateX(-50%) translateY(-3px);
    }

    .movie-tooltip-target.movie-tooltip-target-bottom::before {
        top: calc(100% + 3px);
        bottom: auto;
        border-width: 0 5px 5px 5px;
        border-color: transparent transparent rgba(15, 23, 42, 0.96) transparent;
    }

    .movie-tooltip-target:hover::after,
    .movie-tooltip-target:hover::before,
    .movie-tooltip-target:focus-visible::after,
    .movie-tooltip-target:focus-visible::before {
        opacity: 1;
    }

    .movie-tooltip-target:hover::after,
    .movie-tooltip-target:focus-visible::after {
        transform: translateX(-50%) translateY(0);
    }

    .movie-tooltip-target.movie-tooltip-target-bottom:hover::after,
    .movie-tooltip-target.movie-tooltip-target-bottom:focus-visible::after {
        transform: translateX(-50%) translateY(0);
    }
}

.movie-card-title-line {
    margin-bottom: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.movie-card-badge-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    align-items: flex-start;
}

.movie-card-actions {
    margin-bottom: 0.45rem;
}

.movie-card-description {
    margin-top: 0.2rem;
    padding-top: 0.65rem;
    border-top: 1px solid var(--border);
}

.movie-card-description-text {
    margin: 0;
    color: var(--text-2);
    font-size: 0.84rem;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.movie-card-badge {
    border: 1px solid transparent;
}

.movie-card-badge-actor {
    background: rgba(22, 163, 74, 0.22) !important;
    border-color: rgba(34, 197, 94, 0.5) !important;
    color: #dcfce7 !important;
}

.movie-card-badge-tag {
    background: rgba(2, 132, 199, 0.22) !important;
    border-color: rgba(56, 189, 248, 0.5) !important;
    color: #e0f2fe !important;
}

.movie-card-badge-tag-active {
    background: rgba(245, 158, 11, 0.9) !important;
    border-color: rgba(251, 191, 36, 0.95) !important;
    color: #1f2937 !important;
}

.movie-card-badge-meta {
    background: rgba(148, 163, 184, 0.18) !important;
    border-color: rgba(148, 163, 184, 0.4) !important;
    color: #dbeafe !important;
}

</style>
