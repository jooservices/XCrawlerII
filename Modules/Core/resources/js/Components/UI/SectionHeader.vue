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
        <div class="ui-section-header-left">
            <h2 class="ui-page-title">{{ title }}</h2>
            <p v-if="subtitle" class="ui-page-subtitle">{{ subtitle }}</p>
        </div>
        <div v-if="$slots.actions || $slots.right" class="ui-section-header-right u-flex gap-2">
            <slot name="actions"></slot>
            <slot name="right"></slot>
        </div>
    </div>
</template>
