<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageShell from '@jav/Components/UI/PageShell.vue';
import SectionHeader from '@jav/Components/UI/SectionHeader.vue';
import EmptyState from '@jav/Components/UI/EmptyState.vue';
import DataTableShell from '@jav/Components/UI/DataTableShell.vue';

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
});

const page = usePage();
const permissions = computed(() => page.props.auth?.user?.permissions || []);

const canCreate = computed(() => permissions.value.includes('create-users'));
const canView = computed(() => permissions.value.includes('view-users'));
const canEdit = computed(() => permissions.value.includes('edit-users'));
const canDelete = computed(() => permissions.value.includes('delete-users'));

const updateFilter = (event) => {
    const form = event.target.closest('form');
    const params = new FormData(form);
    router.get(route('admin.users.index'), Object.fromEntries(params), { preserveState: true });
};

const removeUser = (user) => {
    if (!window.confirm('Are you sure you want to delete this user?')) {
        return;
    }

    router.delete(route('admin.users.destroy', user.id));
};
</script>

<template>
    <Head title="User Management" />

    <PageShell>
        <template #header>
            <SectionHeader title="User Management" subtitle="Manage accounts and access roles" />
        </template>

        <template #actions>
            <Link v-if="canCreate" :href="route('admin.users.create')" class="ui-btn ui-btn-primary">
                <i class="fas fa-plus mr-1" />Add User
            </Link>
        </template>

        <form class="ui-row ui-g-2 mb-3" @submit.prevent="updateFilter">
            <div class="ui-col-md-4">
                <input
                    name="search"
                    type="text"
                    class="ui-form-control"
                    placeholder="Search by name, email, username"
                    :value="filters.search || ''"
                >
            </div>
            <div class="ui-col-md-3">
                <select name="role" class="ui-form-select" :value="filters.role || ''" @change="updateFilter">
                    <option value="">All roles</option>
                    <option v-for="role in roles" :key="role.id" :value="role.slug">{{ role.name }}</option>
                </select>
            </div>
            <div class="ui-col-md-2">
                <select name="per_page" class="ui-form-select" :value="filters.per_page || 15" @change="updateFilter">
                    <option :value="15">15 per page</option>
                    <option :value="30">30 per page</option>
                    <option :value="50">50 per page</option>
                </select>
            </div>
            <div class="ui-col-md-3 u-flex gap-2">
                <button type="submit" class="ui-btn ui-btn-primary">Filter</button>
                <Link :href="route('admin.users.index')" class="ui-btn ui-btn-outline-secondary">Clear</Link>
            </div>
        </form>

        <EmptyState
            v-if="users.data.length === 0"
            tone="info"
            icon="fas fa-users"
            message="No users found. Adjust filters or create a new user."
        />

        <DataTableShell v-else>
            <div class="ui-table-responsive">
                <table class="ui-table ui-table-hover mb-0">
                    <thead class="ui-table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Roles</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id">
                            <td>{{ user.id }}</td>
                            <td>{{ user.name }}</td>
                            <td>{{ user.username }}</td>
                            <td>{{ user.email }}</td>
                            <td>
                                <span
                                    v-for="role in user.roles"
                                    :key="role.id"
                                    class="ui-badge u-bg-info u-text-dark mr-1"
                                >
                                    {{ role.name }}
                                </span>
                            </td>
                            <td>
                                <div class="ui-btn-group ui-btn-group-sm">
                                    <Link v-if="canView" :href="route('admin.users.show', user.id)" class="ui-btn ui-btn-info">
                                        <i class="fas fa-eye" />
                                    </Link>
                                    <Link v-if="canEdit" :href="route('admin.users.edit', user.id)" class="ui-btn ui-btn-warning">
                                        <i class="fas fa-edit" />
                                    </Link>
                                    <button v-if="canDelete" type="button" class="ui-btn ui-btn-danger" @click="removeUser(user)">
                                        <i class="fas fa-trash" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <template #footer>
                <nav v-if="users.links?.length > 3">
                    <ul class="ui-pagination mb-0 u-justify-center">
                        <li
                            v-for="link in users.links"
                            :key="link.label"
                            class="ui-page-item"
                            :class="{ active: link.active, disabled: !link.url }"
                        >
                            <Link class="ui-page-link" :href="link.url || '#'" v-html="link.label" preserve-scroll />
                        </li>
                    </ul>
                </nav>
            </template>
        </DataTableShell>
    </PageShell>
</template>
