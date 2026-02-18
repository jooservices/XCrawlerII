<script setup>
import { computed, ref, watch } from 'vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Chip from 'primevue/chip';
import Rating from 'primevue/rating';
import ToggleSwitch from 'primevue/toggleswitch';

const props = defineProps({
    card: {
        type: Object,
        required: true,
    },
    showCover: {
        type: Boolean,
        default: true,
    },
    showStats: {
        type: Boolean,
        default: true,
    },
    topRightItems: {
        type: Array,
        default: () => [],
    },
    showViews: {
        type: Boolean,
        default: true,
    },
    showDownloads: {
        type: Boolean,
        default: true,
    },
    showCode: {
        type: Boolean,
        default: true,
    },
    showReleaseDate: {
        type: Boolean,
        default: true,
    },
    showDescription: {
        type: Boolean,
        default: true,
    },
    showActors: {
        type: Boolean,
        default: true,
    },
    showTags: {
        type: Boolean,
        default: true,
    },
    showDetails: {
        type: Boolean,
        default: true,
    },
    showDetailsToggle: {
        type: Boolean,
        default: true,
    },
    showDownload: {
        type: Boolean,
        default: true,
    },
    showLike: {
        type: Boolean,
        default: true,
    },
    showWatchlist: {
        type: Boolean,
        default: true,
    },
    showUserRating: {
        type: Boolean,
        default: true,
    },
    showAverageRating: {
        type: Boolean,
        default: true,
    },
    showFeaturedToggle: {
        type: Boolean,
        default: true,
    },
    onCoverClick: {
        type: Function,
        default: null,
    },
    onLike: {
        type: Function,
        default: null,
    },
    onWatchlist: {
        type: Function,
        default: null,
    },
    onRate: {
        type: Function,
        default: null,
    },
    onDownload: {
        type: Function,
        default: null,
    },
    onToggleFeatured: {
        type: Function,
        default: null,
    },
});

const liked = ref(Boolean(props.card?.isLiked));
const watchlisted = ref(Boolean(props.card?.inWatchlist));
const featured = ref(Boolean(props.card?.isFeatured));
const showDetailsState = ref(false);
const userRating = ref(Number(props.card?.userRating || 0));

watch(
    () => props.card?.isLiked,
    (next) => {
        liked.value = Boolean(next);
    }
);
watch(
    () => props.card?.inWatchlist,
    (next) => {
        watchlisted.value = Boolean(next);
    }
);
watch(
    () => props.card?.isFeatured,
    (next) => {
        featured.value = Boolean(next);
    }
);
watch(
    () => props.card?.userRating,
    (next) => {
        userRating.value = Number(next || 0);
    }
);

const actorList = computed(() => (Array.isArray(props.card?.actors) ? props.card.actors : []));
const tagList = computed(() => (Array.isArray(props.card?.tags) ? props.card.tags : []));
const recommendationList = computed(() => (Array.isArray(props.card?.reasons) ? props.card.reasons : []));
const metadata = computed(() => {
    return [
        { key: 'Code', value: props.card?.code || '-' },
        { key: 'Runtime', value: props.card?.runtime || '-' },
        { key: 'Studio', value: props.card?.studio || '-' },
        { key: 'Language', value: props.card?.language || '-' },
    ];
});

const resolvedTopRightItems = computed(() => {
    if (Array.isArray(props.topRightItems) && props.topRightItems.length > 0) {
        return props.topRightItems;
    }

    const items = [];
    if (props.showViews) {
        items.push({ icon: 'fas fa-eye', value: props.card?.views ?? 0, label: 'Views' });
    }
    if (props.showDownloads) {
        items.push({ icon: 'fas fa-download', value: props.card?.downloads ?? 0, label: 'Downloads' });
    }
    return items;
});

const showStatsBlock = computed(() => props.showStats && resolvedTopRightItems.value.length > 0);
const showActorsBlock = computed(() => props.showActors && actorList.value.length > 0);
const showTagsBlock = computed(() => props.showTags && tagList.value.length > 0);
const showActionsRow = computed(() => props.showLike || props.showWatchlist);
const showRatingRow = computed(() => props.showUserRating || props.showAverageRating || props.showFeaturedToggle);
const showFooter = computed(() => props.showDownload || showActionsRow.value || showRatingRow.value || props.showDetailsToggle);

const handleLike = () => {
    liked.value = !liked.value;
    if (props.onLike) {
        props.onLike(liked.value);
    }
};

const handleWatchlist = () => {
    watchlisted.value = !watchlisted.value;
    if (props.onWatchlist) {
        props.onWatchlist(watchlisted.value);
    }
};

const handleRate = (value) => {
    userRating.value = value;
    if (props.onRate) {
        props.onRate(value);
    }
};

