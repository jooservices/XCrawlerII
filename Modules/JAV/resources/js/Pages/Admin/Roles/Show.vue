<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PageShell from '@core/Components/UI/PageShell.vue';
import SectionHeader from '@core/Components/UI/SectionHeader.vue';

defineProps({
    role: Object,
});
</script>

<template>
    <Head :title="`Role: ${role.name}`" />

    <PageShell>
        <template #header>
            <SectionHeader :title="`Role: ${role.name}`" subtitle="Inspect role permissions and assigned users" />
        </template>

        <template #actions>
            <div class="u-flex gap-2">
                <Link :href="route('admin.roles.edit', role.id)" class="ui-btn ui-btn-warning">Edit</Link>
                <Link :href="route('admin.roles.index')" class="ui-btn ui-btn-outline-secondary">Back</Link>
            </div>
        </template>

        <div class="ui-card mb-3">
            <div class="ui-card-body">
                <div class="ui-row ui-g-3">
                    <div class="ui-col-md-4"><strong>Name:</strong> {{ role.name }}</div>
                    <div class="ui-col-md-4"><strong>Slug:</strong> <code>{{ role.slug }}</code></div>
                    <div class="ui-col-md-4"><strong>Description:</strong> {{ role.description || '-' }}</div>
                </div>
            </div>
        </div>

        <div class="ui-card mb-3">
            <div class="ui-card-header">Permissions</div>
            <div class="ui-card-body">
                <div v-if="role.permissions?.length" class="u-flex u-flex-wrap gap-2">
                    <span v-for="permission in role.permissions" :key="permission.id" class="ui-badge u-bg-primary">
                        {{ permission.name }}
                    </span>
                </div>
                <p v-else class="u-text-muted mb-0">No permissions assigned.</p>
            </div>
        </div>

        <div class="ui-card">
            <div class="ui-card-header">Users With This Role</div>
            <div class="ui-card-body">
                <div v-if="role.users?.length" class="ui-table-responsive">
                    <table class="ui-table ui-table-sm mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in role.users" :key="user.id">
                                <td>{{ user.id }}</td>
                                <td>{{ user.name }}</td>
                                <td>{{ user.email }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="u-text-muted mb-0">No users assigned.</p>
            </div>
        </div>
    </PageShell>
</template>
