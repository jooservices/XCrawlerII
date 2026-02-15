<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    tags: Object,
});

const visibleTags = ref([...(props.tags?.data || [])]);
const nextPageUrl = ref(props.tags?.next_page_url || null);
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

    router.get(route('jav.vue.tags'), params, {
        preserveState: true,
        preserveScroll: true,
        only: ['tags'],
        onSuccess: (visit) => {
            const incoming = visit?.props?.tags;
            if (incoming?.data) {
                visibleTags.value = [...visibleTags.value, ...incoming.data];
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
    () => props.tags,
    (incoming) => {
        if (!incoming) {
            visibleTags.value = [];
            nextPageUrl.value = null;
            return;
        }

        if (Number(incoming.current_page || 1) <= 1) {
            visibleTags.value = [...(incoming.data || [])];
        }
        nextPageUrl.value = incoming.next_page_url || null;
    },
    { deep: true }
);
</script>

<template>
    <Head title="Tags" />

    
        <div class="ui-container-fluid">
            <div class="ui-row mb-4">
                <div class="ui-col-md-12">
                    <h2>Tags</h2>
                </div>
            </div>

            <div class="ui-row ui-row-cols-2 ui-row-cols-md-4 ui-row-cols-lg-6 ui-g-4">
                <div v-for="tag in visibleTags" :key="tag.id" class="ui-col">
                    <Link :href="route('jav.vue.dashboard', { tag: tag.name })" class="u-no-underline u-text-dark">
                        <div class="ui-card u-h-full u-shadow-sm hover-shadow">
                            <div class="ui-card-body u-text-center">
                                <i class="fas fa-tag fa-2x u-text-info mb-3"></i>
                                <h5 class="ui-card-title u-truncate" :title="tag.name">{{ tag.name }}</h5>
                                <span class="ui-badge u-bg-secondary">{{ tag.javs_count || 0 }} JAVs</span>
                            </div>
                        </div>
                    </Link>
                </div>
                <div v-if="visibleTags.length === 0" class="ui-col-12">
                    <div class="ui-alert ui-alert-warning u-text-center">
                        No tags found.
                    </div>
                </div>
            </div>

            <div ref="sentinelRef" id="sentinel"></div>
            <div v-if="loadingMore" id="loading-spinner" class="u-text-center my-4">
                <div class="ui-spinner u-text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    
</template>

<style scoped>
.hover-shadow {
    transition: box-shadow 0.2s ease-in-out;
}

.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
</style>
