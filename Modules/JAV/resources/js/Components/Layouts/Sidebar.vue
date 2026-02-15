<template>
    <div
        class="sidebar u-bg-dark"
        :class="{ 'sidebar-open': uiStore.sidebarOpen }"
    >
        <div class="sidebar-content">
            <nav class="ui-nav u-flex-col pt-3">
                <h6 class="sidebar-heading px-3 mt-4 mb-1 u-text-muted">
                    Browse
                </h6>
                <Link :href="route('jav.vue.dashboard')" class="ui-nav-link" :class="{ active: isRoute('jav.vue.dashboard') }">
                    <i class="fas fa-film mr-2"></i> Movies
                </Link>
                <Link :href="route('jav.vue.actors')" class="ui-nav-link" :class="{ active: isRoute('jav.vue.actors') }">
                    <i class="fas fa-users mr-2"></i> Actors
                </Link>
                <Link :href="route('jav.vue.tags')" class="ui-nav-link" :class="{ active: isRoute('jav.vue.tags') }">
                    <i class="fas fa-tags mr-2"></i> Tags
                </Link>

                <template v-if="user">
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 u-text-muted">
                        Personal
                    </h6>
                    <Link :href="route('jav.vue.recommendations')" class="ui-nav-link" :class="{ active: isRoute('jav.vue.recommendations') }">
                        <i class="fas fa-star mr-2"></i> Recommendations
                    </Link>
                    <Link :href="route('jav.vue.history')" class="ui-nav-link" :class="{ active: isRoute('jav.vue.history') }">
                        <i class="fas fa-history mr-2"></i> History
                    </Link>
                    <Link :href="route('jav.vue.favorites')" class="ui-nav-link" :class="{ active: isRoute('jav.vue.favorites') }">
                        <i class="fas fa-heart mr-2"></i> Favorites
                    </Link>
                    <Link :href="route('jav.vue.watchlist')" class="ui-nav-link" :class="{ active: isRoute('jav.vue.watchlist') }">
                        <i class="fas fa-bookmark mr-2"></i> Watchlist
                    </Link>
                    <Link :href="route('jav.vue.ratings')" class="ui-nav-link" :class="{ active: isRoute('jav.vue.ratings') }">
                        <i class="fas fa-star-half-alt mr-2"></i> Ratings
                    </Link>
                </template>

                <template v-if="isAdmin">
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 u-text-muted">
                        Admin
                    </h6>
                    <Link :href="route('admin.users.index')" class="ui-nav-link" :class="{ active: isRoute('admin.users.*') }">
                        <i class="fas fa-users-cog mr-2"></i> Users
                    </Link>
                    <Link :href="route('admin.roles.index')" class="ui-nav-link" :class="{ active: isRoute('admin.roles.*') }">
                        <i class="fas fa-shield-alt mr-2"></i> Roles
                    </Link>
                </template>
            </nav>
        </div>
    </div>

    <!-- Overlay for mobile -->
    <div
        v-if="uiStore.sidebarOpen"
        class="sidebar-overlay"
        @click="uiStore.closeSidebar()"
    ></div>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useUIStore } from '../../Stores/ui';

const page = usePage();
const uiStore = useUIStore();

const user = computed(() => page.props.auth?.user);
const isAdmin = computed(() => user.value?.roles?.includes('admin'));
const currentUrl = computed(() => String(page.url || ''));
const currentRouteName = computed(() => {
    const currentUrlValue = currentUrl.value;
    const routeName = route().current();
    return routeName ? String(routeName) : currentUrlValue;
});

const routePatternMatches = (routePattern) => {
    const current = currentRouteName.value;
    const pattern = String(routePattern);

    if (!pattern.includes('*')) {
        return current === pattern;
    }

    const segments = pattern.split('*');
    const startsWithSegment = segments.shift() || '';
    const endsWithSegment = segments.pop() || '';

    if (startsWithSegment !== '' && !current.startsWith(startsWithSegment)) {
        return false;
    }

    if (endsWithSegment !== '' && !current.endsWith(endsWithSegment)) {
        return false;
    }

    let cursor = startsWithSegment.length;

    for (const segment of segments) {
        if (segment === '') {
            continue;
        }

        const foundIndex = current.indexOf(segment, cursor);
        if (foundIndex === -1) {
            return false;
        }

        cursor = foundIndex + segment.length;
    }

    return true;
};

const isRoute = (routeName) => {
    return routePatternMatches(routeName);
};
</script>

<style scoped>
.sidebar {
    position: fixed;
    top: 56px;
    left: 0;
    bottom: 0;
    width: 250px;
    overflow-y: auto;
    transition: transform 0.3s ease-in-out;
    z-index: 1040;
}

.sidebar-content {
    height: 100%;
}

.sidebar-heading {
    font-size: 0.75rem;
    text-transform: uppercase;
}

.ui-nav-link {
    color: rgba(255, 255, 255, 0.7);
    padding: 0.75rem 1rem;
}

.ui-nav-link:hover {
    color: #fff;
    background-color: rgba(0, 0, 0, 0.2);
}

.ui-nav-link.active {
    color: #fff;
    background-color: rgba(0, 0, 0, 0.35);
}

.sidebar-overlay {
    display: none;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar-open {
        transform: translateX(0);
    }

    .sidebar-overlay {
        display: block;
        position: fixed;
        top: 56px;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1030;
    }
}

@media (min-width: 769px) {
    .sidebar {
        transform: translateX(0);
    }
}
</style>
