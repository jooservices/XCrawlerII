<template>
    <div>
        <Navbar />
        <Sidebar />

        <div class="main-content">
            <div class="ui-container-fluid py-4">
                <!-- Flash Messages -->
                <div v-if="page.props.flash.success && !dismissed.success" class="ui-alert ui-alert-success ui-alert-dismissible fade show" role="ui-alert">
                    {{ page.props.flash.success }}
                    <button type="button" class="ui-btn-close" @click="dismissed.success = true"></button>
                </div>

                <div v-if="page.props.flash.error && !dismissed.error" class="ui-alert ui-alert-danger ui-alert-dismissible fade show" role="ui-alert">
                    {{ page.props.flash.error }}
                    <button type="button" class="ui-btn-close" @click="dismissed.error = true"></button>
                </div>

                <div v-if="page.props.flash.message && !dismissed.message" class="ui-alert ui-alert-info ui-alert-dismissible fade show" role="ui-alert">
                    {{ page.props.flash.message }}
                    <button type="button" class="ui-btn-close" @click="dismissed.message = true"></button>
                </div>

                <!-- Page Content -->
                <slot />
            </div>
        </div>

        <Footer />
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Navbar from './Navbar.vue';
import Sidebar from './Sidebar.vue';
import Footer from './Footer.vue';

const page = usePage();
const dismissed = reactive({
    success: false,
    error: false,
    message: false,
});
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
