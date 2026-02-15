<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Create JAV" />

    
        <div class="ui-container-fluid py-4">
            <h2 class="mb-3">Create JAV</h2>

            <div class="ui-card">
                <div class="ui-card-body">
                    <div class="ui-alert ui-alert-info mb-3">
                        Create flow is not implemented in backend yet.
                    </div>

                    <Link :href="route('jav.vue.javs.index')" class="ui-btn ui-btn-outline-secondary">
                        Back to list
                    </Link>
                </div>
            </div>
        </div>
    
</template>
