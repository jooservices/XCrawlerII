<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    items: Object,
});
</script>

<template>
    <Head title="JAV Resource" />

    
        <div class="ui-container-fluid py-4">
            <div class="u-flex u-justify-between u-items-center mb-3">
                <h2 class="mb-0">JAV Resource</h2>
                <Link :href="route('jav.vue.javs.create')" class="ui-btn ui-btn-primary ui-btn-sm">
                    <i class="fas fa-plus mr-1"></i>Create
                </Link>
            </div>

            <div class="ui-card">
                <div class="ui-card-body">
                    <div class="ui-table-responsive">
                        <table class="ui-table ui-table-striped ui-table-hover mb-0">
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
                                    <td colspan="5" class="u-text-muted">No records.</td>
                                </tr>
                                <tr v-for="item in items.data || []" :key="item.id">
                                    <td>{{ item.id }}</td>
                                    <td>{{ item.code }}</td>
                                    <td>{{ item.title }}</td>
                                    <td>{{ item.date || '-' }}</td>
                                    <td>
                                        <Link :href="route('jav.vue.javs.show', item.uuid || item.id)" class="ui-btn ui-btn-sm ui-btn-outline-primary mr-2">View</Link>
                                        <Link :href="route('jav.vue.javs.edit', item.uuid || item.id)" class="ui-btn ui-btn-sm ui-btn-outline-secondary">Edit</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <ul class="ui-pagination mb-0">
                            <li v-for="(link, index) in items.links || []" :key="index" class="ui-page-item" :class="{ active: link.active, disabled: !link.url }">
                                <Link v-if="link.url" class="ui-page-link" :href="link.url" v-html="link.label" />
                                <span v-else class="ui-page-link" v-html="link.label" />
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    
</template>
