<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PageShell from '@jav/Components/UI/PageShell.vue';
import SectionHeader from '@jav/Components/UI/SectionHeader.vue';

const props = defineProps({
    item: Object,
});
</script>

<template>
    <Head :title="`JAV #${item.id}`" />

    <PageShell>
        <template #header>
            <SectionHeader :title="`JAV #${item.id}`" subtitle="Record details" />
        </template>

        <template #actions>
            <div>
                <Link :href="route('jav.vue.javs.edit', item.uuid || item.id)" class="ui-btn ui-btn-outline-secondary ui-btn-sm mr-2">Edit</Link>
                <Link :href="route('jav.vue.javs.index')" class="ui-btn ui-btn-outline-primary ui-btn-sm">Back</Link>
            </div>
        </template>

            <div class="ui-card">
                <div class="ui-card-body">
                    <p><strong>ID:</strong> {{ item.id }}</p>
                    <p><strong>Code:</strong> {{ item.code }}</p>
                    <p><strong>Title:</strong> {{ item.title }}</p>
                    <p><strong>Date:</strong> {{ item.date || '-' }}</p>
                    <p><strong>Source:</strong> {{ item.source || '-' }}</p>
                    <p><strong>URL:</strong> <a v-if="item.url" :href="item.url" target="_blank" rel="noopener noreferrer">{{ item.url }}</a><span v-else>-</span></p>
                </div>
                </div>
            </PageShell>
</template>
