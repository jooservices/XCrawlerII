<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';
import { useUIStore } from '@jav/Stores/ui';

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
        .replace(/[^a-z0-9]+/g, ' ')
        .replace(/\s+/g, ' ');
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
const isProcessing = ref(false);
const isWatchlistProcessing = ref(false);
const ratingProcessing = ref(false);

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
        uiStore.showToast('Failed to update watchlist', 'error');
    } finally {
        isWatchlistProcessing.value = false;
    }
};

const rate = async (rating) => {
    if (!hasAuthUser.value || ratingProcessing.value) return;
    ratingProcessing.value = true;

    try {
        if (localUserRatingId.value) {
            const response = await axios.put(route('jav.api.ratings.update', localUserRatingId.value), {
                rating,
            });
            if (response.data?.success) {
                localUserRating.value = Number(response.data?.data?.rating || rating);
                uiStore.showToast('Rating updated', 'success');
            }
        } else {
            try {
                const response = await axios.post(route('jav.api.ratings.store'), {
                    jav_id: props.item.id,
                    rating,
                });
                if (response.data?.success) {
                    localUserRating.value = Number(response.data?.data?.rating || rating);
                    localUserRatingId.value = response.data?.data?.id || null;
                    uiStore.showToast('Rating saved', 'success');
                }
            } catch (storeError) {
                const message = String(storeError?.response?.data?.message || '');
                if (!message.toLowerCase().includes('already rated')) {
                    throw storeError;
                }

                const checkResponse = await axios.get(route('jav.api.ratings.check', props.item.id));
                const existingId = checkResponse?.data?.id || null;
                if (!existingId) {
                    throw storeError;
                }

                const updateResponse = await axios.put(route('jav.api.ratings.update', existingId), {
                    rating,
                });
                if (updateResponse.data?.success) {
                    localUserRating.value = Number(updateResponse.data?.data?.rating || rating);
                    localUserRatingId.value = updateResponse.data?.data?.id || existingId;
                    uiStore.showToast('Rating updated', 'success');
                }
            }
        }
    } catch (error) {
        uiStore.showToast('Failed to save rating', 'error');
    } finally {
        ratingProcessing.value = false;
    }
};
</script>

<template>
    <div class="ui-col">
        <div class="ui-card u-h-full u-shadow-sm movie-card u-cursor-pointer" :data-uuid="item.uuid" @click="openDetail">
            <Link :href="detailRoute" class="u-relative u-block">
                <img 
                    :src="item.cover" 
                    :alt="item.code" 
                    @error="handleImageError"
                    class="ui-card-img-top u-h-300 u-object-cover"
                >
                <div class="u-absolute u-top-0 u-left-0 u-text-white px-2 py-1 m-2 movie-card-date">
                    <small><i class="fas fa-calendar-alt"></i> {{ formatDate(item.date) }}</small>
                </div>
                <div class="u-absolute u-top-0 u-right-0 u-bg-dark u-bg-opacity-75 u-text-white px-2 py-1 m-2 u-rounded">
                    <small><i class="fas fa-eye"></i> <span>{{ item.views ?? 0 }}</span></small>
                    <small class="ml-2"><i class="fas fa-download"></i> <span>{{ item.downloads ?? 0 }}</span></small>
                </div>
            </Link>

            <div class="ui-card-body movie-card-body">
                <div class="movie-card-content">
                    <div class="movie-card-heading">
                        <Link :href="detailRoute" class="u-no-underline">
                            <h5 class="ui-card-title u-text-primary mb-1">{{ item.formatted_code || item.code }}</h5>
                        </Link>
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
                            <a :href="downloadRoute" class="ui-btn ui-btn-primary ui-btn-sm download-btn">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>

                        <div v-if="hasAuthUser" class="mt-2 u-flex gap-2">
                            <button
                                type="button"
                                class="ui-btn ui-btn-sm u-z-2 u-relative"
                                :class="localIsLiked ? 'ui-btn-danger' : 'ui-btn-outline-danger'"
                                :disabled="isProcessing"
                                title="Like"
                                @click.prevent="toggleLike"
                            >
                                <i :class="localIsLiked ? 'fas fa-heart' : 'far fa-heart'"></i>
                            </button>
                            <button
                                type="button"
                                class="ui-btn ui-btn-sm u-z-2 u-relative"
                                :class="localInWatchlist ? 'ui-btn-warning' : 'ui-btn-outline-warning'"
                                :disabled="isWatchlistProcessing"
                                title="Watchlist"
                                @click.prevent="toggleWatchlist"
                            >
                                <i :class="localInWatchlist ? 'fas fa-bookmark' : 'far fa-bookmark'"></i>
                            </button>
                            <div class="quick-rating-group u-flex u-items-center ml-auto">
                        <button
                            v-for="star in 5"
                            :key="`star-${item.id}-${star}`"
                            type="button"
                            class="ui-btn ui-btn-link ui-btn-sm p-0 mx-1 quick-rate-btn u-z-2 u-relative"
                            :class="(localUserRating || 0) >= star ? 'u-text-warning' : 'u-text-secondary'"
                            :title="`Rate ${star} star${star > 1 ? 's' : ''}`"
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
            </div>
        </div>
    </div>
</template>

<style scoped>
.movie-card {
    display: flex;
    flex-direction: column;
    min-height: 635px;
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

.movie-card-title-line {
    margin-bottom: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.6rem;
}

.movie-card-meta {
    min-height: 1.7rem;
}

.movie-card-badge-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    align-items: flex-start;
    min-height: 2rem;
    max-height: 2rem;
    overflow: hidden;
}

.movie-card-reasons {
    min-height: 1.8rem;
    max-height: 1.8rem;
}

.movie-card-actions {
    min-height: 4.8rem;
}

.movie-card-description {
    margin-top: auto;
    padding-top: 0.65rem;
    border-top: 1px solid var(--border);
    min-height: 4.3rem;
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

.movie-card-date {
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
}
</style>
