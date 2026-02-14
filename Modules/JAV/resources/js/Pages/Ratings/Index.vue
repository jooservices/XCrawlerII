<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';
import { useUIStore } from '@jav/Stores/ui';

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

    <DashboardLayout>
        <div class="container-fluid py-4">
            <div class="row mb-4">
                <div class="col-12">
                    <h2><i class="fas fa-star me-2"></i>Ratings & Reviews</h2>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div v-if="(ratings.data || []).length === 0" class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-star fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No ratings yet</p>
                        </div>
                    </div>

                    <div v-else class="card">
                        <div class="card-body">
                            <div v-for="rating in ratings.data" :key="rating.id" class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="mb-2">
                                            <i
                                                v-for="i in 5"
                                                :key="`star-${rating.id}-${i}`"
                                                class="fas fa-star"
                                                :class="i <= rating.rating ? 'text-warning' : 'text-muted'"
                                            ></i>
                                            <span class="ms-2 text-muted">{{ rating.rating }}/5</span>
                                        </div>
                                        <p v-if="rating.review" class="mb-2">{{ rating.review }}</p>
                                        <small class="text-muted">
                                            by {{ rating.user?.name }} • {{ rating.created_at_human || rating.created_at }}
                                        </small>
                                        <div class="mt-1">
                                            <Link :href="route('jav.vue.ratings.show', rating.id)" class="small text-decoration-none">View details</Link>
                                        </div>
                                    </div>

                                    <div v-if="authId && rating.user_id === authId">
                                        <button type="button" class="btn btn-sm btn-outline-danger" @click="deleteRating(rating.id)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination">
                                        <li v-for="(link, k) in ratings.links" :key="k" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                                            <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label" />
                                            <span v-else class="page-link" v-html="link.label"></span>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
