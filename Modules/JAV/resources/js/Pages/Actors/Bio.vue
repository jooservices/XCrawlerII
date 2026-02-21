<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MovieCard from '@jav/Components/MovieCard.vue';
import ActorInsightsPanel from '@jav/Components/ActorInsightsPanel.vue';
import PageShell from '@core/Components/UI/PageShell.vue';
import SectionHeader from '@core/Components/UI/SectionHeader.vue';
import EmptyState from '@jav/Components/UI/EmptyState.vue';

const props = defineProps({
    actor: Object,
    movies: Object,
    bioProfile: Object,
    actorInsights: Object,
    primarySource: String,
    primarySyncedAt: String,
    primarySyncedAtFormatted: String,
});
</script>

<template>
    <Head :title="actor.name" />

    <PageShell>
        <template #header>
            <SectionHeader :title="actor.name" subtitle="Actor profile and related titles" />
        </template>

        <template #actions>
            <Link :href="route('jav.vue.actors')" class="ui-btn ui-btn-secondary ui-btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Actors
            </Link>
        </template>

            <div class="ui-row mb-4">
                <div class="ui-col-md-4">
                    <img
                        :src="actor.cover"
                        class="img-fluid u-rounded u-shadow"
                        :alt="actor.name"
                        @error="(e) => { e.target.src = 'https://placehold.co/400x600?text=No+Image'; }"
                    >
                </div>
                <div class="ui-col-md-8">
                    <div class="mb-3">
                        <span class="ui-badge u-bg-secondary">{{ actor.javs_count || 0 }} JAVs</span>
                        <span v-if="primarySource" class="ui-badge u-bg-dark">{{ String(primarySource).toUpperCase() }} Primary</span>
                        <span v-if="primarySyncedAtFormatted" class="ui-badge u-bg-info u-text-dark">Synced: {{ primarySyncedAtFormatted }}</span>
                    </div>

                    <div class="mb-3">
                        <Link :href="route('jav.vue.dashboard', { actor: actor.name })" class="ui-btn ui-btn-success ui-btn-sm mr-2">
                            <i class="fas fa-film mr-1"></i> Show All JAVs
                        </Link>
                    </div>

                    <h5>Bio Profile</h5>
                    <div v-if="bioProfile && Object.keys(bioProfile).length > 0" class="ui-table-responsive">
                        <table class="ui-table ui-table-sm ui-table-bordered u-bg-white">
                            <tbody>
                                <tr v-for="(value, label) in bioProfile" :key="`bio-${label}`">
                                    <th class="u-w-220">{{ label }}</th>
                                    <td>{{ value }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <EmptyState v-else tone="warning" icon="fas fa-id-card" message="No profile data synced yet." />

                    <div class="mt-4">
                        <ActorInsightsPanel :actor-insights="actorInsights" title="Actor Analytics" :show-title="true" :show-actor-genres="true" />
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="ui-row mb-3">
                <div class="ui-col-12">
                    <h4>JAVs</h4>
                </div>
            </div>
            <div class="movie-masonry-grid">
                <MovieCard v-for="item in movies.data" :key="item.id" :item="item" />
                <div v-if="movies.data.length === 0" class="ui-col-12">
                    <EmptyState tone="warning" icon="fas fa-film" message="No JAVs found for this actor." />
                </div>
            </div>

            <div class="mt-4 u-flex u-justify-center">
                <nav aria-label="Page navigation">
                    <ul class="ui-pagination">
                        <li v-for="(link, k) in movies.links" :key="k" class="ui-page-item" :class="{ 'active': link.active, 'disabled': !link.url }">
                            <Link v-if="link.url" class="ui-page-link" :href="link.url" v-html="link.label" />
                            <span v-else class="ui-page-link" v-html="link.label"></span>
                        </li>
                    </ul>
                </nav>
            </div>
    </PageShell>
</template>

<style scoped>
.movie-masonry-grid {
    column-count: 1;
    column-gap: 1rem;
}

.movie-masonry-grid > .ui-col,
.movie-masonry-grid > .ui-col-12 {
    break-inside: avoid;
    margin-bottom: 1rem;
}

.movie-masonry-grid > .ui-col-12 {
    column-span: all;
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
