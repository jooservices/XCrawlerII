<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MovieCard from '@jav/Components/MovieCard.vue';
import PageShell from '@jav/Components/UI/PageShell.vue';
import SectionHeader from '@jav/Components/UI/SectionHeader.vue';
import EmptyState from '@jav/Components/UI/EmptyState.vue';

const props = defineProps({
    favorites: Object,
});

const movieFavoriteType = 'Modules\\JAV\\Models\\Jav';
const actorFavoriteType = 'Modules\\JAV\\Models\\Actor';
const tagFavoriteType = 'Modules\\JAV\\Models\\Tag';

const isMovieFavorite = (favorite) => favorite?.favoritable_type === movieFavoriteType;
const toMovieItem = (favorite) => {
    const movie = favorite?.favoritable || {};

    return {
        ...movie,
        actors: Array.isArray(movie.actors) ? movie.actors : [],
        tags: Array.isArray(movie.tags) ? movie.tags : [],
        is_liked: true,
        in_watchlist: Boolean(movie.in_watchlist),
        watchlist_id: movie.watchlist_id || null,
        user_rating: movie.user_rating || 0,
        user_rating_id: movie.user_rating_id || null,
    };
};
</script>

<template>
    <Head title="Favorites" />

    <PageShell>
        <template #header>
            <SectionHeader title="Favorites" subtitle="Movies, actors, and tags you've liked" />
        </template>

        <EmptyState
            v-if="favorites.data.length === 0"
            tone="info"
            icon="fas fa-heart"
            message="You haven't liked anything yet. Start exploring and save your favorites!"
        />

        <template v-else>
            <div class="ui-row ui-row-cols-1 ui-row-cols-md-3 ui-row-cols-lg-5 ui-g-4">
                <template v-for="favorite in favorites.data" :key="favorite.id">
                    <MovieCard
                        v-if="isMovieFavorite(favorite)"
                        :item="toMovieItem(favorite)"
                    />

                    <div v-else class="ui-col">
                        <template v-if="favorite.favoritable_type === actorFavoriteType">
                            <Link :href="route('jav.vue.dashboard', { actor: favorite.favoritable?.name })" class="u-no-underline u-text-dark">
                                <div class="ui-card ui-interactive-card u-h-full u-bg-success u-bg-opacity-10">
                                    <div class="ui-card-body u-text-center">
                                        <i class="fas fa-user fa-4x u-text-success mb-3"></i>
                                        <h5 class="ui-card-title">{{ favorite.favoritable?.name }}</h5>
                                        <span class="ui-badge u-bg-success"><i class="fas fa-users"></i> Actor</span>
                                        <p class="u-text-muted small mt-2">Liked {{ favorite.created_at_human || favorite.created_at }}</p>
                                    </div>
                                </div>
                            </Link>
                        </template>

                        <template v-else-if="favorite.favoritable_type === tagFavoriteType">
                            <Link :href="route('jav.vue.dashboard', { tag: favorite.favoritable?.name })" class="u-no-underline u-text-dark">
                                <div class="ui-card ui-interactive-card u-h-full u-bg-info u-bg-opacity-10">
                                    <div class="ui-card-body u-text-center">
                                        <i class="fas fa-tag fa-4x u-text-info mb-3"></i>
                                        <h5 class="ui-card-title">{{ favorite.favoritable?.name }}</h5>
                                        <span class="ui-badge u-bg-info"><i class="fas fa-tags"></i> Tag</span>
                                        <p class="u-text-muted small mt-2">Liked {{ favorite.created_at_human || favorite.created_at }}</p>
                                    </div>
                                </div>
                            </Link>
                        </template>
                    </div>
                </template>
            </div>

            <div class="u-flex u-justify-center mt-4">
                <nav aria-label="Page navigation">
                    <ul class="ui-pagination">
                        <li v-for="(link, k) in favorites.links" :key="k" class="ui-page-item" :class="{ 'active': link.active, 'disabled': !link.url }">
                            <Link v-if="link.url" class="ui-page-link" :href="link.url" v-html="link.label" />
                            <span v-else class="ui-page-link" v-html="link.label"></span>
                        </li>
                    </ul>
                </nav>
            </div>
        </template>
    </PageShell>
</template>
