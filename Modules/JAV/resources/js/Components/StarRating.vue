<template>
    <div class="star-rating">
        <i
            v-for="star in 5"
            :key="star"
            class="fas fa-star"
            :class="star <= rating ? 'text-warning' : 'text-muted'"
        ></i>
        <span v-if="showValue" class="ms-2 text-muted">
            {{ rating.toFixed(1) }}
        </span>
    </div>
</template>

<script setup>
defineProps({
    rating: {
        type: Number,
        default: 0,
        validator: (value) => value >= 0 && value <= 5,
    },
    showValue: {
        type: Boolean,
        default: true,
    },
});
</script>

<style scoped>
.star-rating i {
    font-size: 1rem;
    margin-right: 2px;
}
</style>
