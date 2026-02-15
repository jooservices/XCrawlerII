<script setup>
import { computed, useSlots } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    subtitle: {
        type: String,
        default: '',
    },
});

const slots = useSlots();
const hasHeader = computed(() => Boolean(props.title || props.subtitle || slots.header || slots.actions));
</script>

<template>
    <div class="ui-container-fluid">
        <div v-if="hasHeader" class="u-flex u-justify-between u-items-center mb-3">
            <div>
                <slot name="header">
                    <h2 v-if="title" class="mb-0">{{ title }}</h2>
                    <p v-if="subtitle" class="u-text-muted mb-0 mt-1">{{ subtitle }}</p>
                </slot>
            </div>

            <div v-if="$slots.actions" class="u-flex gap-2">
                <slot name="actions" />
            </div>
        </div>

        <slot />
    </div>
</template>