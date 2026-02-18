<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import ToggleSwitch from 'primevue/toggleswitch';
import PrimeMovieCardFull from '@jav/Components/PrimeMovieCardFull.vue';

const compactMode = ref(false);
const highlighted = ref(false);

const actorPool = [
    'Yua Mikami',
    'Rae Lil Black',
    'Yui Hatano',
    'Tsubasa Amami',
    'Kana Momonogi',
    'Aoi Sora',
    'Julia',
    'Kasumi Arimura',
    'Suzu Hirose',
    'Kento Yamazaki',
];

const tagPool = [
    'Romance',
    'Drama',
    'Action',
    'Mystery',
    'Comedy',
    'Documentary',
    'Sci-Fi',
    'Adventure',
    'Slice of Life',
    'Exclusive',
];

const studioPool = ['S1 NO.1 STYLE', 'DocuFilms', 'FantasyWorks', 'Romance Films', 'Mystery Studio', 'Big Studio'];
const titlePool = [
    'Moonlight Promise',
    'Silent Harbor',
    'Neon District',
    'Winter Letter',
    'Beyond Tokyo',
    'Crimson Night',
    'Secret Platform',
    'After Rain',
    'Hidden Chapter',
    'Ocean Memory',
];

function sample(list, count, seed) {
    return Array.from({ length: count }, (_, index) => list[(seed + index * 2) % list.length]);
}

function makeMovie(index) {
    const id = index + 1;
    const isLite = id % 5 === 1;
    const isFull = id % 5 === 0;
    const actorCount = isLite ? 1 : isFull ? 8 : 3;
    const tagCount = isLite ? 1 : isFull ? 6 : 3;

    return {
        code: `RND-${String(100 + id).padStart(3, '0')}`,
        title: `${titlePool[index % titlePool.length]} #${id}`,
        releaseDate: `202${4 + (index % 3)}-${String((index % 12) + 1).padStart(2, '0')}-${String((index % 27) + 1).padStart(2, '0')}`,
        size: Number((isLite ? 1.8 + (index % 3) * 0.4 : isFull ? 10.5 + (index % 5) : 3.4 + (index % 4) * 1.1).toFixed(1)),
        quality: isFull ? '4K' : 'HD',
        averageRating: Number((3.2 + (index % 10) * 0.18).toFixed(1)),
        userRating: index % 6,
        views: 500 + id * 320,
        downloads: 40 + id * 35,
        runtime: `${1 + (index % 3)}h ${String(5 + (index * 7) % 55).padStart(2, '0')}m`,
        studio: studioPool[index % studioPool.length],
        language: 'Japanese',
        isLiked: id % 2 === 0,
        inWatchlist: id % 3 === 0,
        isFeatured: isFull,
        cover: `https://placehold.co/640x960?text=Movie+${id}`,
        description: isLite
            ? 'Compact card with minimal metadata for quick scanning.'
            : isFull
                ? 'Full-detail card with dense metadata, cast, tags, and recommendation signals for stress testing the layout.'
                : 'Balanced card with practical details and realistic metadata for normal browsing.',
        actors: sample(actorPool, actorCount, index),
        tags: sample(tagPool, tagCount, index + 1),
        reasons: isLite
            ? []
            : isFull
                ? ['Featured by admin', 'High engagement in similar genre', 'Long-session viewers finished this title']
                : ['Users with similar watch history liked this'],
    };
}

const allDemoMovies = Array.from({ length: 26 }, (_, index) => makeMovie(index));

const demoMovies = ref([]);
const page = ref(1);
const pageSize = 10;
const loading = ref(false);
const sentinel = ref(null);
const observer = ref(null);

function loadNextPage() {
    if (loading.value) return;

    const start = (page.value - 1) * pageSize;
    if (start >= allDemoMovies.length) return;

    loading.value = true;
    const end = start + pageSize;
    demoMovies.value.push(...allDemoMovies.slice(start, end));
    page.value += 1;
    loading.value = false;
}

