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

// Helper to check active route
const isActive = (routePattern) => {
    return route().current(routePattern);
};
const user = computed(() => page.props.auth?.user || null);
const roles = computed(() => user.value?.roles || []);
const permissions = computed(() => user.value?.permissions || []);
const canViewUsers = computed(() => permissions.value.includes('view-users'));
const canViewRoles = computed(() => permissions.value.includes('view-roles'));
const isAdmin = computed(() => roles.value.includes('admin'));
const isAdminOrModerator = computed(() => roles.value.includes('admin') || roles.value.includes('moderator'));

// If collapsed (desktop) and not mobile, we might want to show icons only or condensed view.
// For now, strict 'sidebarExpanded' logic: 
// If collapsed: Show Icon Only (centered).
// If expanded: Show Icon + Text.
const showText = computed(() => props.mobile || uiStore.sidebarExpanded);
</script>

<template>
    <div id="sidebar" class="sidebar">
        <div class="nav flex-column">
            <Link :href="route('jav.vue.dashboard')" class="nav-link" :class="{ 'active': isActive('jav.vue.dashboard') }">
                <i class="fas fa-film me-2"></i> 
                <span v-if="showText">Movies</span>
            </Link>
            
            <Link :href="route('jav.vue.actors')" class="nav-link" :class="{ 'active': isActive('jav.vue.actors') }">
                <i class="fas fa-users me-2"></i> 
                <span v-if="showText">Actors</span>
            </Link>
            
            <Link :href="route('jav.vue.tags')" class="nav-link" :class="{ 'active': isActive('jav.vue.tags') }">
                <i class="fas fa-tags me-2"></i> 
                <span v-if="showText">Tags</span>
            </Link>

            <hr class="border-secondary my-2">

            <Link :href="route('jav.vue.recommendations')" class="nav-link" :class="{ 'active': isActive('jav.vue.recommendations') }">
                <i class="fas fa-star me-2"></i>
                <span v-if="showText">Recommendations</span>
            </Link>

            <Link :href="route('jav.vue.history')" class="nav-link" :class="{ 'active': isActive('jav.vue.history') }">
                <i class="fas fa-history me-2"></i> 
                <span v-if="showText">History</span>
            </Link>
            
            <Link :href="route('jav.vue.favorites')" class="nav-link" :class="{ 'active': isActive('jav.vue.favorites') }">
                <i class="fas fa-heart me-2"></i> 
                <span v-if="showText">Favorites</span>
            </Link>

            <Link :href="route('jav.vue.watchlist')" class="nav-link" :class="{ 'active': isActive('jav.vue.watchlist') }">
                <i class="fas fa-bookmark me-2"></i>
                <span v-if="showText">Watchlist</span>
            </Link>

            <Link :href="route('jav.vue.ratings')" class="nav-link" :class="{ 'active': isActive('jav.vue.ratings*') }">
                <i class="fas fa-star me-2"></i>
                <span v-if="showText">Ratings</span>
            </Link>

            <Link :href="route('jav.vue.preferences')" class="nav-link" :class="{ 'active': isActive('jav.vue.preferences') }">
                <i class="fas fa-sliders-h me-2"></i>
                <span v-if="showText">Preferences</span>
            </Link>

            <template v-if="isAdminOrModerator">
                <hr class="border-secondary my-2">
                <div class="px-3 py-2 text-uppercase" style="font-size: 0.75rem; color: #aaa;">
                    <i class="fas fa-cog me-2"></i> Administration
                </div>

                <a
                    v-if="canViewUsers"
                    :href="route('admin.users.index')"
                    class="nav-link"
                    :class="{ 'active': isActive('admin.users.*') }"
                >
                    <i class="fas fa-users-cog me-2"></i>
                    <span v-if="showText">Users</span>
                </a>

                <a
                    v-if="canViewRoles"
                    :href="route('admin.roles.index')"
                    class="nav-link"
                    :class="{ 'active': isActive('admin.roles.*') }"
                >
                    <i class="fas fa-shield-alt me-2"></i>
                    <span v-if="showText">Roles</span>
                </a>

                <template v-if="isAdmin">
                    <Link :href="route('jav.vue.admin.analytics')" class="nav-link" :class="{ 'active': isActive('jav.vue.admin.analytics') }">
                        <i class="fas fa-chart-pie me-2"></i>
                        <span v-if="showText">Analytics</span>
                    </Link>
                    <Link :href="route('jav.vue.admin.sync')" class="nav-link" :class="{ 'active': isActive('jav.vue.admin.sync') }">
                        <i class="fas fa-bolt me-2"></i>
                        <span v-if="showText">Quick Sync</span>
                    </Link>
                    <Link :href="route('jav.vue.admin.sync-progress')" class="nav-link" :class="{ 'active': isActive('jav.vue.admin.sync-progress*') }">
                        <i class="fas fa-chart-line me-2"></i>
                        <span v-if="showText">Sync Progress</span>
                    </Link>
                    <Link :href="route('jav.vue.admin.search-quality')" class="nav-link" :class="{ 'active': isActive('jav.vue.admin.search-quality') }">
                        <i class="fas fa-search me-2"></i>
                        <span v-if="showText">Search Quality</span>
                    </Link>
                    <Link :href="route('jav.vue.admin.provider-sync')" class="nav-link" :class="{ 'active': isActive('jav.vue.admin.provider-sync') }">
                        <i class="fas fa-sync-alt me-2"></i>
                        <span v-if="showText">Provider Sync</span>
                    </Link>
                </template>
            </template>
        </div>
    </div>
</template>
