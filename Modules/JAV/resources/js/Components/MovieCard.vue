<template>
    <div class="card h-100 movie-card">
        <img
            :src="movie.cover || 'https://via.placeholder.com/300x400'"
            class="card-img-top"
            :alt="movie.title"
            style="height: 300px; object-fit: cover;"
        />
        <div class="card-body">
            <h6 class="card-title">{{ movie.title }}</h6>
            <p class="card-text text-muted small">
                <i class="fas fa-calendar me-1"></i>
                {{ formatDate(movie.date) }}
            </p>

            <div class="d-flex gap-2 mb-2">
                <span class="badge bg-primary">
                    <i class="fas fa-eye me-1"></i>
                    {{ movie.views || 0 }}
                </span>
                <span class="badge bg-success">
                    <i class="fas fa-download me-1"></i>
                    {{ movie.downloads || 0 }}
                </span>
            </div>

            <!-- Actors -->
            <div v-if="movie.actors && movie.actors.length" class="mb-2">
                <small class="text-muted d-block mb-1">Actors:</small>
                <div class="d-flex flex-wrap gap-1">
                    <span
                        v-for="actor in movie.actors.slice(0, 3)"
                        :key="actor.id"
                        class="badge bg-secondary"
                    >
                        {{ actor.name }}
                    </span>
                    <span v-if="movie.actors.length > 3" class="badge bg-secondary">
                        +{{ movie.actors.length - 3 }}
                    </span>
                </div>
            </div>

            <!-- Tags -->
            <div v-if="movie.tags && movie.tags.length" class="mb-2">
                <small class="text-muted d-block mb-1">Tags:</small>
                <div class="d-flex flex-wrap gap-1">
                    <span
                        v-for="tag in movie.tags.slice(0, 3)"
                        :key="tag.id"
                        class="badge bg-info"
                    >
                        {{ tag.name }}
                    </span>
                    <span v-if="movie.tags.length > 3" class="badge bg-info">
                        +{{ movie.tags.length - 3 }}
                    </span>
                </div>
            </div>

            <Link
                :href="`/jav/movies-vue/${movie.id}`"
                class="btn btn-primary btn-sm w-100 mt-2"
            >
                View Details
            </Link>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    movie: {
        type: Object,
        required: true,
    },
});

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString();
};
</script>

<style scoped>
.movie-card {
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
}

.movie-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.card-title {
    font-size: 0.9rem;
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
</style>
