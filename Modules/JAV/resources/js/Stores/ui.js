import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useUIStore = defineStore('ui', () => {
    const sidebarOpen = ref(false);

    const toggleSidebar = () => {
        sidebarOpen.value = !sidebarOpen.value;
    };

    const closeSidebar = () => {
        sidebarOpen.value = false;
    };

    const openSidebar = () => {
        sidebarOpen.value = true;
    };

    return {
        sidebarOpen,
        toggleSidebar,
        closeSidebar,
        openSidebar,
    };
});
