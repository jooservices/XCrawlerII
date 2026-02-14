<template>
    <div>
        <Navbar />
        <Sidebar />

        <div class="main-content">
            <div class="container-fluid py-4">
                <!-- Flash Messages -->
                <div v-if="page.props.flash.success" class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ page.props.flash.success }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <div v-if="page.props.flash.error" class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ page.props.flash.error }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <div v-if="page.props.flash.message" class="alert alert-info alert-dismissible fade show" role="alert">
                    {{ page.props.flash.message }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <!-- Page Content -->
                <slot />
            </div>
        </div>

        <Footer />
    </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import Navbar from './Navbar.vue';
import Sidebar from './Sidebar.vue';
import Footer from './Footer.vue';

const page = usePage();
</script>

<style scoped>
.main-content {
    margin-top: 56px;
    margin-left: 0;
    min-height: calc(100vh - 56px);
    transition: margin-left 0.3s ease-in-out;
}

@media (min-width: 769px) {
    .main-content {
        margin-left: 250px;
    }
}
</style>
