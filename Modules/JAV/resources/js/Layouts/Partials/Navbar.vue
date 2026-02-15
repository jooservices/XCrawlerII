<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import { useUIStore } from '@jav/Stores/ui';
import axios from 'axios';

const uiStore = useUIStore();
const page = usePage();
const navbarOpen = ref(false);
const notificationsOpen = ref(false);
const userMenuOpen = ref(false);
const adminMenuOpen = ref(false);
const searchMenuOpen = ref(false);
const isSearching = ref(false);
const suggestions = ref([]);
const searchQuery = ref('');
const activeSuggestionIndex = ref(-1);
const adminMenuRef = ref(null);
const notificationsMenuRef = ref(null);
const userMenuRef = ref(null);
const searchRef = ref(null);
let searchTimerId = null;
let searchRequestId = 0;

const user = computed(() => page.props.auth?.user ?? null);
const roles = computed(() => user.value?.roles || []);
const permissions = computed(() => user.value?.permissions || []);
const canViewUsers = computed(() => permissions.value.includes('view-users'));
const canViewRoles = computed(() => permissions.value.includes('view-roles'));
const isAdmin = computed(() => roles.value.includes('admin'));
const isAdminOrModerator = computed(() => roles.value.includes('admin') || roles.value.includes('moderator'));
const notifications = computed(() => page.props.notifications?.items ?? []);
const unreadCount = computed(() => Number(page.props.notifications?.count ?? 0));
const hasSuggestions = computed(() => suggestions.value.length > 0);
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

const isActive = (routePattern) => {
    return routePatternMatches(routePattern);
};
const isActiveAny = (routePatterns = []) => routePatterns.some((pattern) => isActive(pattern));

const toggleSidebar = () => {
    if (globalThis.matchMedia('(max-width: 991.98px)').matches) {
        uiStore.mobileSidebarOpen = !uiStore.mobileSidebarOpen;
        return;
    }

    uiStore.sidebarExpanded = !uiStore.sidebarExpanded;
};

const markNotificationRead = async (notificationId) => {
    try {
        await axios.post(route('jav.api.notifications.read', notificationId));
        notificationsOpen.value = false;
        router.reload({ preserveScroll: true });
    } catch {
        uiStore.showToast('Failed to mark notification as read', 'error');
    }
};

const markAllNotificationsRead = async () => {
    try {
        await axios.post(route('jav.api.notifications.read-all'));
        notificationsOpen.value = false;
        router.reload({ preserveScroll: true });
    } catch {
        uiStore.showToast('Failed to mark all notifications as read', 'error');
    }
};

const toggleNavbar = () => {
    navbarOpen.value = !navbarOpen.value;
};

const hideSearchMenu = () => {
    searchMenuOpen.value = false;
    activeSuggestionIndex.value = -1;
};

const submitGlobalSearch = () => {
    const query = searchQuery.value.trim();
    hideSearchMenu();

    router.get(route('jav.vue.dashboard'), query === '' ? {} : { q: query }, {
        preserveScroll: true,
    });
};

const selectSuggestion = (suggestion) => {
    if (!suggestion?.href) {
        return;
    }

    searchQuery.value = suggestion.label || searchQuery.value;
    hideSearchMenu();
    router.visit(suggestion.href, { preserveScroll: true });
};

