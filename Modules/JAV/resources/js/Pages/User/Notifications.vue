<script setup>
import { Head, router } from '@inertiajs/vue3';
import { useUIStore } from '@jav/Stores/ui';
import axios from 'axios';

const props = defineProps({
    notifications: {
        type: Array,
        default: () => [],
    },
});

const uiStore = useUIStore();

const markAsRead = (notificationId) => {
    axios.post(route('jav.api.notifications.read', notificationId))
        .then(() => {
            uiStore.showToast('Notification marked as read', 'success');
            router.reload({ preserveScroll: true });
        })
        .catch(() => {
            uiStore.showToast('Failed to mark notification as read', 'error');
        });
};

const markAllAsRead = () => {
    axios.post(route('jav.api.notifications.read-all'))
        .then(() => {
            uiStore.showToast('All notifications marked as read', 'success');
            router.reload({ preserveScroll: true });
        })
        .catch(() => {
            uiStore.showToast('Failed to mark all notifications as read', 'error');
        });
};

const formatTimestamp = (value) => {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return date.toLocaleString();
};
</script>

<template>
    <Head title="Notifications" />

    
        <div class="ui-row mb-4">
            <div class="ui-col">
                <h2>Notifications</h2>
            </div>
            <div class="ui-col-auto">
                <button
                    v-if="notifications.length > 0"
                    class="ui-btn ui-btn-sm ui-btn-outline-primary"
                    type="button"
                    @click="markAllAsRead"
                >
                    <i class="fas fa-check-double mr-1"></i>
                    Mark All as Read
                </button>
            </div>
        </div>

        <div class="ui-row">
            <div class="ui-col-md-8 mx-auto">
                <div v-if="notifications.length > 0" class="ui-list-group">
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        class="ui-list-group-item ui-list-group-item-action"
                        :class="{ 'u-bg-light': !notification.read_at }"
                    >
                        <div class="u-flex u-w-full u-justify-between u-items-start">
                            <div class="u-flex-grow">
                                <h6 class="mb-1">
                                    <i class="fas fa-bell mr-2 u-text-primary"></i>
                                    {{ notification.title }}
                                </h6>
                                <p class="mb-1">{{ notification.message }}</p>
                                <div
                                    v-if="notification.jav"
                                    class="small u-text-muted mb-1"
                                >
                                    <a
                                        :href="route('jav.vue.movies.show', notification.jav.uuid || notification.jav.id)"
                                        class="u-no-underline"
                                    >
                                        {{ notification.jav.code }} {{ notification.jav.title }}
                                    </a>
                                </div>
                                <small class="u-text-muted">
                                    <i class="fas fa-clock mr-1"></i>
                                    {{ formatTimestamp(notification.created_at) }}
                                </small>
                            </div>
                            <div class="ml-3">
                                <button
                                    v-if="!notification.read_at"
                                    class="ui-btn ui-btn-sm ui-btn-outline-secondary"
                                    type="button"
                                    title="Mark as read"
                                    @click="markAsRead(notification.id)"
                                >
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="ui-alert ui-alert-info u-text-center py-5">
                    <i class="fas fa-inbox fa-3x mb-3 u-block u-text-muted"></i>
                    <h5>No New Notifications</h5>
                    <p>You're all caught up!</p>
                </div>
            </div>
        </div>
    
</template>
