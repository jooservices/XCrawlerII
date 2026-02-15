<script setup>
defineProps({
    message: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        default: 'fas fa-circle-info',
    },
    tone: {
        type: String,
        default: 'info',
    },
});
</script>

<template>
    <div class="ui-alert ui-empty-state" :class="`ui-alert-${tone}`">
        <i :class="icon" aria-hidden="true"></i>
        <span>{{ message }}</span>
    </div>
</template>
