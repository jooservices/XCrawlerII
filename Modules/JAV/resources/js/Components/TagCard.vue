<template>
    <div class="ui-card u-text-center u-h-full tag-card">
        <div class="ui-card-body">
            <div class="card-header-row">
                <i class="fas fa-tag fa-2x u-text-info"></i>
                <FeaturedQuickAction
                    inline
                    item-type="tag"
                    :item-id="tag.id"
                    :item-label="tag.name"
                />
            </div>
            <h6 class="ui-card-title mb-1">{{ tag.name }}</h6>
            <p v-if="tag.movie_count" class="ui-card-text u-text-muted small mb-0">
                {{ tag.movie_count }} movies
            </p>
            <Link
                :href="`/jav/tags-vue?filter=${tag.name}`"
                class="ui-btn ui-btn-sm ui-btn-outline-info mt-2"
            >
                View Movies
            </Link>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import FeaturedQuickAction from '@jav/Components/FeaturedQuickAction.vue';

defineProps({
    tag: {
        type: Object,
        required: true,
    },
});
</script>

<style scoped>
.tag-card {
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
}

.card-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.tag-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}
</style>
