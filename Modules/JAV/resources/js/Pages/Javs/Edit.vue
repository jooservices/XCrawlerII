<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';

const props = defineProps({
    item: Object,
});
</script>

<template>
    <Head :title="`Edit JAV #${item.id}`" />

    <DashboardLayout>
        <div class="container-fluid py-4">
            <h2 class="mb-3">Edit JAV</h2>

            <div class="card">
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        Edit flow is not implemented in backend yet.
                    </div>

                    <p class="mb-1"><strong>ID:</strong> {{ item.id }}</p>
                    <p class="mb-3"><strong>Code:</strong> {{ item.code }}</p>

                    <Link :href="route('jav.vue.javs.show', item.uuid || item.id)" class="btn btn-outline-secondary me-2">View</Link>
                    <Link :href="route('jav.vue.javs.index')" class="btn btn-outline-primary">Back to list</Link>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
