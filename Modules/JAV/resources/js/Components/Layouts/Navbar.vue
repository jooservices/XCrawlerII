<template>
    <nav class="ui-navbar ui-navbar-expand-lg ui-navbar-dark u-bg-dark u-fixed-top">
        <div class="ui-container-fluid">
            <button class="ui-btn ui-btn-link u-text-white mr-3" @click="toggleSidebar">
                <i class="fas fa-bars"></i>
            </button>

            <Link href="/jav/dashboard-vue" class="ui-navbar-brand">
                JAV Collection
            </Link>

            <button class="ui-navbar-toggler" type="button" :aria-expanded="navbarOpen ? 'true' : 'false'" @click.stop="navbarOpen = !navbarOpen">
                <span class="ui-navbar-toggler-icon"></span>
            </button>

            <div class="collapse ui-navbar-collapse" :class="{ show: navbarOpen }" id="navbarNav">
                <form class="u-flex u-flex-grow mx-lg-4" @submit.prevent="handleSearch">
                    <input
                        v-model="searchQuery"
                        class="ui-form-control mr-2"
                        type="search"
                        placeholder="Search movies..."
                    />
                    <button class="ui-btn ui-btn-outline-light" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>

                <ul class="ui-navbar-nav ml-auto">
                    <li v-if="!user" class="ui-nav-item">
                        <Link href="/login-vue" class="ui-nav-link">Login</Link>
                    </li>
                    <li v-if="!user" class="ui-nav-item">
                        <Link href="/register-vue" class="ui-nav-link">Register</Link>
                    </li>
                    <li v-if="user" ref="userMenuRef" class="ui-nav-item ui-dropdown">
                        <a class="ui-nav-link ui-dropdown-toggle" href="#" :aria-expanded="userMenuOpen ? 'true' : 'false'" @click.prevent.stop="userMenuOpen = !userMenuOpen">
                            {{ user.name }}
                        </a>
                        <ul class="ui-dropdown-menu ui-dropdown-menu-end" :class="{ show: userMenuOpen }">
                            <li><a class="ui-dropdown-item" href="#" @click.prevent="logout">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useUIStore } from '../../Stores/ui';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const uiStore = useUIStore();

const searchQuery = ref('');
const navbarOpen = ref(false);
const userMenuOpen = ref(false);
const userMenuRef = ref(null);

const toggleSidebar = () => {
    uiStore.toggleSidebar();
};

const handleSearch = () => {
    // Placeholder - will implement in Phase 2
    console.log('Search:', searchQuery.value);
};

const logout = () => {
    // Placeholder - will implement in Phase 3
    console.log('Logout clicked');
};

const handleOutsideClick = (event) => {
    if (userMenuRef.value && !userMenuRef.value.contains(event.target)) {
        userMenuOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleOutsideClick);
});
</script>
