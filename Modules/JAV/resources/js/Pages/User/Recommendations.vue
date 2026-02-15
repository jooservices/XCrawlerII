<script setup>
import { Head } from '@inertiajs/vue3';
import MovieCard from '@jav/Components/MovieCard.vue';

const props = defineProps({
    recommendations: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Recommendations" />

    
        <div class="ui-container-fluid">
            <div class="ui-row mb-4">
                <div class="ui-col-12">
                    <h2><i class="fas fa-magic text-purple"></i> Recommended for You</h2>
                    <p class="u-text-muted">Based on your liked movies, actors, and tags</p>
                </div>
            </div>

            <div v-if="recommendations.length === 0" class="ui-alert ui-alert-info">
                <i class="fas fa-info-circle"></i> No recommendations yet. Like some movies, actors, or tags to get personalized suggestions!
            </div>

            <div v-else class="ui-row ui-row-cols-1 ui-row-cols-md-3 ui-row-cols-lg-4 ui-g-4">
                <MovieCard
                    v-for="(recommendation, index) in recommendations"
                    :key="recommendation.movie?.id || index"
                    :item="recommendation.movie"
                    :recommendation-reasons="recommendation.reasons"
                />
            </div>
        </div>
    
</template>
