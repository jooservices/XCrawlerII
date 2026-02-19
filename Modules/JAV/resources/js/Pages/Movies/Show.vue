<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import { useUIStore } from '@jav/Stores/ui';
import PageShell from '@jav/Components/UI/PageShell.vue';
import SectionHeader from '@jav/Components/UI/SectionHeader.vue';
import MovieCard from '@jav/Components/MovieCard.vue';
import analyticsService from '@core/Services/analyticsService';

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

onMounted(async () => {
    try {
        await analyticsService.track('view', 'movie', props.jav?.uuid);
    } catch {
        // swallow analytics errors to avoid breaking page UX
    }
});
</script>

<template>
    <Head :title="`${jav.formatted_code || jav.code} ${jav.title}`" />

    <PageShell>
        <template #header>
            <SectionHeader :title="jav.formatted_code || jav.code" :subtitle="jav.title" />
        </template>

        <template #actions>
            <Link :href="route('jav.vue.dashboard')" class="ui-btn ui-btn-secondary ui-btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </Link>
        </template>

            <div class="ui-row mb-4">
                <div class="ui-col-md-6">
                    <img
                        :src="jav.cover"
                        class="img-fluid u-rounded u-shadow"
                        :alt="jav.formatted_code || jav.code"
                        @error="(e) => { e.target.src = 'https://placehold.co/400x600?text=No+Image'; }"
                    >
                </div>
                <div class="ui-col-md-6">
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
                                class="ui-badge u-bg-success u-no-underline mr-1 mb-1"
                            >
                                {{ resolveName(actor) }}
                            </Link>
                        </template>
                        <span v-else class="u-text-muted">No actors listed</span>
                    </div>

                    <div class="mb-3">
                        <strong><i class="fas fa-tags"></i> Tags:</strong><br>
                        <template v-if="jav.tags && jav.tags.length > 0">
                            <Link
                                v-for="(tag, index) in jav.tags"
                                :key="`tag-${index}`"
                                :href="route('jav.vue.dashboard', { tag: resolveName(tag) })"
                                class="ui-badge u-bg-info u-text-dark u-no-underline mr-1 mb-1"
                            >
                                {{ resolveName(tag) }}
                            </Link>
                        </template>
                        <span v-else class="u-text-muted">No tags listed</span>
                    </div>

                    <div class="mb-3">
                        <strong><i class="fas fa-info-circle"></i> Description:</strong>
                        <p class="mt-2">{{ jav.description || 'No description available.' }}</p>
                    </div>

                    <div class="mt-4">
                        <button
                            v-if="hasAuthUser"
                            class="ui-btn ui-btn-lg mr-2"
                            :class="localIsLiked ? 'ui-btn-danger' : 'ui-btn-outline-danger'"
                            :disabled="isProcessing"
                            @click="handleFavorite"
                        >
                            <i class="fas fa-heart"></i> {{ localIsLiked ? 'Liked' : 'Like' }}
                        </button>
                        <a :href="route('jav.movies.download', jav.uuid || jav.id)" class="ui-btn ui-btn-primary ui-btn-lg mr-2">
                            <i class="fas fa-download"></i> Download Torrent
                        </a>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <template v-if="relatedByActors && relatedByActors.length > 0">
                <div class="ui-row mb-5">
                    <div class="ui-col-12">
                        <h3><i class="fas fa-users"></i> Related Movies by Actors</h3>
                    </div>
                </div>
                <div class="ui-row ui-row-cols-1 ui-row-cols-md-3 ui-row-cols-lg-5 ui-g-4 mb-5">
                    <MovieCard v-for="item in relatedByActors" :key="`actor-related-${item.id}`" :item="item" />
                </div>
            </template>

            <template v-if="relatedByTags && relatedByTags.length > 0">
                <div class="ui-row mb-5">
                    <div class="ui-col-12">
                        <h3><i class="fas fa-tags"></i> Related Movies by Tags</h3>
                    </div>
                </div>
                <div class="ui-row ui-row-cols-1 ui-row-cols-md-3 ui-row-cols-lg-5 ui-g-4">
                    <MovieCard v-for="item in relatedByTags" :key="`tag-related-${item.id}`" :item="item" />
                </div>
            </template>
    </PageShell>
</template>
