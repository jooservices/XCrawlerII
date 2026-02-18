<script setup>
import { computed, ref } from 'vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Chip from 'primevue/chip';
import Rating from 'primevue/rating';
import ToggleSwitch from 'primevue/toggleswitch';

const props = defineProps({
    movie: {
        type: Object,
        required: true,
    },
});

const liked = ref(Boolean(props.movie?.isLiked));
const watchlisted = ref(Boolean(props.movie?.inWatchlist));
const featured = ref(Boolean(props.movie?.isFeatured));
const showDetails = ref(false);
const userRating = ref(Number(props.movie?.userRating || 0));

const actorList = computed(() => (Array.isArray(props.movie?.actors) ? props.movie.actors : []));
const tagList = computed(() => (Array.isArray(props.movie?.tags) ? props.movie.tags : []));
const recommendationList = computed(() => (Array.isArray(props.movie?.reasons) ? props.movie.reasons : []));
const metadata = computed(() => {
    return [
        { key: 'Code', value: props.movie?.code || '-' },
        { key: 'Runtime', value: props.movie?.runtime || '-' },
        { key: 'Studio', value: props.movie?.studio || '-' },
        { key: 'Language', value: props.movie?.language || '-' },
    ];
});
</script>

<template>
    <Card class="prime-movie-card-full prime-movie-card-full--dark" role="region" aria-label="PrimeVue movie card">
        <template #header>
            <div class="prime-movie-card-full__cover-wrap">
                <img :src="movie.cover" :alt="movie.title" class="prime-movie-card-full__cover">
                <div class="prime-movie-card-full__overlay">
                    <div class="prime-movie-card-full__stats">
                        <span class="prime-movie-card-full__stat"><i class="fas fa-eye"></i> {{ movie.views }}</span>
                        <span class="prime-movie-card-full__stat"><i class="fas fa-download"></i> {{ movie.downloads }}</span>
                    </div>
                </div>
            </div>
        </template>

        <template #title>
            <div class="prime-movie-card-full__title-row" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                <span class="prime-movie-card-full__code">{{ movie.code }}</span>
                <Tag :value="movie.releaseDate" severity="secondary" size="small" />
            </div>
            <div class="prime-movie-card-full__title-wrap">
                <h3 class="prime-movie-card-full__title">{{ movie.title }}</h3>
            </div>
        </template>

        <template #subtitle>
            <div class="prime-movie-card-full__subtitle">
            </div>
        </template>

        <template #content>
            <div class="prime-movie-card-full__content">
                <p class="prime-movie-card-full__description">{{ movie.description }}</p>

                <div class="prime-movie-card-full__group">
                    <h4>Actors</h4>
                    <div class="prime-movie-card-full__chips">
                        <Chip v-for="actor in actorList" :key="`actor-${movie.code}-${actor}`" :label="actor" size="small" />
                    </div>
                </div>

                <div class="prime-movie-card-full__group">
                    <h4>Tags</h4>
                    <div class="prime-movie-card-full__chips">
                        <Tag
                            v-for="tag in tagList"
                            :key="`tag-${movie.code}-${tag}`"
                            :value="tag"
                            severity="secondary"
                            rounded
                            size="small"
                        />
                    </div>
                </div>

                <div class="prime-movie-card-full__group">
                    <h4>Recommended Because</h4>
                    <ul class="prime-movie-card-full__reasons">
                        <li v-for="reason in recommendationList" :key="`reason-${movie.code}-${reason}`">{{ reason }}</li>
                    </ul>
                </div>

                <div v-if="showDetails" class="prime-movie-card-full__details">
                    <div v-for="item in metadata" :key="item.key" class="prime-movie-card-full__detail-item">
                        <strong>{{ item.key }}:</strong>
                        <span>{{ item.value }}</span>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="prime-movie-card-full__footer">
                <div class="prime-movie-card-full__footer-row prime-movie-card-full__footer-row--single prime-movie-card-full__footer-row--download">
                    <Button 
                        size="small" 
                        :label="movie.size ? `Download (${movie.size} GB)` : 'Download'"
                        severity="primary"
                        class="prime-movie-card-full__download-btn"
                    >
                        <template #icon>
                            <i class="fas fa-download"></i>
                        </template>
                    </Button>
                </div>
                <div class="prime-movie-card-full__footer-row prime-movie-card-full__footer-row--single">
                    <Button size="small" :label="liked ? 'Liked' : 'Like'" :severity="liked ? 'danger' : 'secondary'" outlined @click="liked = !liked">
                        <template #icon>
                            <i :class="liked ? 'fas fa-heart' : 'far fa-heart'"></i>
                        </template>
                    </Button>
                    <Button size="small" :label="watchlisted ? 'Watchlisted' : 'Watchlist'" :severity="watchlisted ? 'warning' : 'secondary'" outlined @click="watchlisted = !watchlisted">
                        <template #icon>
                            <i :class="watchlisted ? 'fas fa-bookmark' : 'far fa-bookmark'"></i>
                        </template>
                    </Button>
                </div>

                <div class="prime-movie-card-full__footer-row prime-movie-card-full__footer-row--between">
                    <div class="prime-movie-card-full__rating-wrap">
                        <span>Your rating</span>
                        <Rating v-model="userRating" :stars="5" size="small" />
                        <div class="prime-movie-card-full__average">Average: {{ movie.averageRating }}/5</div>
                    </div>

                    <div class="prime-movie-card-full__switch-wrap">
                        <label for="featured-toggle" style="display: flex; align-items: center; gap: 0.4rem;">
                            <span>Featured</span>
                            <i :class="featured ? 'fas fa-star' : 'far fa-star'" style="color: #fbbf24; font-size: 1.2em;"></i>
                        </label>
                        <ToggleSwitch id="featured-toggle" v-model="featured" size="small" />
                    </div>
                </div>

                <Button
                    :label="showDetails ? 'Hide extra details' : 'Show extra details'"
                    text
                    icon="pi pi-angle-down"
                    icon-pos="right"
                    @click="showDetails = !showDetails"
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
</style>
/* Download button full width and attractive color */
.prime-movie-card-full__footer-row--download {
    width: 100%;
}
.prime-movie-card-full__download-btn {
    width: 100%;
    background: linear-gradient(90deg, #2563eb 0%, #1e40af 100%);
    color: #fff !important;
    border: none;
    box-shadow: 0 2px 8px 0 rgba(30,64,175,0.08);
    font-weight: 600;
    letter-spacing: 0.01em;
    transition: background 0.2s;
}
.prime-movie-card-full__download-btn:hover {
    background: linear-gradient(90deg, #1e40af 0%, #2563eb 100%);
    color: #fff !important;
}
/* Font Awesome stat icon spacing */
.prime-movie-card-full__stat {
    display: inline-flex;
    align-items: center;
    gap: 0.25em;
    font-size: 0.95em;
    margin-right: 0.7em;
}
/* Force dark mode for the main card in the demo */
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
