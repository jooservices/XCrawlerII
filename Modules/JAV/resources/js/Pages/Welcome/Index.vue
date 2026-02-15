<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineOptions({
    layout: null,
});

defineProps({
    modules: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head>
        <title>Welcome</title>
    </Head>

    <div class="ui-container py-5 welcome-page">
        <div class="mb-4">
            <h1 class="mb-2">JOOservices</h1>
            <p class="welcome-subtitle mb-0">Welcome to our office platform.</p>
        </div>

        <div class="ui-card">
            <div class="ui-card-body">
                <h2 class="h5 mb-3">Products</h2>

                <div class="ui-row g-3">
                    <div
                        v-for="module in modules"
                        :key="module.name"
                        class="ui-col-12 ui-col-md-6 ui-col-lg-4"
                    >
                        <div class="product-item p-3 h-100 d-flex flex-column">
                            <strong class="mb-2">{{ module.name }}</strong>
                            <p class="product-description mb-3">{{ module.description }}</p>
                            <div class="mt-auto">
                                <Link
                                    v-if="module.enabled"
                                    :href="module.route"
                                    class="ui-btn ui-btn-primary"
                                >
                                    Open
                                </Link>
                                <span v-else class="badge bg-secondary">Unavailable</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.welcome-page {
    color: var(--text-1);
}

.welcome-subtitle,
.product-description {
    color: var(--text-2);
}

.product-item {
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    background: var(--surface-2);
}
</style>