const handleDownload = () => {
    if (props.onDownload) {
        props.onDownload();
    }
};

const handleFeatured = (value) => {
    featured.value = value;
    if (props.onToggleFeatured) {
        props.onToggleFeatured(value);
    }
};

const handleCoverClick = () => {
    if (props.onCoverClick) {
        props.onCoverClick();
    }
};
</script>

<template>
    <Card class="prime-movie-card-full prime-movie-card-full--dark" role="region" aria-label="PrimeVue card">
        <template #header>
            <div v-if="showCover" class="prime-movie-card-full__cover-wrap" role="button" tabindex="0" @click="handleCoverClick">
                <img :src="card.cover" :alt="card.title" class="prime-movie-card-full__cover">
                <div v-if="showStatsBlock" class="prime-movie-card-full__overlay">
                    <div class="prime-movie-card-full__stats">
                        <span
                            v-for="(item, index) in resolvedTopRightItems"
                            :key="`${card.code || card.title || 'stat'}-${index}`"
                            class="prime-movie-card-full__stat"
                            :title="item.label"
                        >
                            <i :class="item.icon"></i> {{ item.value }}
                        </span>
                    </div>
                </div>
            </div>
        </template>

        <template #title>
            <div v-if="showCode || showReleaseDate" class="prime-movie-card-full__title-row" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                <span v-if="showCode" class="prime-movie-card-full__code">{{ card.code }}</span>
                <Tag v-if="showReleaseDate" :value="card.releaseDate" severity="secondary" size="small" />
            </div>
            <div class="prime-movie-card-full__title-wrap">
                <h3 class="prime-movie-card-full__title">{{ card.title }}</h3>
            </div>
        </template>

        <template #subtitle>
            <div class="prime-movie-card-full__subtitle">
            </div>
        </template>

        <template #content>
            <div class="prime-movie-card-full__content">
                <p v-if="showDescription" class="prime-movie-card-full__description">{{ card.description }}</p>

                <div v-if="showActorsBlock" class="prime-movie-card-full__group">
                    <h4>Actors</h4>
                    <div class="prime-movie-card-full__chips">
                        <Chip v-for="actor in actorList" :key="`actor-${card.code}-${actor}`" :label="actor" size="small" />
                    </div>
                </div>

                <div v-if="showTagsBlock" class="prime-movie-card-full__group">
                    <h4>Tags</h4>
                    <div class="prime-movie-card-full__chips">
                        <Tag
                            v-for="tag in tagList"
                            :key="`tag-${card.code}-${tag}`"
                            :value="tag"
                            severity="secondary"
                            rounded
                            size="small"
                        />
                    </div>
                </div>

                <!--
                <div class="prime-movie-card-full__group">
                    <h4>Recommended Because</h4>
                    <ul class="prime-movie-card-full__reasons">
                        <li v-for="reason in recommendationList" :key="`reason-${card.code}-${reason}`">{{ reason }}</li>
                    </ul>
                </div>
                -->

                <div v-if="showDetails && showDetailsState" class="prime-movie-card-full__details">
                    <div v-for="item in metadata" :key="item.key" class="prime-movie-card-full__detail-item">
                        <strong>{{ item.key }}:</strong>
                        <span>{{ item.value }}</span>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <div v-if="showFooter" class="prime-movie-card-full__footer">
                <div v-if="showDownload" class="prime-movie-card-full__footer-row prime-movie-card-full__footer-row--single prime-movie-card-full__footer-row--download">
                    <Button
                        size="small"
                        :label="card.size ? `Download (${card.size} GB)` : 'Download'"
                        severity="primary"
                        class="prime-movie-card-full__download-btn"
                        @click="handleDownload"
                    >
                        <template #icon>
                            <i class="fas fa-download"></i>
                        </template>
                    </Button>
                </div>
                <div v-if="showActionsRow" class="prime-movie-card-full__footer-row prime-movie-card-full__footer-row--single">
                    <Button v-if="showLike" size="small" :label="liked ? 'Liked' : 'Like'" :severity="liked ? 'danger' : 'secondary'" outlined @click="handleLike">
                        <template #icon>
                            <i :class="liked ? 'fas fa-heart' : 'far fa-heart'"></i>
                        </template>
                    </Button>
                    <Button v-if="showWatchlist" size="small" :label="watchlisted ? 'Watchlisted' : 'Watchlist'" :severity="watchlisted ? 'warning' : 'secondary'" outlined @click="handleWatchlist">
                        <template #icon>
                            <i :class="watchlisted ? 'fas fa-bookmark' : 'far fa-bookmark'"></i>
                        </template>
                    </Button>
                </div>

                <div v-if="showRatingRow" class="prime-movie-card-full__footer-row prime-movie-card-full__footer-row--between">
                    <div class="prime-movie-card-full__rating-wrap">
                        <Rating v-if="showUserRating" v-model="userRating" :stars="5" size="small" title="Your rating" @update:modelValue="handleRate" />
                        <div v-if="showAverageRating" class="prime-movie-card-full__average">Average: {{ card.averageRating }}/5</div>
                    </div>

                    <div v-if="showFeaturedToggle" class="prime-movie-card-full__switch-wrap">
                        <label for="featured-toggle" style="display: flex; align-items: center; gap: 0.4rem;">
                            <span>Featured</span>
                            <i :class="featured ? 'fas fa-star' : 'far fa-star'" style="color: #fbbf24; font-size: 1.2em;"></i>
                        </label>
                        <ToggleSwitch id="featured-toggle" v-model="featured" size="small" @update:modelValue="handleFeatured" />
                    </div>
                </div>

                <Button
                    v-if="showDetailsToggle"
                    :label="showDetailsState ? 'Hide extra details' : 'Show extra details'"
                    text
                    icon="pi pi-angle-down"
                    icon-pos="right"
                    @click="showDetailsState = !showDetailsState"
                />
            </div>
        </template>
    </Card>
