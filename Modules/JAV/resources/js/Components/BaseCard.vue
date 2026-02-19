<script setup>
defineProps({
    cardClass: {
        type: [String, Array, Object],
        default: '',
    },
    bodyClass: {
        type: [String, Array, Object],
        default: '',
    },
});

defineEmits(['click']);
</script>

<template>
    <div class="ui-card" :class="cardClass" @click="$emit('click', $event)">
        <div class="ui-card-body" :class="bodyClass">
            <slot />
        </div>
    </div>
</template>
