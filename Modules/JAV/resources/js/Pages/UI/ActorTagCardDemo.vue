<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import PrimeActorCardFull from '@jav/Components/PrimeActorCardFull.vue';
import PrimeTagCardFull from '@jav/Components/PrimeTagCardFull.vue';

const actorNames = [
    'Yua Mikami', 'Aoi Sora', 'Yui Hatano', 'Julia', 'Tsubasa Amami',
    'Kana Momonogi', 'Rae Lil Black', 'Yua Asakura', 'Rin Natsuki', 'Mio Kimijima',
    'Hikaru Konno', 'Mina Kitano', 'Airi Kijima', 'Noa Eikawa', 'Sora Amakawa',
    'Mihina Nagai', 'Mao Kurata', 'Yui Nishikawa', 'Nene Yoshitaka', 'Ema Futaba',
    'Yume Nikaido', 'Haru Kuroki', 'Yurina Ayashiro', 'Mio Ishikawa', 'Miri Mizuki', 'Rika Aimi',
];

const tagNames = [
    'Romance', 'Drama', 'Action', 'Mystery', 'Comedy', 'Slice of Life',
    'School', 'Office', 'Fantasy', 'Thriller', 'Exclusive', 'Adventure',
    'Idol', 'Classic', 'Sci-Fi', 'Mature', 'Story Rich', 'HD',
    '4K', 'Remastered', 'Subtitled', 'Popular', 'New Release', 'Trending', 'Editor Pick', 'Recommended',
];

const allActors = actorNames.map((name, index) => ({
    id: index + 1,
    type: 'actor',
    code: `ACT-${String(100 + index + 1).padStart(3, '0')}`,
    name,
    movieCount: 24 + (index * 7) % 160,
    favorites: 120 + (index * 31) % 900,
    followers: 260 + (index * 47) % 1800,
    averageRating: Number((3.1 + (index % 10) * 0.19).toFixed(1)),
    userRating: index % 6,
    cover: `https://placehold.co/640x820?text=Actor+${index + 1}`,
    age: 19 + (index % 17),
    isLiked: index % 2 === 0,
    isFavorited: index % 3 === 0,
    isFeatured: index % 5 === 0,
    agency: ['S1', 'IdeaPocket', 'Moodyz', 'Attackers'][index % 4],
    debutYear: 2010 + (index % 14),
    nationality: 'Japan',
    height: `${150 + (index % 21)} cm`,
    description: 'Demo actor profile card with social and curation actions similar to the movie card pattern.',
}));

const allTags = tagNames.map((name, index) => ({
    id: index + 1,
    type: 'tag',
    code: `TAG-${String(100 + index + 1).padStart(3, '0')}`,
    name,
    movieCount: 35 + (index * 11) % 420,
    followers: 200 + (index * 43) % 1500,
    averageRating: Number((3 + (index % 9) * 0.2).toFixed(1)),
    userRating: index % 6,
    isLiked: index % 2 === 1,
    isFavorited: index % 4 === 0,
    isFeatured: index % 6 === 0,
    cover: `https://placehold.co/640x420?text=Tag+${index + 1}`,
    group: ['Genre', 'Mood', 'Theme', 'Quality'][index % 4],
    trend: ['Rising', 'Stable', 'Hot', 'Seasonal'][index % 4],
    language: 'Japanese',
    region: 'JP',
    description: 'Demo tag profile card with social and curation actions matching the movie and actor card pattern.',
}));

const visibleActors = ref([]);
const visibleTags = ref([]);

const page = ref(1);
const pageSize = 10;
const loading = ref(false);
const sentinel = ref(null);
const observer = ref(null);

const loadNextPage = () => {
    if (loading.value) {
        return;
    }

    const start = (page.value - 1) * pageSize;
    if (start >= allActors.length && start >= allTags.length) {
        return;
    }

    loading.value = true;
    const end = start + pageSize;

    visibleActors.value.push(...allActors.slice(start, end));
    visibleTags.value.push(...allTags.slice(start, end));

    page.value += 1;
    loading.value = false;
};

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
            rootMargin: '400px 0px',
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
    <div class="ui-container-fluid py-4 actor-tag-demo-page actor-tag-demo-page--dark">
        <section class="actor-tag-demo-page__intro mb-4">
            <h1 class="mb-2">Actor & Tag Card Demo (Concept Sandbox)</h1>
            <p class="mb-0">Same card concept style as movie demo, but lighter information for actor/tag entities.</p>
        </section>

        <section class="mb-5">
            <h2 class="actor-tag-demo-page__section-title">Actor Cards</h2>
            <div class="actor-tag-demo-page__grid">
                <div
                    v-for="actor in visibleActors"
                    :key="`actor-${actor.id}`"
                    class="actor-tag-demo-card"
                >
                    <PrimeActorCardFull :actor="actor" />
                </div>
            </div>
        </section>

        <section>
            <h2 class="actor-tag-demo-page__section-title">Tag Cards</h2>
            <div class="actor-tag-demo-page__grid">
                <div
                    v-for="tag in visibleTags"
                    :key="`tag-${tag.id}`"
                    class="actor-tag-demo-card"
                >
                    <PrimeTagCardFull :tag-item="tag" />
                </div>
            </div>
        </section>

        <div ref="sentinel" class="actor-tag-demo-page__sentinel"></div>
    </div>
</template>

<style scoped>
.actor-tag-demo-page--dark {
    min-height: 100vh;
    background: #181a20;
    color: #e5e7eb;
}

.actor-tag-demo-page__intro p {
    color: var(--text-2, #a1a1aa);
}

.actor-tag-demo-page__section-title {
    margin-bottom: 1rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.actor-tag-demo-page__grid {
    column-count: 5;
    column-gap: 1rem;
}

.actor-tag-demo-card {
    break-inside: avoid;
    margin-bottom: 1rem;
    overflow: hidden;
}

.actor-tag-demo-card__cover-wrap {
    position: relative;
    overflow: hidden;
}

.actor-tag-demo-card__cover-wrap--portrait {
    height: 18rem;
}

.actor-tag-demo-card__cover-wrap--landscape {
    height: 11rem;
}

.actor-tag-demo-card__cover {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.actor-tag-demo-card__overlay {
    position: absolute;
    top: 0.7rem;
    right: 0.7rem;
}

.actor-tag-demo-card__title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.actor-tag-demo-card__title {
    margin: 0;
    font-size: 1rem;
}

.actor-tag-demo-card__meta {
    display: grid;
    gap: 0.35rem;
    font-size: 0.9rem;
    opacity: 0.9;
}

.actor-tag-demo-card__meta i {
    width: 1rem;
    text-align: center;
    margin-right: 0.35rem;
}

.actor-tag-demo-card__action {
    width: 100%;
}

.actor-tag-demo-page__sentinel {
    height: 1px;
    width: 100%;
}

@media (max-width: 1800px) {
    .actor-tag-demo-page__grid {
        column-count: 4;
    }
}

@media (max-width: 1400px) {
    .actor-tag-demo-page__grid {
        column-count: 3;
    }
}

@media (max-width: 1000px) {
    .actor-tag-demo-page__grid {
        column-count: 2;
    }
}

@media (max-width: 700px) {
    .actor-tag-demo-page__grid {
        column-count: 1;
    }
}
</style>
