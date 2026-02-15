<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="ui-section-header">
        <div>
            <h2 class="ui-page-title">{{ title }}</h2>
            <p v-if="subtitle" class="ui-page-subtitle">{{ subtitle }}</p>
        </div>
        <div v-if="$slots.actions" class="u-flex gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
