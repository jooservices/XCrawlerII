<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PageShell from '@jav/Components/UI/PageShell.vue';
import SectionHeader from '@jav/Components/UI/SectionHeader.vue';

const props = defineProps({
    item: Object,
});
</script>

<template>
    <Head :title="`Edit JAV #${item.id}`" />

    <PageShell>
        <template #header>
            <SectionHeader :title="`Edit JAV #${item.id}`" subtitle="Edit flow placeholder" />
        </template>

            <div class="ui-card">
                <div class="ui-card-body">
                    <div class="ui-alert ui-alert-info mb-3">
                        Edit flow is not implemented in backend yet.
                    </div>

                    <p class="mb-1"><strong>ID:</strong> {{ item.id }}</p>
                    <p class="mb-3"><strong>Code:</strong> {{ item.code }}</p>

                    <Link :href="route('jav.vue.javs.show', item.uuid || item.id)" class="ui-btn ui-btn-outline-secondary mr-2">View</Link>
                    <Link :href="route('jav.vue.javs.index')" class="ui-btn ui-btn-outline-primary">Back to list</Link>
                </div>
                </div>
            </PageShell>
</template>
