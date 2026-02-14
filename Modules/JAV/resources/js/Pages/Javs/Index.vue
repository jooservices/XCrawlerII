<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';

defineProps({
    items: Object,
});
</script>

<template>
    <Head title="JAV Resource" />

    <DashboardLayout>
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">JAV Resource</h2>
                <Link :href="route('jav.vue.javs.create')" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i>Create
                </Link>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Code</th>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="(items.data || []).length === 0">
                                    <td colspan="5" class="text-muted">No records.</td>
                                </tr>
                                <tr v-for="item in items.data || []" :key="item.id">
                                    <td>{{ item.id }}</td>
                                    <td>{{ item.code }}</td>
                                    <td>{{ item.title }}</td>
                                    <td>{{ item.date || '-' }}</td>
                                    <td>
                                        <Link :href="route('jav.vue.javs.show', item.uuid || item.id)" class="btn btn-sm btn-outline-primary me-2">View</Link>
                                        <Link :href="route('jav.vue.javs.edit', item.uuid || item.id)" class="btn btn-sm btn-outline-secondary">Edit</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <ul class="pagination mb-0">
                            <li v-for="(link, index) in items.links || []" :key="index" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                                <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label" />
                                <span v-else class="page-link" v-html="link.label" />
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
