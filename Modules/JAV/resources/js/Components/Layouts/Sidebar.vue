<template>
    <div
        class="sidebar bg-dark"
        :class="{ 'sidebar-open': uiStore.sidebarOpen }"
    >
        <div class="sidebar-content">
            <nav class="nav flex-column pt-3">
                <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                    Browse
                </h6>
                <Link href="/jav/dashboard-vue" class="nav-link" :class="{ active: isRoute('jav.dashboard.vue') }">
                    <i class="fas fa-film me-2"></i> Movies
                </Link>
                <Link href="/jav/actors-vue" class="nav-link" :class="{ active: isRoute('jav.actors.vue') }">
                    <i class="fas fa-users me-2"></i> Actors
                </Link>
                <Link href="/jav/tags-vue" class="nav-link" :class="{ active: isRoute('jav.tags.vue') }">
                    <i class="fas fa-tags me-2"></i> Tags
                </Link>

                <template v-if="user">
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        Personal
                    </h6>
                    <Link href="/jav/recommendations-vue" class="nav-link" :class="{ active: isRoute('jav.recommendations.vue') }">
                        <i class="fas fa-star me-2"></i> Recommendations
                    </Link>
                    <Link href="/jav/history-vue" class="nav-link" :class="{ active: isRoute('jav.history.vue') }">
                        <i class="fas fa-history me-2"></i> History
                    </Link>
                    <Link href="/jav/favorites-vue" class="nav-link" :class="{ active: isRoute('jav.favorites.vue') }">
                        <i class="fas fa-heart me-2"></i> Favorites
                    </Link>
                    <Link href="/watchlist-vue" class="nav-link" :class="{ active: isRoute('watchlist.index.vue') }">
                        <i class="fas fa-bookmark me-2"></i> Watchlist
                    </Link>
                    <Link href="/ratings-vue" class="nav-link" :class="{ active: isRoute('ratings.index.vue') }">
                        <i class="fas fa-star-half-alt me-2"></i> Ratings
                    </Link>
                </template>

                <template v-if="isAdmin">
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        Admin
                    </h6>
                    <a href="#" class="nav-link">
                        <i class="fas fa-users-cog me-2"></i> Users
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-shield-alt me-2"></i> Roles
                    </a>
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

const isRoute = (routeName) => {
    return route().current(routeName);
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

.nav-link {
    color: rgba(255, 255, 255, 0.7);
    padding: 0.75rem 1rem;
}

.nav-link:hover {
    color: #fff;
    background-color: rgba(255, 255, 255, 0.1);
}

.nav-link.active {
    color: #fff;
    background-color: rgba(255, 255, 255, 0.2);
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
