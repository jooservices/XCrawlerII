<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';
import MovieCard from '@jav/Components/MovieCard.vue';

const props = defineProps({
    actor: Object,
    movies: Object,
    bioProfile: Object,
    primarySource: String,
    primarySyncedAt: String,
    primarySyncedAtFormatted: String,
});
</script>

<template>
    <Head :title="actor.name" />

    <DashboardLayout>
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-md-4">
                    <img
                        :src="actor.cover"
                        class="img-fluid rounded shadow"
                        :alt="actor.name"
                        @error="(e) => { e.target.src = 'https://placehold.co/400x600?text=No+Image'; }"
                    >
                </div>
                <div class="col-md-8">
                    <h2 class="mb-2">{{ actor.name }}</h2>
                    <div class="mb-3">
                        <span class="badge bg-secondary">{{ actor.javs_count || 0 }} JAVs</span>
                        <span v-if="primarySource" class="badge bg-dark">{{ String(primarySource).toUpperCase() }} Primary</span>
                        <span v-if="primarySyncedAtFormatted" class="badge bg-info text-dark">Synced: {{ primarySyncedAtFormatted }}</span>
                    </div>

                    <div class="mb-3">
                        <Link :href="route('jav.vue.dashboard', { actor: actor.name })" class="btn btn-success btn-sm me-2">
                            <i class="fas fa-film me-1"></i> Show All JAVs
                        </Link>
                        <Link :href="route('jav.vue.actors')" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back to Actors
                        </Link>
                    </div>

                    <h5>Bio Profile</h5>
                    <div v-if="bioProfile && Object.keys(bioProfile).length > 0" class="table-responsive">
                        <table class="table table-sm table-bordered bg-white">
                            <tbody>
                                <tr v-for="(value, label) in bioProfile" :key="`bio-${label}`">
                                    <th style="width: 220px;">{{ label }}</th>
                                    <td>{{ value }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="alert alert-warning mb-0">
                        No profile data synced yet.
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="row mb-3">
                <div class="col-12">
                    <h4>JAVs</h4>
                </div>
            </div>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-4">
                <MovieCard v-for="item in movies.data" :key="item.id" :item="item" />
                <div v-if="movies.data.length === 0" class="col-12">
                    <div class="alert alert-warning text-center mb-0">
                        No JAVs found for this actor.
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-center">
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <li v-for="(link, k) in movies.links" :key="k" class="page-item" :class="{ 'active': link.active, 'disabled': !link.url }">
                            <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label" />
                            <span v-else class="page-link" v-html="link.label"></span>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </DashboardLayout>
</template>
