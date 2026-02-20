<script setup>
import { Head } from '@inertiajs/vue3';
import MovieCard from '@jav/Components/MovieCard.vue';
import PageShell from '@jav/Components/UI/PageShell.vue';
import SectionHeader from '@jav/Components/UI/SectionHeader.vue';
import EmptyState from '@jav/Components/UI/EmptyState.vue';

const props = defineProps({
    recommendations: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Recommendations" />

    <PageShell>
        <template #header>
            <SectionHeader title="Recommended for You" subtitle="Based on your liked movies, actors, and tags" />
        </template>

        <EmptyState
            v-if="recommendations.length === 0"
            tone="info"
            icon="fas fa-wand-magic-sparkles"
            message="No recommendations yet. Like some movies, actors, or tags to get personalized suggestions!"
        />

        <div v-else class="movie-masonry-grid">
            <MovieCard
                v-for="(recommendation, index) in recommendations"
                :key="recommendation.movie?.id || index"
                :item="recommendation.movie"
                :recommendation-reasons="recommendation.reasons"
            />
        </div>
    </PageShell>
</template>

<style scoped>
.movie-masonry-grid {
    column-count: 1;
    column-gap: 1rem;
}

.movie-masonry-grid > .ui-col {
    break-inside: avoid;
    margin-bottom: 1rem;
}

@media (min-width: 768px) {
    .movie-masonry-grid {
        column-count: 4;
    }
}

@media (min-width: 1200px) {
    .movie-masonry-grid {
        column-count: 4;
    }
}
</style>
