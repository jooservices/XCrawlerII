<script setup>
import { onBeforeUnmount, onMounted, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';
import { useUIStore } from '@jav/Stores/ui';
import Navbar from './Partials/Navbar.vue';
import Sidebar from './Partials/Sidebar.vue';
import Footer from './Partials/Footer.vue';

const uiStore = useUIStore();
const page = usePage();
const toast = useToast();

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const isMobileViewport = () => globalThis.matchMedia('(max-width: 991.98px)').matches;

const syncBodyClasses = () => {
    const userPreferences = page.props.auth?.user?.preferences ?? {};
    document.body.classList.add('app-dark', 'dark');

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
    globalThis.addEventListener('resize', resetViewportClasses);
    document.addEventListener('click', handleDocumentClick);
});

watch(
    () => uiStore.toast,
    (value) => {
        if (!value?.show || !value.message) {
            return;
        }

        toast.add({
            severity: value.type === 'success' ? 'success' : 'error',
            summary: value.type === 'success' ? 'Success' : 'Error',
            detail: value.message,
            life: 3000,
        });
    },
    { deep: true }
);

watch(
    () => [uiStore.sidebarExpanded, uiStore.mobileSidebarOpen, page.props.auth?.user?.preferences],
    () => {
        syncBodyClasses();
    },
    { deep: true }
);

onBeforeUnmount(() => {
    globalThis.removeEventListener('resize', resetViewportClasses);
    document.removeEventListener('click', handleDocumentClick);
});
</script>

<template>
    <div>
        <Toast position="top-right" />
        <Navbar />

        <div class="ui-container-fluid dashboard-layout">
            <div class="ui-row ui-g-0">
                <aside id="sidebarColumn" class="dashboard-sidebar-col u-hidden u-lg-block ui-col-lg-3 ui-col-xl-2">
                    <Sidebar />
                </aside>

                <main id="mainContentColumn" class="ui-col-12 ui-col-lg-9 ui-col-xl-10 main-content">
                    <slot />
                    <Footer />
                </main>
            </div>
        </div>
    </div>
</template>
