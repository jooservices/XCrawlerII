import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useUIStore = defineStore('ui', () => {
    // Desktop sidebar state (default: expanded)
    const sidebarExpanded = ref(true);

    // Mobile sidebar state (default: closed/hidden)
    const mobileSidebarOpen = ref(false);

    const toggleSidebar = () => {
        sidebarExpanded.value = !sidebarExpanded.value;
    };

    const toggleMobileSidebar = () => {
        mobileSidebarOpen.value = !mobileSidebarOpen.value;
    };

    const closeMobileSidebar = () => {
        mobileSidebarOpen.value = false;
    };

    // Toast Notification State
    const toast = ref({
        show: false,
        message: '',
        type: 'success', // 'success' or 'error'
    });

    const showToast = (message, type = 'success') => {
        toast.value = {
            show: true,
            message,
            type,
        };

        setTimeout(() => {
            toast.value.show = false;
        }, 3000);
    };

    return {
        sidebarExpanded,
        mobileSidebarOpen,
        toggleSidebar,
        toggleMobileSidebar,
        closeMobileSidebar,
        toast,
        showToast,
    };
});
