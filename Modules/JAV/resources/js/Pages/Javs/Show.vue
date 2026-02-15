<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    item: Object,
});
</script>

<template>
    <Head :title="`JAV #${item.id}`" />

    
        <div class="ui-container-fluid py-4">
            <div class="u-flex u-justify-between u-items-center mb-3">
                <h2 class="mb-0">JAV Detail</h2>
                <div>
                    <Link :href="route('jav.vue.javs.edit', item.uuid || item.id)" class="ui-btn ui-btn-outline-secondary ui-btn-sm mr-2">Edit</Link>
                    <Link :href="route('jav.vue.javs.index')" class="ui-btn ui-btn-outline-primary ui-btn-sm">Back</Link>
                </div>
            </div>

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
        </div>
    
</template>
