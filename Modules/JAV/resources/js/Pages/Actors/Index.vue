<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';

const props = defineProps({
    actors: Object,
});

const visibleActors = ref([...(props.actors?.data || [])]);
const nextPageUrl = ref(props.actors?.next_page_url || null);
const loadingMore = ref(false);
const sentinelRef = ref(null);
let observer = null;

const parseUrlParams = (url) => {
    try {
        const parsed = new URL(url, window.location.origin);
        return Object.fromEntries(parsed.searchParams.entries());
    } catch (error) {
        return {};
    }
};

const loadMore = () => {
    if (loadingMore.value || !nextPageUrl.value) {
        return;
    }

    loadingMore.value = true;
    const params = parseUrlParams(nextPageUrl.value);

    router.get(route('jav.vue.actors'), params, {
        preserveState: true,
        preserveScroll: true,
        only: ['actors'],
        onSuccess: (visit) => {
            const incoming = visit?.props?.actors;
            if (incoming?.data) {
                visibleActors.value = [...visibleActors.value, ...incoming.data];
                nextPageUrl.value = incoming.next_page_url || null;
            } else {
                nextPageUrl.value = null;
            }
        },
        onFinish: () => {
            loadingMore.value = false;
        },
    });
};

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                loadMore();
            }
        });
    }, { rootMargin: '200px' });

    if (sentinelRef.value) {
        observer.observe(sentinelRef.value);
    }
});

onBeforeUnmount(() => {
    if (observer) {
        observer.disconnect();
        observer = null;
    }
});

watch(
    () => props.actors,
    (incoming) => {
        if (!incoming) {
            visibleActors.value = [];
            nextPageUrl.value = null;
            return;
        }

        if (Number(incoming.current_page || 1) <= 1) {
            visibleActors.value = [...(incoming.data || [])];
        }
        nextPageUrl.value = incoming.next_page_url || null;
    },
    { deep: true }
);
</script>

<template>
    <Head title="Actors" />

    <DashboardLayout>
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-md-12">
                    <h2>Actors</h2>
                </div>
            </div>

            <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-4">
                <div v-for="actor in visibleActors" :key="actor.id" class="col">
                    <div class="card h-100 shadow-sm hover-shadow">
                        <div class="position-relative">
                            <img
                                :src="actor.cover"
                                class="card-img-top"
                                :alt="actor.name"
                                @error="(e) => { e.target.src = 'https://placehold.co/300x400?text=No+Image'; }"
                            >
                        </div>
                        <div class="card-body text-center">
                            <h5 class="card-title text-truncate" :title="actor.name">{{ actor.name }}</h5>
                            <span class="badge bg-secondary">{{ actor.javs_count || 0 }} JAVs</span>
                            <div class="mt-3 d-grid gap-2">
                                <Link :href="route('jav.vue.actors.bio', actor.uuid || actor.id)" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-id-card me-1"></i> Bio
                                </Link>
                                <Link :href="route('jav.vue.dashboard', { actor: actor.name })" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-film me-1"></i> JAVs
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="visibleActors.length === 0" class="col-12">
                    <div class="alert alert-warning text-center">
                        No actors found.
                    </div>
                </div>
            </div>

            <div ref="sentinelRef" id="sentinel"></div>
            <div v-if="loadingMore" id="loading-spinner" class="text-center my-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.hover-shadow {
    transition: box-shadow 0.2s ease-in-out;
}

.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
</style>
