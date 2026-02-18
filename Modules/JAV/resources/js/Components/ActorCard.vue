<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useUIStore } from '@jav/Stores/ui';
import BaseCard from '@jav/Components/BaseCard.vue';

const props = defineProps({
    actor: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const uiStore = useUIStore();
const roles = computed(() => page.props.auth?.user?.roles || []);
const isAdmin = computed(() => roles.value.includes('admin'));

const averageRating = computed(() => {
    const value = props.actor?.average_rating ?? props.actor?.ratings_avg_rating ?? props.actor?.avg_rating ?? props.actor?.averageRating;
    const numeric = Number(value);
    return Number.isFinite(numeric) ? Number(numeric.toFixed(1)) : 0;
});

const localUserRating = ref(Number(props.actor?.user_rating || 0));
const localIsFeatured = ref(Boolean(props.actor?.is_featured ?? props.actor?.isFeatured));

const defaultFeaturedGroup = 'recent';
const featuredLoaded = ref(false);
const featuredEntries = ref([]);

const loadFeaturedEntries = async () => {
    if (!isAdmin.value || featuredLoaded.value) {
        return;
    }

    try {
        const response = await axios.get(route('jav.api.admin.featured-items.lookup'), {
            params: {
                item_type: 'actor',
                item_id: props.actor.id,
            },
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        featuredEntries.value = response.data?.items || [];
        featuredLoaded.value = true;
        localIsFeatured.value = featuredEntries.value.length > 0;
    } catch (error) {
        uiStore.showToast(error.response?.data?.message || 'Failed to load featured status.', 'error');
    }
};

const toggleFeatured = async (value) => {
    if (!isAdmin.value) {
        return;
    }

    if (!featuredLoaded.value) {
        await loadFeaturedEntries();
    }

    const activeEntry = featuredEntries.value.find((entry) => entry.group === defaultFeaturedGroup) || featuredEntries.value[0];

    try {
        if (value) {
            const payload = {
                item_type: 'actor',
                item_id: Number(props.actor.id),
                group: defaultFeaturedGroup,
                rank: 0,
                is_active: true,
            };
            const response = await axios.post(route('jav.api.admin.featured-items.store'), payload, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            featuredEntries.value = [response.data, ...featuredEntries.value];
            localIsFeatured.value = true;
            uiStore.showToast('Added to featured group.', 'success');
            return;
        }

        if (activeEntry?.id) {
            await axios.delete(route('jav.api.admin.featured-items.destroy', activeEntry.id), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            featuredEntries.value = featuredEntries.value.filter((entry) => entry.id !== activeEntry.id);
            localIsFeatured.value = featuredEntries.value.length > 0;
            uiStore.showToast('Removed from featured group.', 'success');
        }
    } catch (error) {
        uiStore.showToast(error.response?.data?.message || 'Failed to update featured status.', 'error');
    }
};

const detailRoute = computed(() => {
    return route('jav.vue.actors.bio', props.actor.uuid || props.actor.id);
});

const cardData = computed(() => {
    return {
        code: props.actor?.age || '',
        title: props.actor?.name || '',
        releaseDate: '',
        size: null,
        averageRating: averageRating.value,
        userRating: localUserRating.value,
        views: props.actor?.views ?? props.actor?.view_count ?? 0,
        downloads: 0,
        runtime: '-',
        studio: '-',
        language: '-',
        isLiked: false,
        inWatchlist: false,
        isFeatured: localIsFeatured.value,
        cover: props.actor?.cover || '',
        description: '',
        actors: [],
        tags: [],
        reasons: [],
    };
});

const topRightItems = computed(() => {
    const likes = props.actor?.likes_count ?? props.actor?.favorites_count ?? props.actor?.like_count ?? 0;
    const views = props.actor?.views ?? props.actor?.view_count ?? 0;
    const movies = props.actor?.movie_count ?? props.actor?.movies_count ?? 0;

    return [
        { icon: 'fas fa-heart', value: likes, label: 'Likes' },
        { icon: 'fas fa-eye', value: views, label: 'Views' },
        { icon: 'fas fa-film', value: movies, label: 'Movies' },
    ];
});

const openDetail = () => {
    router.visit(detailRoute.value);
};
</script>

<template>
    <div class="ui-col" @mouseenter="loadFeaturedEntries">
        <BaseCard
            :card="cardData"
            :show-cover="true"
            :show-stats="true"
            :show-views="false"
            :show-downloads="false"
            :top-right-items="topRightItems"
            :show-code="false"
            :show-release-date="false"
            :show-description="false"
            :show-actors="false"
            :show-tags="false"
            :show-details="false"
            :show-details-toggle="false"
            :show-download="false"
            :show-like="false"
            :show-watchlist="false"
            :show-user-rating="true"
            :show-average-rating="true"
            :show-featured-toggle="isAdmin"
            :on-toggle-featured="toggleFeatured"
            :on-cover-click="openDetail"
        />
    </div>
</template>
