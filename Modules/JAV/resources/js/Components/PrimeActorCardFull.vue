<script setup>
import { computed, ref } from 'vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Rating from 'primevue/rating';
import ToggleSwitch from 'primevue/toggleswitch';

const props = defineProps({
    actor: {
        type: Object,
        required: true,
    },
});

const liked = ref(Boolean(props.actor?.isLiked));
const favorited = ref(Boolean(props.actor?.isFavorited));
const featured = ref(Boolean(props.actor?.isFeatured));
const showDetails = ref(false);
const userRating = ref(Number(props.actor?.userRating || 0));

const profileFacts = computed(() => {
    return [
        { key: 'Agency', value: props.actor?.agency || '-' },
        { key: 'Debut', value: props.actor?.debutYear || '-' },
        { key: 'Nationality', value: props.actor?.nationality || '-' },
        { key: 'Height', value: props.actor?.height || '-' },
    ];
});
</script>

<template>
    <Card class="prime-actor-card-full prime-actor-card-full--dark" role="region" aria-label="PrimeVue actor card">
        <template #header>
            <div class="prime-actor-card-full__cover-wrap">
                <img :src="actor.cover" :alt="actor.name" class="prime-actor-card-full__cover">
                <div class="prime-actor-card-full__overlay">
                    <div class="prime-actor-card-full__stats">
                        <span class="prime-actor-card-full__stat"><i class="fas fa-film"></i> {{ actor.movieCount }}</span>
                        <span class="prime-actor-card-full__stat"><i class="fas fa-heart"></i> {{ actor.favorites }}</span>
                    </div>
                </div>
            </div>
        </template>

        <template #title>
            <div class="prime-actor-card-full__title-row">
                <span class="prime-actor-card-full__code">{{ actor.code }}</span>
                <Tag :value="`Age ${actor.age}`" severity="secondary" size="small" />
            </div>
            <div class="prime-actor-card-full__title-wrap">
                <h3 class="prime-actor-card-full__title">{{ actor.name }}</h3>
            </div>
        </template>

        <template #content>
            <div class="prime-actor-card-full__content">
                <p class="prime-actor-card-full__description">{{ actor.description }}</p>

                <div v-if="showDetails" class="prime-actor-card-full__details">
                    <div v-for="item in profileFacts" :key="item.key" class="prime-actor-card-full__detail-item">
                        <strong>{{ item.key }}:</strong>
                        <span>{{ item.value }}</span>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="prime-actor-card-full__footer">
                <div class="prime-actor-card-full__footer-row prime-actor-card-full__footer-row--single">
                    <Button class="prime-actor-card-full__primary-action" severity="primary" size="small" label="View Profile">
                        <template #icon>
                            <i class="fas fa-user"></i>
                        </template>
                    </Button>
                </div>

                <div class="prime-actor-card-full__footer-row prime-actor-card-full__footer-row--single">
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

                <div class="prime-actor-card-full__footer-row prime-actor-card-full__footer-row--between">
                    <div class="prime-actor-card-full__rating-wrap">
                        <span>Your rating</span>
                        <Rating v-model="userRating" :stars="5" size="small" />
                        <div class="prime-actor-card-full__average">Average: {{ actor.averageRating }}/5</div>
                    </div>

                    <div class="prime-actor-card-full__switch-wrap">
                        <label for="actor-featured-toggle" style="display: flex; align-items: center; gap: 0.4rem;">
                            <span>Featured</span>
                            <i :class="featured ? 'fas fa-star' : 'far fa-star'" style="color: #fbbf24; font-size: 1.1em;"></i>
                        </label>
                        <ToggleSwitch id="actor-featured-toggle" v-model="featured" size="small" />
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
.prime-actor-card-full {
    overflow: hidden;
}

.prime-actor-card-full__cover-wrap {
    position: relative;
    height: 19rem;
    overflow: hidden;
}

.prime-actor-card-full__cover {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.prime-actor-card-full__overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    display: flex;
    justify-content: flex-end;
    align-items: flex-start;
    padding: 0.85rem;
}

.prime-actor-card-full__stats {
    display: flex;
    gap: 0.35rem;
}

.prime-actor-card-full__stat {
    display: inline-flex;
    align-items: center;
    gap: 0.25em;
    font-size: 0.95em;
}

.prime-actor-card-full__title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
}

.prime-actor-card-full__code {
    font-size: 0.8rem;
    opacity: 0.75;
}

.prime-actor-card-full__title-wrap {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.prime-actor-card-full__title {
    margin: 0;
    font-size: 1.05rem;
    line-height: 1.4;
}

.prime-actor-card-full__content {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.prime-actor-card-full__description {
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.5;
}

.prime-actor-card-full__details {
    border-top: 1px solid var(--p-content-border-color);
    padding-top: 0.8rem;
    display: grid;
    gap: 0.4rem;
}

.prime-actor-card-full__detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.88rem;
}

.prime-actor-card-full__footer {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
}

.prime-actor-card-full__footer-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.prime-actor-card-full__footer-row--between {
    justify-content: space-between;
}

.prime-actor-card-full__average {
    font-size: 0.84rem;
    opacity: 0.85;
}

.prime-actor-card-full__primary-action {
    width: 100%;
}

.prime-actor-card-full__rating-wrap,
.prime-actor-card-full__switch-wrap {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.84rem;
}

.prime-actor-card-full--dark {
    background: #23262f !important;
    color: #e5e7eb !important;
    border-color: #23262f !important;
}

.prime-actor-card-full--dark .p-card-content,
.prime-actor-card-full--dark .p-card-title,
.prime-actor-card-full--dark .p-card-subtitle {
    background: transparent !important;
    color: #e5e7eb !important;
}
</style>
