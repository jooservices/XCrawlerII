<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    roles: Object,
    filters: Object,
});

const page = usePage();
const permissions = computed(() => page.props.auth?.user?.permissions || []);

const canCreate = computed(() => permissions.value.includes('create-roles'));
const canView = computed(() => permissions.value.includes('view-roles'));
const canEdit = computed(() => permissions.value.includes('edit-roles'));
const canDelete = computed(() => permissions.value.includes('delete-roles'));

const updateFilter = (event) => {
    const form = event.target.closest('form');
    const params = new FormData(form);
    router.get(route('admin.roles.index'), Object.fromEntries(params), { preserveState: true });
};

const removeRole = (role) => {
    if (!window.confirm('Are you sure you want to delete this role?')) {
        return;
    }

    router.delete(route('admin.roles.destroy', role.id));
};

const isCoreRole = (slug) => ['admin', 'moderator', 'user'].includes(slug);
</script>

<template>
    <Head title="Role Management" />

    <div class="ui-container-fluid">
        <div class="u-flex u-justify-between u-items-center mb-3">
            <h2 class="mb-0">Role Management</h2>
            <Link v-if="canCreate" :href="route('admin.roles.create')" class="ui-btn ui-btn-primary">
                <i class="fas fa-plus mr-1" />Add Role
            </Link>
        </div>

        <form class="ui-row ui-g-2 mb-3" @submit.prevent="updateFilter">
            <div class="ui-col-md-6">
                <input
                    name="search"
                    type="text"
                    class="ui-form-control"
                    placeholder="Search by name, slug, description"
                    :value="filters.search || ''"
                >
            </div>
            <div class="ui-col-md-2">
                <select name="per_page" class="ui-form-select" :value="filters.per_page || 15" @change="updateFilter">
                    <option :value="15">15 per page</option>
                    <option :value="30">30 per page</option>
                    <option :value="50">50 per page</option>
                </select>
            </div>
            <div class="ui-col-md-4 u-flex gap-2">
                <button type="submit" class="ui-btn ui-btn-primary">Search</button>
                <Link :href="route('admin.roles.index')" class="ui-btn ui-btn-outline-secondary">Clear</Link>
            </div>
        </form>

        <div class="ui-card">
            <div class="ui-table-responsive">
                <table class="ui-table ui-table-hover mb-0">
                    <thead class="ui-table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Description</th>
                            <th>Permissions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="role in roles.data" :key="role.id">
                            <td>{{ role.id }}</td>
                            <td>{{ role.name }}</td>
                            <td><code>{{ role.slug }}</code></td>
                            <td>{{ role.description || '-' }}</td>
                            <td>
                                <span class="ui-badge u-bg-secondary">{{ role.permissions?.length || 0 }} permissions</span>
                            </td>
                            <td>
                                <div class="ui-btn-group ui-btn-group-sm">
                                    <Link v-if="canView" :href="route('admin.roles.show', role.id)" class="ui-btn ui-btn-info">
                                        <i class="fas fa-eye" />
                                    </Link>
                                    <Link v-if="canEdit" :href="route('admin.roles.edit', role.id)" class="ui-btn ui-btn-warning">
                                        <i class="fas fa-edit" />
                                    </Link>
                                    <button
                                        v-if="canDelete && !isCoreRole(role.slug)"
                                        type="button"
                                        class="ui-btn ui-btn-danger"
                                        @click="removeRole(role)"
                                    >
                                        <i class="fas fa-trash" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="roles.data.length === 0">
                            <td colspan="6" class="u-text-center py-4">No roles found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="ui-card-footer">
                <nav v-if="roles.links?.length > 3">
                    <ul class="ui-pagination mb-0 u-justify-center">
                        <li
                            v-for="link in roles.links"
                            :key="link.label"
                            class="ui-page-item"
                            :class="{ active: link.active, disabled: !link.url }"
                        >
                            <Link class="ui-page-link" :href="link.url || '#'" v-html="link.label" preserve-scroll />
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</template>
