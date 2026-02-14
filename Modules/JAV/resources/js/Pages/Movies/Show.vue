<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';
import { computed, ref } from 'vue';
import axios from 'axios';
import { useUIStore } from '@jav/Stores/ui';

const props = defineProps({
    jav: Object,
    relatedByActors: Object,
    relatedByTags: Object,
    isLiked: Boolean,
});

const uiStore = useUIStore();
const page = usePage();
const localIsLiked = ref(props.isLiked);
const isProcessing = ref(false);
const hasAuthUser = computed(() => Boolean(page.props.auth?.user));

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const resolveName = (obj) => {
    if (typeof obj === 'string') return obj;
    return obj?.name || '';
};

const handleFavorite = async () => {
    if (isProcessing.value) return;
    isProcessing.value = true;

    try {
        const response = await axios.post(route('jav.api.toggle-like'), {
            id: props.jav.id,
            type: 'jav',
        });

        if (response.data.success) {
            localIsLiked.value = response.data.liked;
            uiStore.showToast(response.data.liked ? 'Added to favorites' : 'Removed from favorites', 'success');
        }
    } catch (error) {
        uiStore.showToast('Failed to update favorite status', 'error');
    } finally {
        isProcessing.value = false;
    }
};
</script>

<template>
    <Head :title="`${jav.formatted_code || jav.code} ${jav.title}`" />

    <DashboardLayout>
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-md-6">
                    <img
                        :src="jav.cover"
                        class="img-fluid rounded shadow"
                        :alt="jav.formatted_code || jav.code"
                        @error="(e) => { e.target.src = 'https://placehold.co/400x600?text=No+Image'; }"
                    >
                </div>
                <div class="col-md-6">
                    <h2 class="text-primary">{{ jav.formatted_code || jav.code }}</h2>
                    <h4 class="text-muted">{{ jav.title }}</h4>

                    <div class="mt-3">
                        <p><strong><i class="fas fa-calendar-alt"></i> Date:</strong> {{ formatDate(jav.date) }}</p>
                        <p v-if="jav.size"><strong><i class="fas fa-hdd"></i> Size:</strong> {{ jav.size }} GB</p>
                        <p><strong><i class="fas fa-eye"></i> Views:</strong> {{ jav.views || 0 }}</p>
                        <p><strong><i class="fas fa-download"></i> Downloads:</strong> {{ jav.downloads || 0 }}</p>
                    </div>

                    <div class="mb-3">
                        <strong><i class="fas fa-users"></i> Actors:</strong><br>
                        <template v-if="jav.actors && jav.actors.length > 0">
                            <Link
                                v-for="(actor, index) in jav.actors"
                                :key="`actor-${index}`"
                                :href="route('jav.vue.dashboard', { actor: resolveName(actor) })"
                                class="badge bg-success text-decoration-none me-1 mb-1"
                            >
                                {{ resolveName(actor) }}
                            </Link>
                        </template>
                        <span v-else class="text-muted">No actors listed</span>
                    </div>

                    <div class="mb-3">
                        <strong><i class="fas fa-tags"></i> Tags:</strong><br>
                        <template v-if="jav.tags && jav.tags.length > 0">
                            <Link
                                v-for="(tag, index) in jav.tags"
                                :key="`tag-${index}`"
                                :href="route('jav.vue.dashboard', { tag: resolveName(tag) })"
                                class="badge bg-info text-dark text-decoration-none me-1 mb-1"
                            >
                                {{ resolveName(tag) }}
                            </Link>
                        </template>
                        <span v-else class="text-muted">No tags listed</span>
                    </div>

                    <div class="mb-3">
                        <strong><i class="fas fa-info-circle"></i> Description:</strong>
                        <p class="mt-2">{{ jav.description || 'No description available.' }}</p>
                    </div>

                    <div class="mt-4">
                        <button
                            v-if="hasAuthUser"
                            class="btn btn-lg me-2"
                            :class="localIsLiked ? 'btn-danger' : 'btn-outline-danger'"
                            :disabled="isProcessing"
                            @click="handleFavorite"
                        >
                            <i class="fas fa-heart"></i> {{ localIsLiked ? 'Liked' : 'Like' }}
                        </button>
                        <a :href="route('jav.movies.download', jav.uuid || jav.id)" class="btn btn-primary btn-lg me-2">
                            <i class="fas fa-download"></i> Download Torrent
                        </a>
                        <Link :href="route('jav.vue.dashboard')" class="btn btn-secondary btn-lg">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </Link>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <template v-if="relatedByActors && relatedByActors.length > 0">
                <div class="row mb-5">
                    <div class="col-12">
                        <h3><i class="fas fa-users"></i> Related Movies by Actors</h3>
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-4 mb-5">
                    <div v-for="item in relatedByActors" :key="`actor-related-${item.id}`" class="col">
                        <Link :href="route('jav.vue.movies.show', item.uuid || item.id)" class="text-decoration-none text-dark">
                            <div class="card h-100 shadow-sm" style="cursor: pointer;">
                                <div class="position-relative">
                                    <img
                                        :src="item.cover"
                                        class="card-img-top"
                                        :alt="item.formatted_code || item.code"
                                        @error="(e) => { e.target.src = 'https://via.placeholder.com/300x400?text=No+Image'; }"
                                    >
                                    <div class="position-absolute top-0 end-0 bg-dark bg-opacity-75 text-white px-2 py-1 m-2 rounded">
                                        <small><i class="fas fa-eye"></i> {{ item.views || 0 }}</small>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title text-primary">{{ item.formatted_code || item.code }}</h6>
                                    <p class="card-text text-truncate small" :title="item.title">{{ item.title }}</p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </template>

            <template v-if="relatedByTags && relatedByTags.length > 0">
                <div class="row mb-5">
                    <div class="col-12">
                        <h3><i class="fas fa-tags"></i> Related Movies by Tags</h3>
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-4">
                    <div v-for="item in relatedByTags" :key="`tag-related-${item.id}`" class="col">
                        <Link :href="route('jav.vue.movies.show', item.uuid || item.id)" class="text-decoration-none text-dark">
                            <div class="card h-100 shadow-sm" style="cursor: pointer;">
                                <div class="position-relative">
                                    <img
                                        :src="item.cover"
                                        class="card-img-top"
                                        :alt="item.formatted_code || item.code"
                                        @error="(e) => { e.target.src = 'https://via.placeholder.com/300x400?text=No+Image'; }"
                                    >
                                    <div class="position-absolute top-0 end-0 bg-dark bg-opacity-75 text-white px-2 py-1 m-2 rounded">
                                        <small><i class="fas fa-eye"></i> {{ item.views || 0 }}</small>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title text-primary">{{ item.formatted_code || item.code }}</h6>
                                    <p class="card-text text-truncate small" :title="item.title">{{ item.title }}</p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </template>
        </div>
    </DashboardLayout>
</template>