const fetchSuggestions = async () => {
    const query = searchQuery.value.trim();
    if (query.length < 2) {
        suggestions.value = [];
        hideSearchMenu();
        return;
    }

    const requestId = ++searchRequestId;
    isSearching.value = true;

    try {
        const response = await axios.get(route('jav.api.search.suggest'), {
            params: { q: query, limit: 8 },
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (requestId !== searchRequestId) {
            return;
        }

        suggestions.value = Array.isArray(response.data?.suggestions) ? response.data.suggestions : [];
        searchMenuOpen.value = true;
        activeSuggestionIndex.value = suggestions.value.length > 0 ? 0 : -1;
    } catch {
        if (requestId === searchRequestId) {
            suggestions.value = [];
            hideSearchMenu();
        }
    } finally {
        if (requestId === searchRequestId) {
            isSearching.value = false;
        }
    }
};

const scheduleSuggestionsFetch = () => {
    if (searchTimerId !== null) {
        globalThis.clearTimeout(searchTimerId);
    }

    searchTimerId = globalThis.setTimeout(() => {
        fetchSuggestions();
    }, 250);
};

const onSearchInput = () => {
    scheduleSuggestionsFetch();
};

const onSearchFocus = () => {
    if (searchQuery.value.trim().length >= 2) {
        scheduleSuggestionsFetch();
    }
};

const onSearchKeydown = (event) => {
    if (!searchMenuOpen.value && ['ArrowDown', 'ArrowUp'].includes(event.key)) {
        event.preventDefault();
        if (hasSuggestions.value) {
            searchMenuOpen.value = true;
            activeSuggestionIndex.value = 0;
        }
        return;
    }

    if (event.key === 'ArrowDown' && hasSuggestions.value) {
        event.preventDefault();
        activeSuggestionIndex.value = (activeSuggestionIndex.value + 1) % suggestions.value.length;
        return;
    }

    if (event.key === 'ArrowUp' && hasSuggestions.value) {
        event.preventDefault();
        activeSuggestionIndex.value = (activeSuggestionIndex.value - 1 + suggestions.value.length) % suggestions.value.length;
        return;
    }

    if (event.key === 'Enter') {
        event.preventDefault();
        if (searchMenuOpen.value && activeSuggestionIndex.value >= 0 && suggestions.value[activeSuggestionIndex.value]) {
            selectSuggestion(suggestions.value[activeSuggestionIndex.value]);
            return;
        }
        submitGlobalSearch();
        return;
    }

    if (event.key === 'Escape') {
        hideSearchMenu();
    }
};

const toggleNotifications = () => {
    notificationsOpen.value = !notificationsOpen.value;
    adminMenuOpen.value = false;
    userMenuOpen.value = false;
};

const toggleUserMenu = () => {
    userMenuOpen.value = !userMenuOpen.value;
    adminMenuOpen.value = false;
    notificationsOpen.value = false;
};

const toggleAdminMenu = () => {
    adminMenuOpen.value = !adminMenuOpen.value;
    notificationsOpen.value = false;
    userMenuOpen.value = false;
};

const handleOutsideClick = (event) => {
    if (adminMenuRef.value && !adminMenuRef.value.contains(event.target)) {
        adminMenuOpen.value = false;
    }

    if (notificationsMenuRef.value && !notificationsMenuRef.value.contains(event.target)) {
        notificationsOpen.value = false;
    }

    if (userMenuRef.value && !userMenuRef.value.contains(event.target)) {
        userMenuOpen.value = false;
    }

    if (searchRef.value && !searchRef.value.contains(event.target)) {
        hideSearchMenu();
    }
};

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);
});

onBeforeUnmount(() => {
    if (searchTimerId !== null) {
        globalThis.clearTimeout(searchTimerId);
        searchTimerId = null;
    }

    document.removeEventListener('click', handleOutsideClick);
});
</script>

