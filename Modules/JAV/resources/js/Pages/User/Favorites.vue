<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';

const props = defineProps({
    favorites: Object,
});

</script>

<template>
    <Head title="Favorites" />

    <DashboardLayout>
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h2><i class="fas fa-heart text-danger"></i> My Favorites</h2>
                    <p class="text-muted">Movies, actors, and tags you've liked</p>
                </div>
            </div>

            <div v-if="favorites.data.length === 0" class="alert alert-info">
                <i class="fas fa-info-circle"></i> You haven't liked anything yet. Start exploring and save your favorites!
            </div>

            <template v-else>
                <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-4">
                    <div v-for="favorite in favorites.data" :key="favorite.id" class="col">
                        <template v-if="favorite.favoritable_type === 'Modules\\JAV\\Models\\Jav'">
                            <Link :href="route('jav.vue.movies.show', favorite.favoritable?.uuid || favorite.favoritable?.id)" class="text-decoration-none text-dark">
                                <div class="card h-100 shadow-sm" style="cursor: pointer;">
                                    <div class="position-relative">
                                        <img
                                            :src="favorite.favoritable?.cover"
                                            class="card-img-top"
                                            :alt="favorite.favoritable?.formatted_code"
                                            @error="(e) => { e.target.src = 'https://placehold.co/300x400?text=No+Image'; }"
                                        >
                                        <div class="position-absolute top-0 start-0 bg-danger text-white px-2 py-1 m-2 rounded">
                                            <i class="fas fa-heart"></i>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title text-primary">{{ favorite.favoritable?.formatted_code }}</h6>
                                        <p class="card-text text-truncate small" :title="favorite.favoritable?.title">
                                            {{ favorite.favoritable?.title }}
                                        </p>
                                        <small class="text-muted">Liked {{ favorite.created_at_human || favorite.created_at }}</small>
                                    </div>
                                </div>
                            </Link>
                        </template>

                        <template v-else-if="favorite.favoritable_type === 'Modules\\JAV\\Models\\Actor'">
                            <Link :href="route('jav.vue.dashboard', { actor: favorite.favoritable?.name })" class="text-decoration-none text-dark">
                                <div class="card h-100 shadow-sm bg-success bg-opacity-10" style="cursor: pointer;">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user fa-4x text-success mb-3"></i>
                                        <h5 class="card-title">{{ favorite.favoritable?.name }}</h5>
                                        <span class="badge bg-success"><i class="fas fa-users"></i> Actor</span>
                                        <p class="text-muted small mt-2">Liked {{ favorite.created_at_human || favorite.created_at }}</p>
                                    </div>
                                </div>
                            </Link>
                        </template>

                        <template v-else-if="favorite.favoritable_type === 'Modules\\JAV\\Models\\Tag'">
                            <Link :href="route('jav.vue.dashboard', { tag: favorite.favoritable?.name })" class="text-decoration-none text-dark">
                                <div class="card h-100 shadow-sm bg-info bg-opacity-10" style="cursor: pointer;">
                                    <div class="card-body text-center">
                                        <i class="fas fa-tag fa-4x text-info mb-3"></i>
                                        <h5 class="card-title">{{ favorite.favoritable?.name }}</h5>
                                        <span class="badge bg-info"><i class="fas fa-tags"></i> Tag</span>
                                        <p class="text-muted small mt-2">Liked {{ favorite.created_at_human || favorite.created_at }}</p>
                                    </div>
                                </div>
                            </Link>
                        </template>
                    </div>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li v-for="(link, k) in favorites.links" :key="k" class="page-item" :class="{ 'active': link.active, 'disabled': !link.url }">
                                <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label" />
                                <span v-else class="page-link" v-html="link.label"></span>
                            </li>
                        </ul>
                    </nav>
                </div>
            </template>
        </div>
    </DashboardLayout>
</template>
