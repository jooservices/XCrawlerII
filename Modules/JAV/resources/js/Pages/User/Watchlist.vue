<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import axios from 'axios';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';
import { useUIStore } from '@jav/Stores/ui';

const props = defineProps({
    watchlist: Object,
    status: String,
});

const uiStore = useUIStore();

const hasItems = computed(() => (props.watchlist?.data || []).length > 0);

const onStatusChange = (status) => {
    router.get(route('jav.vue.watchlist'), { status }, { preserveScroll: true });
};

const updateStatus = async (itemId, status) => {
    try {
        const response = await axios.put(route('jav.api.watchlist.update', itemId), { status });
        if (response.data?.success) {
            uiStore.showToast('Status updated successfully', 'success');
            router.reload({ preserveScroll: true, only: ['watchlist'] });
        }
    } catch (error) {
        uiStore.showToast('Failed to update watchlist status', 'error');
    }
};

const removeFromWatchlist = async (itemId) => {
    if (!window.confirm('Remove from watchlist?')) {
        return;
    }

    try {
        const response = await axios.delete(route('jav.api.watchlist.destroy', itemId));
        if (response.data?.success) {
            uiStore.showToast('Removed from watchlist', 'success');
            router.reload({ preserveScroll: true, only: ['watchlist'] });
        }
    } catch (error) {
        uiStore.showToast('Failed to remove from watchlist', 'error');
    }
};
</script>

<template>
    <Head title="My Watchlist" />

    <DashboardLayout>
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-md-12">
                    <h1 class="h3 mb-3"><i class="fas fa-bookmark me-2"></i>My Watchlist</h1>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="btn-group" role="group">
                        <button
                            type="button"
                            class="btn"
                            :class="status === 'all' ? 'btn-primary' : 'btn-outline-primary'"
                            @click="onStatusChange('all')"
                        >
                            <i class="fas fa-list me-1"></i>All ({{ watchlist.total || 0 }})
                        </button>
                        <button
                            type="button"
                            class="btn"
                            :class="status === 'to_watch' ? 'btn-primary' : 'btn-outline-primary'"
                            @click="onStatusChange('to_watch')"
                        >
                            <i class="fas fa-clock me-1"></i>To Watch
                        </button>
                        <button
                            type="button"
                            class="btn"
                            :class="status === 'watching' ? 'btn-primary' : 'btn-outline-primary'"
                            @click="onStatusChange('watching')"
                        >
                            <i class="fas fa-play me-1"></i>Watching
                        </button>
                        <button
                            type="button"
                            class="btn"
                            :class="status === 'watched' ? 'btn-primary' : 'btn-outline-primary'"
                            @click="onStatusChange('watched')"
                        >
                            <i class="fas fa-check me-1"></i>Watched
                        </button>
                    </div>
                </div>
            </div>

            <template v-if="hasItems">
                <div class="row">
                    <div v-for="item in watchlist.data" :key="item.id" class="col-md-3 mb-4">
                        <div class="card h-100">
                            <img :src="item.jav?.cover" class="card-img-top" :alt="item.jav?.title" loading="lazy">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <Link :href="route('jav.vue.movies.show', item.jav?.uuid || item.jav?.id)" class="text-decoration-none">
                                        {{ (item.jav?.title || '').slice(0, 50) }}{{ (item.jav?.title || '').length > 50 ? '...' : '' }}
                                    </Link>
                                </h6>
                                <p class="card-text">
                                    <small class="text-muted">{{ item.jav?.code }}</small>
                                </p>

                                <div class="mb-2">
                                    <span v-if="item.status === 'to_watch'" class="badge bg-info">To Watch</span>
                                    <span v-else-if="item.status === 'watching'" class="badge bg-warning">Watching</span>
                                    <span v-else class="badge bg-success">Watched</span>
                                </div>

                                <div class="input-group input-group-sm mb-2">
                                    <select
                                        class="form-select form-select-sm"
                                        :value="item.status"
                                        @change="updateStatus(item.id, $event.target.value)"
                                    >
                                        <option value="to_watch">To Watch</option>
                                        <option value="watching">Watching</option>
                                        <option value="watched">Watched</option>
                                    </select>
                                </div>

                                <button type="button" class="btn btn-sm btn-danger w-100" @click="removeFromWatchlist(item.id)">
                                    <i class="fas fa-trash me-1"></i>Remove
                                </button>

                                <div class="mt-2">
                                    <small class="text-muted">Added: {{ item.created_at_human || item.created_at }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li v-for="(link, key) in watchlist.links" :key="key" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                                <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label" />
                                <span v-else class="page-link" v-html="link.label" />
                            </li>
                        </ul>
                    </nav>
                </div>
            </template>

            <div v-else class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-bookmark fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Your watchlist is empty</h5>
                    <p class="text-muted">Start adding movies to your watchlist to keep track of what you want to watch!</p>
                    <Link :href="route('jav.vue.dashboard')" class="btn btn-primary">
                        <i class="fas fa-film me-1"></i>Browse Movies
                    </Link>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
