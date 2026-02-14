<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';

const props = defineProps({
    item: Object,
});
</script>

<template>
    <Head :title="`JAV #${item.id}`" />

    <DashboardLayout>
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">JAV Detail</h2>
                <div>
                    <Link :href="route('jav.vue.javs.edit', item.uuid || item.id)" class="btn btn-outline-secondary btn-sm me-2">Edit</Link>
                    <Link :href="route('jav.vue.javs.index')" class="btn btn-outline-primary btn-sm">Back</Link>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <p><strong>ID:</strong> {{ item.id }}</p>
                    <p><strong>Code:</strong> {{ item.code }}</p>
                    <p><strong>Title:</strong> {{ item.title }}</p>
                    <p><strong>Date:</strong> {{ item.date || '-' }}</p>
                    <p><strong>Source:</strong> {{ item.source || '-' }}</p>
                    <p><strong>URL:</strong> <a v-if="item.url" :href="item.url" target="_blank" rel="noopener noreferrer">{{ item.url }}</a><span v-else>-</span></p>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
