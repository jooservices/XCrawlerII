<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import axios from 'axios';
import { useUIStore } from '@core/Stores/ui';
import PageShell from '@core/Components/UI/PageShell.vue';
import SectionHeader from '@core/Components/UI/SectionHeader.vue';
import EmptyState from '@jav/Components/UI/EmptyState.vue';

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

    <PageShell>
        <template #header>
            <SectionHeader title="My Watchlist" subtitle="Track what to watch next" />
        </template>

            <div class="ui-card mb-4">
                <div class="ui-card-body">
                    <div class="ui-btn-group" role="group">
                        <button
                            type="button"
                            class="ui-btn"
                            :class="status === 'all' ? 'ui-btn-primary' : 'ui-btn-outline-primary'"
                            @click="onStatusChange('all')"
                        >
                            <i class="fas fa-list mr-1"></i>All ({{ watchlist.total || 0 }})
                        </button>
                        <button
                            type="button"
                            class="ui-btn"
                            :class="status === 'to_watch' ? 'ui-btn-primary' : 'ui-btn-outline-primary'"
                            @click="onStatusChange('to_watch')"
                        >
                            <i class="fas fa-clock mr-1"></i>To Watch
                        </button>
                        <button
                            type="button"
                            class="ui-btn"
                            :class="status === 'watching' ? 'ui-btn-primary' : 'ui-btn-outline-primary'"
                            @click="onStatusChange('watching')"
                        >
                            <i class="fas fa-play mr-1"></i>Watching
                        </button>
                        <button
                            type="button"
                            class="ui-btn"
                            :class="status === 'watched' ? 'ui-btn-primary' : 'ui-btn-outline-primary'"
                            @click="onStatusChange('watched')"
                        >
                            <i class="fas fa-check mr-1"></i>Watched
                        </button>
                    </div>
                </div>
            </div>

            <template v-if="hasItems">
                <div class="ui-row">
                    <div v-for="item in watchlist.data" :key="item.id" class="ui-col-md-3 mb-4">
                        <div class="ui-card u-h-full">
                            <img :src="item.jav?.cover" class="ui-card-img-top" :alt="item.jav?.title" loading="lazy">
                            <div class="ui-card-body">
                                <h6 class="ui-card-title">
                                    <Link :href="route('jav.vue.movies.show', item.jav?.uuid || item.jav?.id)" class="u-no-underline">
                                        {{ (item.jav?.title || '').slice(0, 50) }}{{ (item.jav?.title || '').length > 50 ? '...' : '' }}
                                    </Link>
                                </h6>
                                <p class="ui-card-text">
                                    <small class="u-text-muted">{{ item.jav?.code }}</small>
                                </p>

                                <div class="mb-2">
                                    <span v-if="item.status === 'to_watch'" class="ui-badge u-bg-info">To Watch</span>
                                    <span v-else-if="item.status === 'watching'" class="ui-badge u-bg-warning">Watching</span>
                                    <span v-else class="ui-badge u-bg-success">Watched</span>
                                </div>

                                <div class="ui-input-group ui-input-group-sm mb-2">
                                    <select
                                        class="ui-form-select ui-form-select-sm"
                                        :value="item.status"
                                        @change="updateStatus(item.id, $event.target.value)"
                                    >
                                        <option value="to_watch">To Watch</option>
                                        <option value="watching">Watching</option>
                                        <option value="watched">Watched</option>
                                    </select>
                                </div>

                                <button type="button" class="ui-btn ui-btn-sm ui-btn-danger u-w-full" @click="removeFromWatchlist(item.id)">
                                    <i class="fas fa-trash mr-1"></i>Remove
                                </button>

                                <div class="mt-2">
                                    <small class="u-text-muted">Added: {{ item.created_at_human || item.created_at }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="u-flex u-justify-center mt-4">
                    <nav aria-label="Page navigation">
                        <ul class="ui-pagination">
                            <li v-for="(link, key) in watchlist.links" :key="key" class="ui-page-item" :class="{ active: link.active, disabled: !link.url }">
                                <Link v-if="link.url" class="ui-page-link" :href="link.url" v-html="link.label" />
                                <span v-else class="ui-page-link" v-html="link.label" />
                            </li>
                        </ul>
                    </nav>
                </div>
            </template>

            <div v-else class="ui-card">
                <div class="ui-card-body">
                    <EmptyState
                        tone="info"
                        icon="fas fa-bookmark"
                        message="Your watchlist is empty. Start adding movies to track what you want to watch next."
                    />
                    <div class="u-text-center mt-3">
                        <Link :href="route('jav.vue.dashboard')" class="ui-btn ui-btn-primary">
                            <i class="fas fa-film mr-1"></i>Browse Movies
                        </Link>
                    </div>
                </div>
            </div>
    </PageShell>
</template>
