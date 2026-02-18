<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useUIStore } from '@jav/Stores/ui';
import BaseCard from '@jav/Components/BaseCard.vue';

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
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
const roles = computed(() => page.props.auth?.user?.roles || []);
const isAdmin = computed(() => roles.value.includes('admin'));

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const resolveName = (value) => {
    if (!value) return '';
    if (typeof value === 'string') return value;
    return value.name || '';
};

const normalizeArray = (list) => {
    return (Array.isArray(list) ? list : [])
        .map((entry) => resolveName(entry))
        .filter((entry) => String(entry || '').trim() !== '');
};

const studioText = computed(() => {
    const studio = props.item?.studio;
    if (Array.isArray(studio)) {
        return studio.filter((entry) => String(entry || '').trim() !== '').join(', ');
    }

    return studio || '';
});

const averageRating = computed(() => {
    const value = props.item?.average_rating ?? props.item?.ratings_avg_rating ?? props.item?.avg_rating ?? props.item?.averageRating;
    const numeric = Number(value);
    return Number.isFinite(numeric) ? Number(numeric.toFixed(1)) : 0;
});

const localIsLiked = ref(Boolean(props.item?.is_liked));
const localInWatchlist = ref(Boolean(props.item?.in_watchlist));
const localWatchlistId = ref(props.item?.watchlist_id || null);
const localUserRating = ref(Number(props.item?.user_rating || 0));
const localUserRatingId = ref(props.item?.user_rating_id || null);
const localIsFeatured = ref(Boolean(props.item?.is_featured ?? props.item?.isFeatured));
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

const defaultFeaturedGroup = 'recent';
const featuredLoaded = ref(false);
const featuredEntries = ref([]);

const loadFeaturedEntries = async () => {
    if (!isAdmin.value || featuredLoaded.value) {
        return;
    }

    try {
        const response = await axios.get(route('jav.api.admin.featured-items.lookup'), {
            params: {
                item_type: 'movie',
                item_id: props.item.id,
            },
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        featuredEntries.value = response.data?.items || [];
        featuredLoaded.value = true;
        localIsFeatured.value = featuredEntries.value.length > 0;
    } catch (error) {
        uiStore.showToast(error.response?.data?.message || 'Failed to load featured status.', 'error');
    }
};

const toggleFeatured = async (value) => {
    if (!isAdmin.value) {
        return;
    }

    if (!featuredLoaded.value) {
        await loadFeaturedEntries();
    }

    const activeEntry = featuredEntries.value.find((entry) => entry.group === defaultFeaturedGroup) || featuredEntries.value[0];

    try {
        if (value) {
            const payload = {
                item_type: 'movie',
                item_id: Number(props.item.id),
                group: defaultFeaturedGroup,
                rank: 0,
                is_active: true,
            };
            const response = await axios.post(route('jav.api.admin.featured-items.store'), payload, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            featuredEntries.value = [response.data, ...featuredEntries.value];
            localIsFeatured.value = true;
            uiStore.showToast('Added to featured group.', 'success');
            return;
        }

        if (activeEntry?.id) {
            await axios.delete(route('jav.api.admin.featured-items.destroy', activeEntry.id), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            featuredEntries.value = featuredEntries.value.filter((entry) => entry.id !== activeEntry.id);
            localIsFeatured.value = featuredEntries.value.length > 0;
            uiStore.showToast('Removed from featured group.', 'success');
        }
    } catch (error) {
        uiStore.showToast(error.response?.data?.message || 'Failed to update featured status.', 'error');
    }
};

const downloadRoute = computed(() => {
    return route('jav.movies.download', props.item.uuid || props.item.id);
});

const detailRoute = computed(() => {
    return route('jav.vue.movies.show', props.item.uuid || props.item.id);
});

const cardData = computed(() => {
    return {
        code: props.item?.formatted_code || props.item?.code || '',
        title: props.item?.title || '',
        releaseDate: formatDate(props.item?.date || props.item?.release_date || props.item?.releaseDate),
        size: props.item?.size || null,
        averageRating: averageRating.value,
        userRating: localUserRating.value,
        views: props.item?.views ?? 0,
        downloads: props.item?.downloads ?? 0,
        runtime: props.item?.runtime || '-',
        studio: studioText.value || '-',
        language: props.item?.language || '-',
        isLiked: localIsLiked.value,
        inWatchlist: localInWatchlist.value,
        isFeatured: localIsFeatured.value,
        cover: props.item?.cover || '',
        description: props.item?.description || '',
        actors: normalizeArray(props.item?.actors),
        tags: normalizeArray(props.item?.tags),
        reasons: [],
    };
});

const handleDownload = () => {
    window.location.href = downloadRoute.value;
};

const openDetail = () => {
    router.visit(detailRoute.value);
};
</script>

<template>
    <div class="ui-col" @mouseenter="loadFeaturedEntries">
        <BaseCard
            :card="cardData"
            :show-cover="true"
            :show-stats="true"
            :show-views="true"
            :show-downloads="true"
            :show-code="true"
            :show-release-date="true"
            :show-description="true"
            :show-actors="true"
            :show-tags="true"
            :show-details="true"
            :show-details-toggle="true"
            :show-download="true"
            :show-like="true"
            :show-watchlist="true"
            :show-user-rating="true"
            :show-average-rating="true"
            :on-like="toggleLike"
            :on-watchlist="toggleWatchlist"
            :on-rate="rate"
            :on-download="handleDownload"
            :on-toggle-featured="toggleFeatured"
            :on-cover-click="openDetail"
            :show-featured-toggle="isAdmin"
        />
    </div>
</template>
