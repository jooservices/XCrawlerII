<script setup>
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';

const props = defineProps({
    recommendations: {
        type: Array,
        default: () => [],
    },
});

const openMovie = (movie) => {
    router.visit(route('jav.vue.movies.show', movie.uuid || movie.id));
};

const handleImageError = (event) => {
    event.target.src = 'https://placehold.co/300x400?text=No+Image';
};
</script>

<template>
    <Head title="Recommendations" />

    <DashboardLayout>
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h2><i class="fas fa-magic text-purple"></i> Recommended for You</h2>
                    <p class="text-muted">Based on your liked movies, actors, and tags</p>
                </div>
            </div>

            <div v-if="recommendations.length === 0" class="alert alert-info">
                <i class="fas fa-info-circle"></i> No recommendations yet. Like some movies, actors, or tags to get personalized suggestions!
            </div>

            <div v-else class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-4">
                <div
                    v-for="(recommendation, index) in recommendations"
                    :key="recommendation.movie?.id || index"
                    class="col"
                >
                    <div
                        class="card h-100 shadow-sm"
                        style="cursor: pointer;"
                        @click="openMovie(recommendation.movie)"
                    >
                        <div class="position-relative">
                            <img
                                :src="recommendation.movie.cover"
                                class="card-img-top"
                                :alt="recommendation.movie.formatted_code"
                                @error="handleImageError"
                            >
                            <div class="position-absolute top-0 end-0 bg-dark bg-opacity-75 text-white px-2 py-1 m-2 rounded">
                                <small><i class="fas fa-eye"></i> {{ recommendation.movie.views ?? 0 }}</small>
                            </div>
                            <div class="position-absolute top-0 start-0 bg-purple bg-opacity-75 text-white px-2 py-1 m-2 rounded">
                                <small><i class="fas fa-star"></i></small>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title text-primary">{{ recommendation.movie.formatted_code }}</h6>
                            <p class="card-text text-truncate small" :title="recommendation.movie.title">{{ recommendation.movie.title }}</p>
                            <div
                                v-if="(recommendation.reasons?.actors?.length || 0) > 0 || (recommendation.reasons?.tags?.length || 0) > 0"
                                class="mb-2"
                            >
                                <span
                                    v-for="actorName in recommendation.reasons?.actors || []"
                                    :key="`actor-${recommendation.movie.id}-${actorName}`"
                                    class="badge bg-success"
                                >
                                    Because you liked actor: {{ actorName }}
                                </span>
                                <span
                                    v-for="tagName in recommendation.reasons?.tags || []"
                                    :key="`tag-${recommendation.movie.id}-${tagName}`"
                                    class="badge bg-info text-dark"
                                >
                                    Because you liked tag: {{ tagName }}
                                </span>
                            </div>
                            <div class="mt-2">
                                <span
                                    v-for="actor in (recommendation.movie.actors || []).slice(0, 2)"
                                    :key="`movie-actor-${recommendation.movie.id}-${actor.id || actor.name}`"
                                    class="badge bg-success text-xs"
                                >
                                    {{ actor.name || actor }}
                                </span>
                                <span
                                    v-if="(recommendation.movie.actors || []).length > 2"
                                    class="badge bg-secondary text-xs"
                                >
                                    +{{ (recommendation.movie.actors || []).length - 2 }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.card-img-top {
    height: 300px;
    object-fit: cover;
}
</style>
