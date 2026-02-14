<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';
</script>

<template>
    <Head title="Create JAV" />

    <DashboardLayout>
        <div class="container-fluid py-4">
            <h2 class="mb-3">Create JAV</h2>

            <div class="card">
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        Create flow is not implemented in backend yet.
                    </div>

                    <Link :href="route('jav.vue.javs.index')" class="btn btn-outline-secondary">
                        Back to list
                    </Link>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
