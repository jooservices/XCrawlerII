<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PageShell from '@jav/Components/UI/PageShell.vue';
import SectionHeader from '@jav/Components/UI/SectionHeader.vue';
</script>

<template>
    <Head title="Create JAV" />

    <PageShell>
        <template #header>
            <SectionHeader title="Create JAV" subtitle="Create flow placeholder" />
        </template>

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
            </PageShell>
</template>
