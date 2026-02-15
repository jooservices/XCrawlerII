<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PageShell from '@jav/Components/UI/PageShell.vue';
import SectionHeader from '@jav/Components/UI/SectionHeader.vue';
import EmptyState from '@jav/Components/UI/EmptyState.vue';
import DataTableShell from '@jav/Components/UI/DataTableShell.vue';

defineProps({
    items: Object,
});
</script>

<template>
    <Head title="JAV Resource" />

    <PageShell>
        <template #header>
            <SectionHeader title="JAV Resource" subtitle="Browse raw JAV records" />
        </template>

        <template #actions>
                <Link :href="route('jav.vue.javs.create')" class="ui-btn ui-btn-primary ui-btn-sm">
                    <i class="fas fa-plus mr-1"></i>Create
                </Link>
        </template>

        <EmptyState
            v-if="(items.data || []).length === 0"
            tone="info"
            icon="fas fa-database"
            message="No records available yet."
        />

        <DataTableShell v-else>
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

            <template #footer>
                <ul class="ui-pagination mb-0">
                    <li v-for="(link, index) in items.links || []" :key="index" class="ui-page-item" :class="{ active: link.active, disabled: !link.url }">
                        <Link v-if="link.url" class="ui-page-link" :href="link.url" v-html="link.label" />
                        <span v-else class="ui-page-link" v-html="link.label" />
                    </li>
                </ul>
            </template>
        </DataTableShell>
    </PageShell>
</template>
