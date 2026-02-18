<script setup>
import { computed, ref } from 'vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Rating from 'primevue/rating';
import ToggleSwitch from 'primevue/toggleswitch';

const props = defineProps({
    tagItem: {
        type: Object,
        required: true,
    },
});

const liked = ref(Boolean(props.tagItem?.isLiked));
const favorited = ref(Boolean(props.tagItem?.isFavorited));
const featured = ref(Boolean(props.tagItem?.isFeatured));
const showDetails = ref(false);
const userRating = ref(Number(props.tagItem?.userRating || 0));

const tagFacts = computed(() => {
    return [
        { key: 'Group', value: props.tagItem?.group || '-' },
        { key: 'Trend', value: props.tagItem?.trend || '-' },
        { key: 'Language', value: props.tagItem?.language || '-' },
        { key: 'Region', value: props.tagItem?.region || '-' },
    ];
});
</script>

<template>
    <Card class="prime-tag-card-full prime-tag-card-full--dark" role="region" aria-label="PrimeVue tag card">
        <template #header>
            <div class="prime-tag-card-full__cover-wrap">
                <img :src="tagItem.cover" :alt="tagItem.name" class="prime-tag-card-full__cover">
                <div class="prime-tag-card-full__overlay">
                    <div class="prime-tag-card-full__stats">
                        <span class="prime-tag-card-full__stat"><i class="fas fa-film"></i> {{ tagItem.movieCount }}</span>
                        <span class="prime-tag-card-full__stat"><i class="fas fa-users"></i> {{ tagItem.followers }}</span>
                    </div>
                </div>
            </div>
        </template>

        <template #title>
            <div class="prime-tag-card-full__title-row">
                <span class="prime-tag-card-full__code">{{ tagItem.code }}</span>
                <Tag :value="tagItem.group" severity="secondary" size="small" />
            </div>
            <div class="prime-tag-card-full__title-wrap">
                <h3 class="prime-tag-card-full__title">{{ tagItem.name }}</h3>
            </div>
        </template>

        <template #content>
            <div class="prime-tag-card-full__content">
                <p class="prime-tag-card-full__description">{{ tagItem.description }}</p>

                <div v-if="showDetails" class="prime-tag-card-full__details">
                    <div v-for="item in tagFacts" :key="item.key" class="prime-tag-card-full__detail-item">
                        <strong>{{ item.key }}:</strong>
                        <span>{{ item.value }}</span>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="prime-tag-card-full__footer">
                <div class="prime-tag-card-full__footer-row prime-tag-card-full__footer-row--single">
                    <Button class="prime-tag-card-full__primary-action" severity="primary" size="small" label="View Tag">
                        <template #icon>
                            <i class="fas fa-tag"></i>
                        </template>
                    </Button>
                </div>

                <div class="prime-tag-card-full__footer-row prime-tag-card-full__footer-row--single">
                    <Button size="small" :label="favorited ? 'Favorited' : 'Favorite'" :severity="favorited ? 'warning' : 'secondary'" outlined @click="favorited = !favorited">
                        <template #icon>
                            <i :class="favorited ? 'fas fa-star' : 'far fa-star'"></i>
                        </template>
                    </Button>
                    <Button size="small" :label="liked ? 'Liked' : 'Like'" :severity="liked ? 'danger' : 'secondary'" outlined @click="liked = !liked">
                        <template #icon>
                            <i :class="liked ? 'fas fa-heart' : 'far fa-heart'"></i>
                        </template>
                    </Button>
                </div>

                <div class="prime-tag-card-full__footer-row prime-tag-card-full__footer-row--between">
                    <div class="prime-tag-card-full__rating-wrap">
                        <span>Your rating</span>
                        <Rating v-model="userRating" :stars="5" size="small" />
                        <div class="prime-tag-card-full__average">Average: {{ tagItem.averageRating }}/5</div>
                    </div>

                    <div class="prime-tag-card-full__switch-wrap">
                        <label for="tag-featured-toggle" style="display: flex; align-items: center; gap: 0.4rem;">
                            <span>Featured</span>
                            <i :class="featured ? 'fas fa-star' : 'far fa-star'" style="color: #fbbf24; font-size: 1.1em;"></i>
                        </label>
                        <ToggleSwitch id="tag-featured-toggle" v-model="featured" size="small" />
                    </div>
                </div>

                <Button
                    :label="showDetails ? 'Hide extra details' : 'Show extra details'"
                    text
                    icon="pi pi-angle-down"
                    icon-pos="right"
                    @click="showDetails = !showDetails"
                />
            </div>
        </template>
    </Card>
</template>

<style scoped>
.prime-tag-card-full {
    overflow: hidden;
}

.prime-tag-card-full__cover-wrap {
    position: relative;
    height: 12rem;
    overflow: hidden;
}

.prime-tag-card-full__cover {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.prime-tag-card-full__overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    display: flex;
    justify-content: flex-end;
    align-items: flex-start;
    padding: 0.85rem;
}

.prime-tag-card-full__stats {
    display: flex;
    gap: 0.35rem;
}

.prime-tag-card-full__stat {
    display: inline-flex;
    align-items: center;
    gap: 0.25em;
    font-size: 0.95em;
}

.prime-tag-card-full__title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
}

.prime-tag-card-full__code {
    font-size: 0.8rem;
    opacity: 0.75;
}

.prime-tag-card-full__title-wrap {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.prime-tag-card-full__title {
    margin: 0;
    font-size: 1.05rem;
    line-height: 1.4;
}

.prime-tag-card-full__content {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.prime-tag-card-full__description {
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.5;
}

.prime-tag-card-full__details {
    border-top: 1px solid var(--p-content-border-color);
    padding-top: 0.8rem;
    display: grid;
    gap: 0.4rem;
}

.prime-tag-card-full__detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.88rem;
}

.prime-tag-card-full__footer {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
}

.prime-tag-card-full__footer-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.prime-tag-card-full__footer-row--between {
    justify-content: space-between;
}

.prime-tag-card-full__average {
    font-size: 0.84rem;
    opacity: 0.85;
}

.prime-tag-card-full__primary-action {
    width: 100%;
}

.prime-tag-card-full__rating-wrap,
.prime-tag-card-full__switch-wrap {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.84rem;
}

.prime-tag-card-full--dark {
    background: #23262f !important;
    color: #e5e7eb !important;
    border-color: #23262f !important;
}

.prime-tag-card-full--dark .p-card-content,
.prime-tag-card-full--dark .p-card-title,
.prime-tag-card-full--dark .p-card-subtitle {
    background: transparent !important;
    color: #e5e7eb !important;
}
</style>