</template>

<style scoped>
.prime-movie-card-full {
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.prime-movie-card-full__cover-wrap {
    position: relative;
    height: 22rem;
    overflow: hidden;
}

.prime-movie-card-full__cover {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.prime-movie-card-full__overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0.85rem;
}

.prime-movie-card-full__stats {
    display: flex;
    gap: 0.35rem;
}

.prime-movie-card-full__title-wrap {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.prime-movie-card-full__code {
    font-size: 0.8rem;
    opacity: 0.75;
}

.prime-movie-card-full__title {
    margin: 0;
    font-size: 1.05rem;
    line-height: 1.4;
}

.prime-movie-card-full__subtitle {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
}

.prime-movie-card-full__average {
    font-size: 0.84rem;
    opacity: 0.85;
}

.prime-movie-card-full__content {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.prime-movie-card-full__description {
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.5;
}

.prime-movie-card-full__group h4 {
    margin: 0 0 0.45rem;
    font-size: 0.84rem;
    font-weight: 600;
}

.prime-movie-card-full__chips {
    display: flex;
    gap: 0.45rem;
    flex-wrap: wrap;
}

.prime-movie-card-full__reasons {
    margin: 0;
    padding-left: 1rem;
    display: grid;
    gap: 0.25rem;
}

.prime-movie-card-full__details {
    border-top: 1px solid var(--p-content-border-color);
    padding-top: 0.8rem;
    display: grid;
    gap: 0.4rem;
}

.prime-movie-card-full__detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.88rem;
}

.prime-movie-card-full__footer {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
}

.prime-movie-card-full__footer-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.prime-movie-card-full__footer-row--between {
    justify-content: space-between;
}

.prime-movie-card-full__rating-wrap,
.prime-movie-card-full__switch-wrap {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.84rem;
}

.prime-movie-card-full__footer-row--download {
    width: 100%;
}

.prime-movie-card-full__download-btn {
    width: 100%;
    background: linear-gradient(90deg, #2563eb 0%, #1e40af 100%);
    color: #fff !important;
    border: none;
    box-shadow: 0 2px 8px 0 rgba(30, 64, 175, 0.08);
    font-weight: 600;
    letter-spacing: 0.01em;
    transition: background 0.2s;
}

.prime-movie-card-full__download-btn:hover {
    background: linear-gradient(90deg, #1e40af 0%, #2563eb 100%);
    color: #fff !important;
}

.prime-movie-card-full__stat {
    display: inline-flex;
    align-items: center;
    gap: 0.25em;
    font-size: 0.95em;
    margin-right: 0.7em;
    color: #f9fafb;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.75);
    background: rgba(15, 23, 42, 0.55);
    padding: 2px 6px;
    border-radius: 999px;
}

.prime-movie-card-full--dark {
    background: #23262f !important;
    color: #e5e7eb !important;
    border-color: #23262f !important;
}

.prime-movie-card-full--dark .p-card-content,
.prime-movie-card-full--dark .p-card-title,
.prime-movie-card-full--dark .p-card-subtitle {
    background: transparent !important;
    color: #e5e7eb !important;
}

.prime-movie-card-full--dark .prime-movie-card-full__blur-on-hover {
    color: #e5e7eb !important;
}

.prime-movie-card-full :deep(.p-button) {
    font-size: 0.72rem;
    padding: 0.35rem 0.6rem;
}

.prime-movie-card-full :deep(.p-tag),
.prime-movie-card-full :deep(.p-chip) {
    font-size: 0.7rem;
}

.prime-movie-card-full :deep(.p-rating .p-rating-icon) {
    font-size: 0.85rem;
}
</style>
