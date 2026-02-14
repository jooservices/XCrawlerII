<template>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <button class="btn btn-link text-white me-3" @click="toggleSidebar">
                <i class="fas fa-bars"></i>
            </button>

            <Link href="/jav/dashboard-vue" class="navbar-brand">
                JAV Collection
            </Link>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="d-flex flex-grow-1 mx-lg-4" @submit.prevent="handleSearch">
                    <input
                        v-model="searchQuery"
                        class="form-control me-2"
                        type="search"
                        placeholder="Search movies..."
                    />
                    <button class="btn btn-outline-light" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>

                <ul class="navbar-nav ms-auto">
                    <li v-if="!user" class="nav-item">
                        <Link href="/login-vue" class="nav-link">Login</Link>
                    </li>
                    <li v-if="!user" class="nav-item">
                        <Link href="/register-vue" class="nav-link">Register</Link>
                    </li>
                    <li v-if="user" class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            {{ user.name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" @click.prevent="logout">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useUIStore } from '../../Stores/ui';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const uiStore = useUIStore();

const searchQuery = ref('');

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
</script>
