<script setup>
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';
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

    <DashboardLayout>
        <div class="row mb-4">
            <div class="col">
                <h2>Notifications</h2>
            </div>
            <div class="col-auto">
                <button
                    v-if="notifications.length > 0"
                    class="btn btn-sm btn-outline-primary"
                    type="button"
                    @click="markAllAsRead"
                >
                    <i class="fas fa-check-double me-1"></i>
                    Mark All as Read
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div v-if="notifications.length > 0" class="list-group">
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        class="list-group-item list-group-item-action"
                        :class="{ 'bg-light': !notification.read_at }"
                    >
                        <div class="d-flex w-100 justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <i class="fas fa-bell me-2 text-primary"></i>
                                    {{ notification.title }}
                                </h6>
                                <p class="mb-1">{{ notification.message }}</p>
                                <div
                                    v-if="notification.jav"
                                    class="small text-muted mb-1"
                                >
                                    <a
                                        :href="route('jav.vue.movies.show', notification.jav.uuid || notification.jav.id)"
                                        class="text-decoration-none"
                                    >
                                        {{ notification.jav.code }} {{ notification.jav.title }}
                                    </a>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    {{ formatTimestamp(notification.created_at) }}
                                </small>
                            </div>
                            <div class="ms-3">
                                <button
                                    v-if="!notification.read_at"
                                    class="btn btn-sm btn-outline-secondary"
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

                <div v-else class="alert alert-info text-center py-5">
                    <i class="fas fa-inbox fa-3x mb-3 d-block text-muted"></i>
                    <h5>No New Notifications</h5>
                    <p>You're all caught up!</p>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
