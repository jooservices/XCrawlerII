<script setup>
import { Link } from '@inertiajs/vue3';
import { useUIStore } from '@jav/Stores/ui';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    mobile: Boolean
});

const uiStore = useUIStore();
const page = usePage();
const currentUrl = computed(() => String(page.url || ''));

const isActive = (routePattern) => {
    // Make active state react immediately to Inertia URL changes.
    currentUrl.value;
    return route().current(routePattern);
};
const isActiveAny = (routePatterns = []) => routePatterns.some((pattern) => isActive(pattern));
// If collapsed (desktop) and not mobile, we might want to show icons only or condensed view.
// For now, strict 'sidebarExpanded' logic: 
// If collapsed: Show Icon Only (centered).
// If expanded: Show Icon + Text.
const showText = computed(() => props.mobile || uiStore.sidebarExpanded);
</script>

<template>
    <div id="sidebar" class="sidebar">
        <div class="ui-nav u-flex-col">
            <Link
                :href="route('jav.vue.dashboard')"
                class="ui-nav-link"
                :class="{ 'active': isActiveAny(['jav.vue.dashboard', 'jav.vue.movies.*']) }"
            >
                <i class="fas fa-film mr-2"></i> 
                <span v-if="showText">Movies</span>
            </Link>
            
            <Link :href="route('jav.vue.actors')" class="ui-nav-link" :class="{ 'active': isActive('jav.vue.actors*') }">
                <i class="fas fa-users mr-2"></i> 
                <span v-if="showText">Actors</span>
            </Link>
            
            <Link :href="route('jav.vue.tags')" class="ui-nav-link" :class="{ 'active': isActive('jav.vue.tags*') }">
                <i class="fas fa-tags mr-2"></i> 
                <span v-if="showText">Tags</span>
            </Link>

            <hr class="u-border-secondary my-2">

            <Link :href="route('jav.vue.recommendations')" class="ui-nav-link" :class="{ 'active': isActive('jav.vue.recommendations') }">
                <i class="fas fa-star mr-2"></i>
                <span v-if="showText">Recommendations</span>
            </Link>

            <Link :href="route('jav.vue.watchlist')" class="ui-nav-link" :class="{ 'active': isActive('jav.vue.watchlist') }">
                <i class="fas fa-bookmark mr-2"></i>
                <span v-if="showText">Watchlist</span>
            </Link>

            <Link :href="route('jav.vue.favorites')" class="ui-nav-link" :class="{ 'active': isActive('jav.vue.favorites') }">
                <i class="fas fa-heart mr-2"></i> 
                <span v-if="showText">Favorites</span>
            </Link>

            <Link :href="route('jav.vue.history')" class="ui-nav-link" :class="{ 'active': isActive('jav.vue.history') }">
                <i class="fas fa-history mr-2"></i> 
                <span v-if="showText">History</span>
            </Link>

            <Link :href="route('jav.vue.ratings')" class="ui-nav-link" :class="{ 'active': isActive('jav.vue.ratings*') }">
                <i class="fas fa-star mr-2"></i>
                <span v-if="showText">Ratings</span>
            </Link>
        </div>
    </div>
</template>
