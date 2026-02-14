<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';
import { useUIStore } from '@jav/Stores/ui';

const props = defineProps({
    item: Object,
});
const page = usePage();
const uiStore = useUIStore();
const hasAuthUser = computed(() => Boolean(page.props.auth?.user));
const preferences = computed(() => page.props.auth?.user?.preferences || {});
const hideActors = computed(() => Boolean(preferences.value.hide_actors));
const hideTags = computed(() => Boolean(preferences.value.hide_tags));
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
    <div class="col">
        <div class="card h-100 shadow-sm movie-card" :data-uuid="item.uuid" style="cursor: pointer;" @click="openDetail">
            <!-- Clickable Area for Navigation (wrapped or handled via click) -->
            <!-- We'll make image link to detail -->
            <Link :href="detailRoute" class="position-relative d-block">
                <img 
                    :src="item.cover" 
                    class="card-img-top" 
                    :alt="item.code" 
                    @error="handleImageError"
                    style="height: 300px; object-fit: cover;"
                >
                <div class="position-absolute top-0 end-0 bg-dark bg-opacity-75 text-white px-2 py-1 m-2 rounded">
                    <small><i class="fas fa-eye"></i> <span>{{ item.views ?? 0 }}</span></small>
                    <small class="ms-2"><i class="fas fa-download"></i> <span>{{ item.downloads ?? 0 }}</span></small>
                </div>
            </Link>

            <div class="card-body">
                <Link :href="detailRoute" class="text-decoration-none">
                    <h5 class="card-title text-primary">{{ item.formatted_code || item.code }}</h5>
                </Link>
                <p class="card-text text-truncate" :title="item.title">{{ titleText }}</p>
                <p class="card-text">
                    <small class="text-muted"><i class="fas fa-calendar-alt"></i> {{ formatDate(item.date) }}</small>
                    <span v-if="item.size" class="float-end badge bg-secondary">{{ item.size }} GB</span>
                </p>

                <!-- Actors -->
                <div v-if="!hideActors" class="mb-2">
                    <Link 
                        v-for="(actor, index) in item.actors" 
                        :key="index"
                        :href="route('jav.vue.dashboard', { actor: resolveName(actor) })"
                        class="badge bg-success text-decoration-none z-index-2 position-relative me-1"
                    >
                        {{ resolveName(actor) }}
                    </Link>
                </div>

                <!-- Tags -->
                <div v-if="!hideTags" class="mb-2">
                    <Link 
                        v-for="(tag, index) in item.tags" 
                        :key="index"
                        :href="route('jav.vue.dashboard', { tag: resolveName(tag) })"
                        class="badge bg-info text-dark text-decoration-none z-index-2 position-relative me-1"
                    >
                        {{ resolveName(tag) }}
                    </Link>
                </div>

                <div class="mt-3 d-grid gap-2">
                    <a :href="downloadRoute" class="btn btn-primary btn-sm download-btn">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>

                <div v-if="hasAuthUser" class="mt-2 d-flex gap-2">
                    <button
                        type="button"
                        class="btn btn-sm z-index-2 position-relative"
                        :class="localIsLiked ? 'btn-danger' : 'btn-outline-danger'"
                        :disabled="isProcessing"
                        title="Like"
                        @click.prevent="toggleLike"
                    >
                        <i :class="localIsLiked ? 'fas fa-heart' : 'far fa-heart'"></i>
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm z-index-2 position-relative"
                        :class="localInWatchlist ? 'btn-warning' : 'btn-outline-warning'"
                        :disabled="isWatchlistProcessing"
                        title="Watchlist"
                        @click.prevent="toggleWatchlist"
                    >
                        <i :class="localInWatchlist ? 'fas fa-bookmark' : 'far fa-bookmark'"></i>
                    </button>
                    <div class="quick-rating-group d-flex align-items-center ms-auto">
                        <button
                            v-for="star in 5"
                            :key="`star-${item.id}-${star}`"
                            type="button"
                            class="btn btn-link btn-sm p-0 mx-1 quick-rate-btn z-index-2 position-relative"
                            :class="(localUserRating || 0) >= star ? 'text-warning' : 'text-secondary'"
                            :title="`Rate ${star} star${star > 1 ? 's' : ''}`"
                            @click.prevent="rate(star)"
                        >
                            <i class="fas fa-star"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card-footer bg-transparent border-top-0">
                <button class="btn btn-sm btn-outline-secondary w-100" type="button" data-bs-toggle="collapse" :data-bs-target="'#desc-' + item.id">
                    Show Description
                </button>
                <div class="collapse mt-2" :id="'desc-' + item.id">
                    <div class="card card-body small">
                        {{ descriptionText }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Scoped styles if needed, mostly bootstrap classes used */
</style>