<template>
    <nav class="ui-navbar ui-navbar-expand-lg ui-navbar-dark u-bg-dark u-fixed-top">
        <div class="ui-container-fluid">
            <button id="sidebarToggle" class="ui-btn ui-btn-dark mr-2" type="button" @click="toggleSidebar">
                <i class="fas fa-bars"></i>
            </button>
            <Link class="ui-navbar-brand" :href="route('jav.vue.dashboard')">JAV Dashboard</Link>
            <button class="ui-navbar-toggler" type="button" :aria-expanded="navbarOpen ? 'true' : 'false'" @click.stop="toggleNavbar">
                <span class="ui-navbar-toggler-icon"></span>
            </button>

            <div class="collapse ui-navbar-collapse" :class="{ show: navbarOpen }" id="navbarNav">
                <ul class="ui-navbar-nav mr-auto">
                    <li class="ui-nav-item">
                        <Link
                            class="ui-nav-link"
                            :class="{ active: isActiveAny(['jav.vue.dashboard', 'jav.vue.movies.*']) }"
                            :href="route('jav.vue.dashboard')"
                        >
                            Home
                        </Link>
                    </li>

                    <li v-if="isAdminOrModerator" ref="adminMenuRef" class="ui-nav-item ui-dropdown">
                        <button
                            type="button"
                            class="ui-nav-link ui-dropdown-toggle"
                            :class="{ active: isActiveAny(['admin.users.*', 'admin.roles.*', 'jav.vue.admin.*', 'admin.job-*']) }"
                            id="adminDropdown"
                            :aria-expanded="adminMenuOpen ? 'true' : 'false'"
                            aria-haspopup="true"
                            @click.stop="toggleAdminMenu"
                        >
                            Admin
                        </button>
                        <ul class="ui-dropdown-menu" :class="{ show: adminMenuOpen }" aria-labelledby="adminDropdown">
                            <li v-if="canViewUsers">
                                <Link :href="route('admin.users.index')" class="ui-dropdown-item" :class="{ active: isActive('admin.users.*') }">Users</Link>
                            </li>
                            <li v-if="canViewRoles">
                                <Link :href="route('admin.roles.index')" class="ui-dropdown-item" :class="{ active: isActive('admin.roles.*') }">Roles</Link>
                            </li>
                            <template v-if="isAdmin">
                                <li><hr class="ui-dropdown-divider"></li>
                                <li>
                                    <Link :href="route('jav.vue.admin.analytics')" class="ui-dropdown-item" :class="{ active: isActive('jav.vue.admin.analytics') }">Analytics</Link>
                                </li>
                                <li>
                                    <Link :href="route('admin.job-telemetry')" class="ui-dropdown-item" :class="{ active: isActive('admin.job-*') }">Job Telemetry</Link>
                                </li>
                                <li>
                                    <Link :href="route('jav.vue.admin.search-quality')" class="ui-dropdown-item" :class="{ active: isActive('jav.vue.admin.search-quality') }">Search Quality</Link>
                                </li>
                                <li>
                                    <Link :href="route('jav.vue.admin.provider-sync')" class="ui-dropdown-item" :class="{ active: isActive('jav.vue.admin.provider-sync') }">Provider Sync</Link>
                                </li>
                            </template>
                        </ul>
                    </li>
                </ul>

                <form ref="searchRef" class="u-relative mx-lg-3 u-flex u-items-center" style="min-width: 260px; max-width: 420px; width: 100%;" @submit.prevent="submitGlobalSearch">
                    <input
                        v-model="searchQuery"
                        class="ui-form-control"
                        type="search"
                        placeholder="Search movies, actors, tags..."
                        aria-label="Global search"
                        autocomplete="off"
                        @input="onSearchInput"
                        @focus="onSearchFocus"
                        @keydown="onSearchKeydown"
                    >
                    <button class="ui-btn ui-btn-outline-light ml-2" type="submit" title="Search" aria-label="Search">
                        <i class="fas fa-search"></i>
                    </button>

                    <ul
                        v-if="searchMenuOpen"
                        class="ui-dropdown-menu show w-100 mt-1"
                        style="display: block; max-height: 320px; overflow: auto;"
                    >
                        <li v-if="isSearching" class="ui-dropdown-item u-text-muted small">Searching...</li>
                        <li
                            v-for="(item, index) in suggestions"
                            v-else-if="hasSuggestions"
                            :key="`${item.type}-${item.label}-${index}`"
                        >
                            <button
                                type="button"
                                class="ui-dropdown-item u-flex u-justify-between u-items-center"
                                :class="{ active: activeSuggestionIndex === index }"
                                @mousedown.prevent="selectSuggestion(item)"
                            >
                                <span class="u-truncate">{{ item.label }}</span>
                                <small class="u-text-muted ml-2 u-uppercase">{{ item.type }}</small>
                            </button>
                        </li>
                        <li v-else class="ui-dropdown-item u-text-muted small">No suggestions</li>
                    </ul>
                </form>

                <ul class="ui-navbar-nav ml-auto">
                    <template v-if="!user">
                        <li class="ui-nav-item">
                            <Link class="ui-nav-link" :href="route('jav.vue.login')">Login</Link>
                        </li>
                        <li class="ui-nav-item">
                            <Link class="ui-nav-link" :href="route('jav.vue.register')">Register</Link>
                        </li>
                    </template>

                    <template v-else>
                        <li ref="notificationsMenuRef" class="ui-nav-item ui-dropdown mr-2">
                            <button type="button" class="ui-nav-link u-relative" id="notificationsDropdown" :aria-expanded="notificationsOpen ? 'true' : 'false'" aria-haspopup="true" title="Notifications" @click.stop="toggleNotifications">
                                <i class="fas fa-bell"></i>
                                <span v-if="unreadCount > 0" class="u-absolute u-top-0 u-left-100 u-translate-middle ui-badge u-rounded-pill u-bg-danger">
                                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                                </span>
                            </button>
                            <ul class="ui-dropdown-menu ui-dropdown-menu-end" :class="{ show: notificationsOpen }" aria-labelledby="notificationsDropdown" style="min-width: 340px;">
                                <li class="ui-dropdown-header u-flex u-justify-between u-items-center">
                                    <span>Notifications</span>
                                    <button v-if="unreadCount > 0" type="button" class="ui-btn ui-btn-link ui-btn-sm p-0" @click="markAllNotificationsRead">
                                        Mark all read
                                    </button>
                                </li>
                                <li><hr class="ui-dropdown-divider"></li>
                                <li v-for="notification in notifications" :key="notification.id" class="px-3 py-2 u-border-bottom">
                                    <div class="fw-semibold">{{ notification.title }}</div>
                                    <div v-if="notification.jav" class="small u-text-muted mb-1">
                                        <Link :href="route('jav.vue.movies.show', notification.jav.uuid)" class="u-no-underline">
                                            {{ notification.jav.code }} {{ notification.jav.title }}
                                        </Link>
                                    </div>
                                    <div v-if="(notification.payload?.matched_actors || []).length > 0" class="small">
                                        Actor: {{ (notification.payload?.matched_actors || []).join(', ') }}
                                    </div>
                                    <div v-if="(notification.payload?.matched_tags || []).length > 0" class="small">
                                        Tag: {{ (notification.payload?.matched_tags || []).join(', ') }}
                                    </div>
                                    <button type="button" class="ui-btn ui-btn-link ui-btn-sm p-0 mt-1" @click="markNotificationRead(notification.id)">
                                        Mark as read
                                    </button>
                                </li>
                                <li v-if="notifications.length === 0" class="px-3 py-2 u-text-muted small">No unread notifications</li>
                            </ul>
                        </li>

                        <li ref="userMenuRef" class="ui-nav-item ui-dropdown">
                            <button type="button" class="ui-nav-link ui-dropdown-toggle" id="navbarDropdown" :aria-expanded="userMenuOpen ? 'true' : 'false'" aria-haspopup="true" @click.stop="toggleUserMenu">
                                <img
                                    v-if="user?.avatar_url"
                                    :src="user.avatar_url"
                                    alt="avatar"
                                    width="28"
                                    height="28"
                                    class="rounded-circle mr-2"
                                >
                                <i v-else class="fas fa-user-circle mr-2"></i>
                                {{ user.name }}
                            </button>
                            <ul class="ui-dropdown-menu ui-dropdown-menu-end" :class="{ show: userMenuOpen }" aria-labelledby="navbarDropdown">
                                <li>
                                    <Link :href="route('jav.vue.preferences')" class="ui-dropdown-item">Preferences</Link>
                                </li>
                                <li><hr class="ui-dropdown-divider"></li>
                                <li>
                                    <Link :href="route('logout')" method="post" as="button" class="ui-dropdown-item">Logout</Link>
                                </li>
                            </ul>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </nav>
</template>

<style scoped>
.ui-navbar button.ui-nav-link {
    border: 0;
    background: transparent;
    appearance: none;
}

.ui-navbar button.ui-nav-link:focus-visible {
    outline: 2px solid rgba(255, 255, 255, 0.4);
    outline-offset: 2px;
}
</style>
