<script setup>
defineProps({
    title: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="ui-card">
        <div v-if="title || $slots.header" class="ui-card-header u-flex u-justify-between u-items-center">
            <slot name="header">
                <h5 class="ui-card-title mb-0">{{ title }}</h5>
            </slot>
        </div>

        <div class="ui-card-body">
            <slot />
        </div>
    </div>
</template>
