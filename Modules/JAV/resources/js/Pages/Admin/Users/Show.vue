<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    user: Object,
});

const permissions = computed(() => {
    const flattened = (props.user.roles || []).flatMap((role) => role.permissions || []);
    const uniqueById = new Map(flattened.map((permission) => [permission.id, permission]));

    return Array.from(uniqueById.values());
});
</script>

<template>
    <Head :title="`User: ${user.name}`" />

    <div class="ui-container-fluid">
        <div class="u-flex u-justify-between u-items-center mb-3">
            <h2 class="mb-0">User Details</h2>
            <div class="u-flex gap-2">
                <Link :href="route('admin.users.edit', user.id)" class="ui-btn ui-btn-warning">Edit</Link>
                <Link :href="route('admin.users.index')" class="ui-btn ui-btn-outline-secondary">Back</Link>
            </div>
        </div>

        <div class="ui-card mb-3">
            <div class="ui-card-body">
                <div class="ui-row ui-g-3">
                    <div class="ui-col-md-3"><strong>ID:</strong> {{ user.id }}</div>
                    <div class="ui-col-md-3"><strong>Name:</strong> {{ user.name }}</div>
                    <div class="ui-col-md-3"><strong>Username:</strong> {{ user.username }}</div>
                    <div class="ui-col-md-3"><strong>Email:</strong> {{ user.email }}</div>
                </div>
            </div>
        </div>

        <div class="ui-card mb-3">
            <div class="ui-card-header">Roles</div>
            <div class="ui-card-body">
                <div v-if="user.roles?.length" class="u-flex u-flex-wrap gap-2">
                    <span v-for="role in user.roles" :key="role.id" class="ui-badge u-bg-primary">
                        {{ role.name }}
                    </span>
                </div>
                <p v-else class="u-text-muted mb-0">No roles assigned.</p>
            </div>
        </div>

        <div class="ui-card">
            <div class="ui-card-header">Role Permissions</div>
            <div class="ui-card-body">
                <div v-if="permissions.length" class="u-flex u-flex-wrap gap-2">
                    <span v-for="permission in permissions" :key="permission.id" class="ui-badge u-bg-secondary">
                        {{ permission.name }}
                    </span>
                </div>
                <p v-else class="u-text-muted mb-0">No permissions available.</p>
            </div>
        </div>
    </div>
</template>