onMounted(() => {
    loadNextPage();

    observer.value = new globalThis.IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting) {
                loadNextPage();
            }
        },
        {
            root: null,
            rootMargin: '500px 0px',
            threshold: 0,
        }
    );

    if (sentinel.value) {
        observer.value.observe(sentinel.value);
    }
});

onBeforeUnmount(() => {
    if (observer.value) {
        observer.value.disconnect();
    }
});
</script>

<template>
    <div class="ui-container-fluid py-4 movie-card-demo-page movie-card-demo-page--dark">
        <section class="movie-card-demo-page__intro mb-4">
            <h1 class="mb-2">PrimeVue Card Demo (UI Refactor Sandbox)</h1>
            <p class="mb-0">Masonry layout with 26 demo cards and lazy loading (10 cards per page).</p>
        </section>

        <div class="movie-card-demo-page__controls-row">
            <Card class="movie-card-demo-page__basic" :class="{ 'movie-card-demo-page__basic--active': highlighted }" role="region" aria-label="PrimeVue card basic demo">
                <template #title>Basic Card Demo</template>
                <template #subtitle>Hover + toggle interaction only (no backend)</template>
                <template #content>
                    <p>This section mirrors the official Card usage pattern with local-only interactions.</p>
                    <div class="movie-card-demo-page__controls">
                        <label for="compact-mode-switch">Compact mode</label>
                        <ToggleSwitch id="compact-mode-switch" v-model="compactMode" />
                    </div>
                </template>
                <template #footer>
                    <div class="movie-card-demo-page__actions">
                        <Button
                            :label="highlighted ? 'Unhighlight card' : 'Highlight card'"
                            :severity="highlighted ? 'warning' : 'secondary'"
                            outlined
                            @click="highlighted = !highlighted"
                        />
                        <Button :label="compactMode ? 'Disable compact' : 'Enable compact'" @click="compactMode = !compactMode" />
                    </div>
                </template>
            </Card>
        </div>

        <div class="movie-card-demo-page__card-grid">
            <div
                v-for="movie in demoMovies"
                :key="movie.code"
                :class="['movie-card-demo-page__full', { 'movie-card-demo-page__full--compact': compactMode }]"
            >
                <PrimeMovieCardFull :movie="movie" />
            </div>
            <div ref="sentinel" class="movie-card-demo-page__sentinel" />
        </div>
    </div>
</template>

<style scoped>
.movie-card-demo-page--dark {
    background: #181a20;
    min-height: 100vh;
    color: #e5e7eb;
}

.movie-card-demo-page__intro p {
    color: var(--text-2, #a1a1aa);
}

.movie-card-demo-page__controls-row {
    margin-bottom: 2.5rem;
    display: flex;
    justify-content: flex-start;
}

.movie-card-demo-page__basic {
    transition: transform 0.2s ease;
    background: #23262f;
    color: #e5e7eb;
}

.movie-card-demo-page__basic:hover {
    transform: translateY(-3px);
}

.movie-card-demo-page__basic--active {
    outline: 2px solid var(--p-primary-color, #f59e42);
}

.movie-card-demo-page__controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 0.75rem;
}

.movie-card-demo-page__actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.movie-card-demo-page__card-grid {
    column-count: 5;
    column-gap: 1rem;
}

.movie-card-demo-page__full {
    break-inside: avoid;
    margin-bottom: 1rem;
    display: block;
    width: 100%;
}

.movie-card-demo-page__full--compact {
    transform: scale(0.96);
    transform-origin: top center;
}

.movie-card-demo-page__sentinel {
    height: 1px;
    width: 100%;
}

@media (max-width: 1800px) {
    .movie-card-demo-page__card-grid {
        column-count: 4;
    }
}

@media (max-width: 1400px) {
    .movie-card-demo-page__card-grid {
        column-count: 3;
    }
}

@media (max-width: 1000px) {
    .movie-card-demo-page__card-grid {
        column-count: 2;
    }
}

@media (max-width: 700px) {
    .movie-card-demo-page__card-grid {
        column-count: 1;
    }
}
</style>