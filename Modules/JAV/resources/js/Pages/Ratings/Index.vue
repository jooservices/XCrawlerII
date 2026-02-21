<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useUIStore } from '@core/Stores/ui';
import PageShell from '@core/Components/UI/PageShell.vue';
import SectionHeader from '@core/Components/UI/SectionHeader.vue';
import EmptyState from '@jav/Components/UI/EmptyState.vue';

const props = defineProps({
    ratings: Object,
});

const page = usePage();
const uiStore = useUIStore();

const authId = page.props.auth?.user?.id || null;

const deleteRating = async (id) => {
    if (!window.confirm('Are you sure you want to delete this rating?')) {
        return;
    }

    try {
        const response = await axios.delete(route('jav.api.ratings.destroy', id));
        if (response.data?.success) {
            uiStore.showToast('Rating deleted successfully', 'success');
            router.reload({ preserveScroll: true, only: ['ratings'] });
        }
    } catch (error) {
        uiStore.showToast('Failed to delete rating', 'error');
    }
};
</script>

<template>
    <Head title="Ratings & Reviews" />

    <PageShell>
        <template #header>
            <SectionHeader title="Ratings & Reviews" subtitle="Your and community movie ratings" />
        </template>

        <div class="ui-row">
            <div class="ui-col-12">
                <EmptyState
                    v-if="(ratings.data || []).length === 0"
                    tone="info"
                    icon="fas fa-star"
                    message="No ratings yet. Start rating movies to build your profile."
                />

                <div v-else class="ui-card">
                    <div class="ui-card-body">
                        <div v-for="rating in ratings.data" :key="rating.id" class="u-border-bottom pb-3 mb-3">
                            <div class="u-flex u-justify-between u-items-start">
                                <div>
                                    <div class="mb-2">
                                        <i
                                            v-for="i in 5"
                                            :key="`star-${rating.id}-${i}`"
                                            class="fas fa-star"
                                            :class="i <= rating.rating ? 'u-text-warning' : 'u-text-muted'"
                                        ></i>
                                        <span class="ml-2 u-text-muted">{{ rating.rating }}/5</span>
                                    </div>
                                    <p v-if="rating.review" class="mb-2">{{ rating.review }}</p>
                                    <small class="u-text-muted">
                                        by {{ rating.user?.name }} • {{ rating.created_at_human || rating.created_at }}
                                    </small>
                                    <div class="mt-1">
                                        <Link :href="route('jav.vue.ratings.show', rating.id)" class="small u-no-underline">View details</Link>
                                    </div>
                                </div>

                                <div v-if="authId && rating.user_id === authId">
                                    <button type="button" class="ui-btn ui-btn-sm ui-btn-outline-danger" @click="deleteRating(rating.id)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <nav aria-label="Page navigation">
                                <ul class="ui-pagination">
                                    <li v-for="(link, k) in ratings.links" :key="k" class="ui-page-item" :class="{ active: link.active, disabled: !link.url }">
                                        <Link v-if="link.url" class="ui-page-link" :href="link.url" v-html="link.label" />
                                        <span v-else class="ui-page-link" v-html="link.label"></span>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PageShell>
</template>
