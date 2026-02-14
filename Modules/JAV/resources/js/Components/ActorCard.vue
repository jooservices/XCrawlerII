<template>
    <div class="card text-center h-100 actor-card">
        <div class="card-body">
            <i class="fas fa-user-circle fa-3x text-primary mb-2"></i>
            <h6 class="card-title mb-1">{{ actor.name }}</h6>
            <p v-if="actor.movie_count" class="card-text text-muted small mb-0">
                {{ actor.movie_count }} movies
            </p>
            <Link
                :href="`/jav/actors-vue?filter=${actor.name}`"
                class="btn btn-sm btn-outline-primary mt-2"
            >
                View Movies
            </Link>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    actor: {
        type: Object,
        required: true,
    },
});
</script>

<style scoped>
.actor-card {
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
}

.actor-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}
</style>
