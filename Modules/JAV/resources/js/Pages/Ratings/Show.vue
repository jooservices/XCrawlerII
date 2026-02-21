<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useUIStore } from '@core/Stores/ui';
import PageShell from '@core/Components/UI/PageShell.vue';
import SectionHeader from '@core/Components/UI/SectionHeader.vue';

const props = defineProps({
    rating: Object,
});

const page = usePage();
const uiStore = useUIStore();

const authId = page.props.auth?.user?.id || null;

const deleteRating = async () => {
    if (!window.confirm('Are you sure you want to delete this rating?')) {
        return;
    }

    try {
        const response = await axios.delete(route('jav.api.ratings.destroy', props.rating.id));
        if (response.data?.success) {
            uiStore.showToast('Rating deleted successfully', 'success');
            router.visit(route('jav.vue.ratings'));
        }
    } catch (error) {
        uiStore.showToast('Failed to delete rating', 'error');
    }
};
</script>

<template>
    <Head title="Rating Details" />

    <PageShell>
        <template #header>
            <SectionHeader title="Rating Details" subtitle="Inspect score, review, and author" />
        </template>

            <div class="ui-row">
                <div class="ui-col-12">
                    <div class="ui-card">
                        <div class="ui-card-body">
                            <div class="mb-3">
                                <h5>Movie</h5>
                                <Link :href="route('jav.vue.movies.show', rating.jav?.uuid || rating.jav?.id)">
                                    {{ rating.jav?.title }}
                                </Link>
                            </div>

                            <div class="mb-3">
                                <h5>Rating</h5>
                                <div>
                                    <i
                                        v-for="i in 5"
                                        :key="`show-rating-star-${i}`"
                                        class="fas fa-star"
                                        :class="i <= rating.rating ? 'u-text-warning' : 'u-text-muted'"
                                    ></i>
                                    <span class="ml-2">{{ rating.rating }}/5</span>
                                </div>
                            </div>

                            <div v-if="rating.review" class="mb-3">
                                <h5>Review</h5>
                                <p>{{ rating.review }}</p>
                            </div>

                            <div class="mb-3">
                                <h5>Rated By</h5>
                                <p>{{ rating.user?.name }}</p>
                            </div>

                            <div class="mb-3">
                                <small class="u-text-muted">
                                    Created: {{ rating.created_at }}
                                    <template v-if="rating.updated_at && rating.updated_at !== rating.created_at">
                                        • Updated: {{ rating.updated_at }}
                                    </template>
                                </small>
                            </div>

                            <div v-if="authId && rating.user_id === authId" class="mt-4">
                                <button type="button" class="ui-btn ui-btn-danger" @click="deleteRating">
                                    <i class="fas fa-trash mr-2"></i>Delete Rating
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </PageShell>
</template>
