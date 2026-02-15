<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        default: '--',
    },
});
</script>

<template>
    <div class="ui-card ui-interactive-card u-h-full">
        <div class="ui-card-body">
            <p class="ui-stat-label">{{ label }}</p>
            <h5 class="ui-stat-value">{{ value }}</h5>
            <slot />
        </div>
    </div>
</template>
