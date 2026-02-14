<script setup>
import { onBeforeUnmount, onMounted, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useUIStore } from '@jav/Stores/ui';
import Navbar from './Partials/Navbar.vue';
import Sidebar from './Partials/Sidebar.vue';
import Footer from './Partials/Footer.vue';

const uiStore = useUIStore();
const page = usePage();

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const toastClass = computed(() => {
    return uiStore.toast.type === 'success' ? 'bg-success text-white' : 'bg-danger text-white';
});

const isMobileViewport = () => window.matchMedia('(max-width: 991.98px)').matches;

const syncBodyClasses = () => {
    const userPreferences = page.props.auth?.user?.preferences ?? {};

    if (userPreferences?.compact_mode) {
        document.body.classList.add('compact-mode');
    } else {
        document.body.classList.remove('compact-mode');
    }

    if (uiStore.sidebarExpanded) {
        document.body.classList.remove('sidebar-collapsed');
    } else {
        document.body.classList.add('sidebar-collapsed');
    }

    if (uiStore.mobileSidebarOpen) {
        document.body.classList.add('sidebar-mobile-open');
    } else {
        document.body.classList.remove('sidebar-mobile-open');
    }
};

const resetViewportClasses = () => {
    if (isMobileViewport()) {
        uiStore.sidebarExpanded = true;
    } else {
        uiStore.mobileSidebarOpen = false;
    }
    syncBodyClasses();
};

const handleDocumentClick = (event) => {
    if (!isMobileViewport() || !uiStore.mobileSidebarOpen) {
        return;
    }

    const clickedInSidebar = event.target.closest('#sidebar');
    const clickedToggle = event.target.closest('#sidebarToggle');
    if (!clickedInSidebar && !clickedToggle) {
        uiStore.mobileSidebarOpen = false;
        syncBodyClasses();
    }
};

onMounted(() => {
    if (flashSuccess.value) {
        uiStore.showToast(flashSuccess.value, 'success');
    }
    if (flashError.value) {
        uiStore.showToast(flashError.value, 'error');
    }

    resetViewportClasses();
    window.addEventListener('resize', resetViewportClasses);
    document.addEventListener('click', handleDocumentClick);
});

watch(
    () => [uiStore.sidebarExpanded, uiStore.mobileSidebarOpen, page.props.auth?.user?.preferences],
    () => {
        syncBodyClasses();
    },
    { deep: true }
);

onBeforeUnmount(() => {
    window.removeEventListener('resize', resetViewportClasses);
    document.removeEventListener('click', handleDocumentClick);
});
</script>

<template>
    <div>
        <Navbar />

        <div class="container-fluid dashboard-layout">
            <div class="row g-0">
                <aside id="sidebarColumn" class="dashboard-sidebar-col d-none d-lg-block col-lg-3 col-xl-2">
                    <Sidebar />
                </aside>

                <main id="mainContentColumn" class="col-12 col-lg-9 col-xl-10 main-content">
                    <div
                        v-if="uiStore.toast.show"
                        class="toast-container position-fixed top-0 end-0 p-3"
                        style="z-index: 1060; margin-top: 60px;"
                    >
                        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="toast-header" :class="toastClass">
                                <strong class="me-auto">Notification</strong>
                                <button type="button" class="btn-close btn-close-white" @click="uiStore.toast.show = false"></button>
                            </div>
                            <div class="toast-body text-dark bg-white">
                                {{ uiStore.toast.message }}
                            </div>
                        </div>
                    </div>

                    <slot />
                    <Footer />
                </main>
            </div>
        </div>
    </div>
</template>
