<script setup>
import { computed } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import { useUIStore } from '@jav/Stores/ui';
import axios from 'axios';

const uiStore = useUIStore();
const page = usePage();

const user = computed(() => page.props.auth?.user ?? null);
const notifications = computed(() => page.props.notifications?.items ?? []);
const unreadCount = computed(() => Number(page.props.notifications?.count ?? 0));

const currentPath = computed(() => String(page.url || ''));
const currentQuery = computed(() => {
    const queryPart = currentPath.value.split('?')[1] ?? '';
    return new URLSearchParams(queryPart);
});

const isActorsRoute = computed(() => currentPath.value.includes('/jav/actors'));
const isTagsRoute = computed(() => currentPath.value.includes('/jav/tags'));
const isDashboardRoute = computed(() => currentPath.value.includes('/jav/dashboard'));
const dashboardFilters = computed(() => page.props.filters || {});

const searchAction = computed(() => {
    if (isActorsRoute.value) {
        return route('jav.vue.actors');
    }
    if (isTagsRoute.value) {
        return route('jav.vue.tags');
    }
    return route('jav.vue.dashboard');
});

const placeholder = computed(() => {
    if (isActorsRoute.value) {
        return 'Search actors...';
    }
    if (isTagsRoute.value) {
        return 'Search tags...';
    }
    return 'Search movies...';
});

const getQueryValue = (key) => currentQuery.value.get(key) ?? '';

const toggleSidebar = () => {
    if (window.matchMedia('(max-width: 991.98px)').matches) {
        uiStore.mobileSidebarOpen = !uiStore.mobileSidebarOpen;
        return;
    }

    uiStore.sidebarExpanded = !uiStore.sidebarExpanded;
};

const markNotificationRead = async (notificationId) => {
    try {
        await axios.post(route('jav.api.notifications.read', notificationId));
        router.reload({ preserveScroll: true });
    } catch (error) {
        uiStore.showToast('Failed to mark notification as read', 'error');
    }
};

const markAllNotificationsRead = async () => {
    try {
        await axios.post(route('jav.api.notifications.read-all'));
        router.reload({ preserveScroll: true });
    } catch (error) {
        uiStore.showToast('Failed to mark all notifications as read', 'error');
    }
};
</script>

<template>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <button id="sidebarToggle" class="btn btn-dark me-2" type="button" @click="toggleSidebar">
                <i class="fas fa-bars"></i>
            </button>
            <Link class="navbar-brand" :href="route('jav.vue.dashboard')">JAV Dashboard</Link>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <form :action="searchAction" method="GET" class="d-flex me-auto" style="max-width: 400px;" id="searchForm">
                    <input class="form-control form-control-sm me-2" type="search" name="q" :placeholder="placeholder" aria-label="Search" :value="getQueryValue('q')">
                    <template v-if="isDashboardRoute">
                        <input v-if="dashboardFilters.actor" type="hidden" name="actor" :value="dashboardFilters.actor">
                        <input v-if="dashboardFilters.tag" type="hidden" name="tag" :value="dashboardFilters.tag">
                        <template v-for="(selectedTag, index) in (dashboardFilters.tags || [])" :key="`persist-tag-${index}`">
                            <input type="hidden" name="tags[]" :value="selectedTag">
                        </template>
                        <input v-if="dashboardFilters.tags_mode" type="hidden" name="tags_mode" :value="dashboardFilters.tags_mode">
                        <input v-if="dashboardFilters.age" type="hidden" name="age" :value="dashboardFilters.age">
                        <input v-if="dashboardFilters.age_min" type="hidden" name="age_min" :value="dashboardFilters.age_min">
                        <input v-if="dashboardFilters.age_max" type="hidden" name="age_max" :value="dashboardFilters.age_max">
                        <input v-if="dashboardFilters.bio_key" type="hidden" name="bio_key" :value="dashboardFilters.bio_key">
                        <input v-if="dashboardFilters.bio_value" type="hidden" name="bio_value" :value="dashboardFilters.bio_value">
                        <template v-for="(bioFilter, bioIndex) in (dashboardFilters.bio_filters || [])" :key="`persist-bio-${bioIndex}`">
                            <input
                                v-if="(bioFilter?.key || bioFilter?.value)"
                                type="hidden"
                                :name="`bio_filters[${bioIndex}][key]`"
                                :value="bioFilter?.key || ''"
                            >
                            <input
                                v-if="(bioFilter?.key || bioFilter?.value)"
                                type="hidden"
                                :name="`bio_filters[${bioIndex}][value]`"
                                :value="bioFilter?.value || ''"
                            >
                        </template>
                        <input v-if="page.props.sort" type="hidden" name="sort" :value="page.props.sort">
                        <input v-if="page.props.direction" type="hidden" name="direction" :value="page.props.direction">
                        <input v-if="page.props.preset" type="hidden" name="preset" :value="page.props.preset">
                        <input v-if="page.props.savedPresetIndex !== null && page.props.savedPresetIndex !== undefined" type="hidden" name="saved_preset" :value="page.props.savedPresetIndex">
                    </template>
                    <button class="btn btn-outline-light btn-sm" type="submit"><i class="fas fa-search"></i></button>
                </form>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <Link class="nav-link" :href="route('jav.vue.dashboard')">Home</Link>
                    </li>

                    <template v-if="!user">
                        <li class="nav-item">
                            <Link class="nav-link" :href="route('jav.vue.login')">Login</Link>
                        </li>
                        <li class="nav-item">
                            <Link class="nav-link" :href="route('jav.vue.register')">Register</Link>
                        </li>
                    </template>

                    <template v-else>
                        <li class="nav-item dropdown me-2">
                            <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                                <i class="fas fa-bell"></i>
                                <span v-if="unreadCount > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown" style="min-width: 340px;">
                                <li class="dropdown-header d-flex justify-content-between align-items-center">
                                    <span>Notifications</span>
                                    <button v-if="unreadCount > 0" type="button" class="btn btn-link btn-sm p-0" @click="markAllNotificationsRead">
                                        Mark all read
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li v-for="notification in notifications" :key="notification.id" class="px-3 py-2 border-bottom">
                                    <div class="fw-semibold">{{ notification.title }}</div>
                                    <div v-if="notification.jav" class="small text-muted mb-1">
                                        <Link :href="route('jav.vue.movies.show', notification.jav.uuid)" class="text-decoration-none">
                                            {{ notification.jav.code }} {{ notification.jav.title }}
                                        </Link>
                                    </div>
                                    <div v-if="(notification.payload?.matched_actors || []).length > 0" class="small">
                                        Actor: {{ (notification.payload?.matched_actors || []).join(', ') }}
                                    </div>
                                    <div v-if="(notification.payload?.matched_tags || []).length > 0" class="small">
                                        Tag: {{ (notification.payload?.matched_tags || []).join(', ') }}
                                    </div>
                                    <button type="button" class="btn btn-link btn-sm p-0 mt-1" @click="markNotificationRead(notification.id)">
                                        Mark as read
                                    </button>
                                </li>
                                <li v-if="notifications.length === 0" class="px-3 py-2 text-muted small">No unread notifications</li>
                            </ul>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ user.name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li>
                                    <Link :href="route('logout')" method="post" as="button" class="dropdown-item">Logout</Link>
                                </li>
                            </ul>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </nav>
</template>
