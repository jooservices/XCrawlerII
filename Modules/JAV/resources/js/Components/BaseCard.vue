<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    cardClass: {
        type: [String, Array, Object],
        default: '',
    },
    bodyClass: {
        type: [String, Array, Object],
        default: '',
    },
    mode: {
        type: String,
        default: 'legacy',
    },
    cover: {
        type: Object,
        default: () => ({}),
    },
    cornerStart: {
        type: Array,
        default: () => [],
    },
    cornerEnd: {
        type: Array,
        default: () => [],
    },
    heading: {
        type: Object,
        default: () => ({}),
    },
    groupTop: {
        type: Array,
        default: () => [],
    },
    meta: {
        type: Array,
        default: () => [],
    },
    groupA: {
        type: Array,
        default: () => [],
    },
    groupB: {
        type: Array,
        default: () => [],
    },
    primaryAction: {
        type: Object,
        default: null,
    },
    tools: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({
            showDivider: true,
            text: '',
            lines: [],
        }),
    },
});

defineEmits(['click']);

const inStructuredMode = computed(() => props.mode === 'structured');
const hasPrimaryAction = computed(() => Boolean(props.primaryAction?.href && props.primaryAction?.label));
const hasTools = computed(() => Array.isArray(props.tools) && props.tools.length > 0);
const hasSummary = computed(() => {
    const text = String(props.summary?.text || '').trim();
    const lines = Array.isArray(props.summary?.lines)
        ? props.summary.lines.filter((line) => {
            return String(line?.label || '').trim() !== '' || String(line?.value || '').trim() !== '';
        })
        : [];

    return text !== '' || lines.length > 0;
});

const invoke = (handler, event, value = null) => {
    if (typeof handler !== 'function') {
        return;
    }

    if (value === null) {
        handler(event);
        return;
    }

    handler(value, event);
};

const toolButtonClass = (tool) => {
    if (tool?.className) {
        return tool.className;
    }

    return tool?.active ? 'ui-btn-danger' : 'ui-btn-outline-secondary';
};

const toolIconClass = (tool) => {
    if (tool?.iconClass) {
        return tool.iconClass;
    }

    return String(tool?.icon || '');
};

const toolRatingValue = (tool) => {
    const value = Number(tool?.value || 0);
    return Number.isFinite(value) ? value : 0;
};

const toolRatingMax = (tool) => {
    const value = Number(tool?.max || 5);
    return Number.isFinite(value) && value > 0 ? value : 5;
};

const itemKey = (item, index, prefix) => {
    const key = String(item?.key || '').trim();
    if (key !== '') {
        return key;
    }

    return `${prefix}-${index}`;
};

const itemClass = (item, fallback = '') => {
    return [fallback, item?.className || ''];
};
</script>

<template>
    <div class="ui-card" :class="cardClass" @click="!inStructuredMode ? $emit('click', $event) : undefined">
        <div class="ui-card-body" :class="[bodyClass, inStructuredMode ? 'base-card-body' : '']">
            <template v-if="inStructuredMode">
                <component :is="cover?.href ? Link : 'div'" :href="cover?.href" class="u-relative u-block">
                    <img
                        v-if="cover?.src"
                        :src="cover.src"
                        :alt="cover?.alt || ''"
                        :class="cover?.className || 'ui-card-img-top u-h-300 u-object-cover'"
                        @error="invoke(cover?.onError, $event)"
                    >

                    <div
                        v-if="cornerStart.length > 0"
                        class="u-absolute u-top-0 u-left-0 u-bg-dark u-bg-opacity-75 u-text-white px-2 py-1 m-2 u-rounded"
                    >
                        <small
                            v-for="(item, index) in cornerStart"
                            :key="itemKey(item, index, 'corner-start')"
                            class="base-card-tooltip-target base-card-tooltip-target-bottom base-card-corner-item"
                            :class="index > 0 ? 'ml-2' : ''"
                            :aria-label="item?.tooltip || item?.title || item?.text || ''"
                            :data-tooltip="item?.tooltip || item?.title || item?.text || ''"
                        >
                            <i v-if="item?.icon" :class="item.icon"></i>
                            <span v-if="item?.text" class="base-card-corner-text">{{ item.text }}</span>
                        </small>
                    </div>

                    <div
                        v-if="cornerEnd.length > 0"
                        class="u-absolute u-top-0 u-right-0 u-bg-dark u-bg-opacity-75 u-text-white px-2 py-1 m-2 u-rounded"
                    >
                        <small
                            v-for="(item, index) in cornerEnd"
                            :key="itemKey(item, index, 'corner-end')"
                            class="base-card-tooltip-target base-card-tooltip-target-bottom base-card-corner-item"
                            :class="index > 0 ? 'ml-2' : ''"
                            :aria-label="item?.tooltip || item?.title || item?.text || ''"
                            :data-tooltip="item?.tooltip || item?.title || item?.text || ''"
                        >
                            <i v-if="item?.icon" :class="item.icon"></i>
                            <span v-if="item?.text" class="base-card-corner-text">{{ item.text }}</span>
                        </small>
                    </div>
                </component>

                <div class="base-card-content">
                    <div class="base-card-heading">
                        <div class="base-card-code-row">
                            <component :is="heading?.codeHref ? Link : 'div'" :href="heading?.codeHref" class="u-no-underline">
                                <h5 class="ui-card-title u-text-primary mb-1 base-card-code">{{ heading?.code || '' }}</h5>
                            </component>
                            <small
                                v-if="heading?.date"
                                class="base-card-inline-date"
                                :title="heading?.dateTitle || heading?.date"
                            >
                                <i class="fas fa-calendar-alt"></i> {{ heading.date }}
                            </small>
                        </div>
                        <p v-if="heading?.title" class="ui-card-text base-card-title-line" :title="heading?.title">{{ heading.title }}</p>
                    </div>

                    <div v-if="meta.length > 0" class="base-card-badge-row base-card-meta-row">
                        <span
                            v-for="(item, index) in meta"
                            :key="itemKey(item, index, 'meta')"
                            class="ui-badge base-card-badge"
                            :class="itemClass(item)"
                        >
                            <i v-if="item?.icon" :class="item.icon"></i>
                            <span v-if="item?.text"> {{ item.text }}</span>
                        </span>
                    </div>

                    <div v-if="groupTop.length > 0" class="base-card-badge-row base-card-group-row">
                        <span
                            v-for="(item, index) in groupTop"
                            :key="itemKey(item, index, 'group-top')"
                            class="ui-badge base-card-badge"
                            :class="itemClass(item)"
                        >
                            <i v-if="item?.icon" :class="item.icon"></i>
                            <span v-if="item?.text"> {{ item.text }}</span>
                        </span>
                    </div>

                    <div v-if="groupA.length > 0" class="base-card-badge-row base-card-group-row">
                        <component
                            :is="item?.href ? Link : 'span'"
                            v-for="(item, index) in groupA"
                            :key="itemKey(item, index, 'group-a')"
                            :href="item?.href"
                            class="ui-badge base-card-badge"
                            :class="itemClass(item, item?.href ? 'u-no-underline u-z-2 u-relative' : '')"
                        >
                            <i v-if="item?.icon" :class="item.icon"></i>
                            <span v-if="item?.text"> {{ item.text }}</span>
                        </component>
                    </div>

                    <div v-if="groupB.length > 0" class="base-card-badge-row base-card-group-row base-card-group-b-row">
                        <component
                            :is="item?.href ? Link : 'span'"
                            v-for="(item, index) in groupB"
                            :key="itemKey(item, index, 'group-b')"
                            :href="item?.href"
                            class="ui-badge base-card-badge"
                            :class="itemClass(item, item?.href ? 'u-no-underline u-z-2 u-relative' : '')"
                        >
                            <i v-if="item?.icon" :class="item.icon"></i>
                            <span v-if="item?.text"> {{ item.text }}</span>
                        </component>
                    </div>

                    <div class="base-card-actions">
                        <div class="u-grid gap-2">
                            <a
                                v-if="hasPrimaryAction && primaryAction?.native"
                                :href="primaryAction.href"
                                class="ui-btn ui-btn-sm"
                                :class="primaryAction?.className || (primaryAction?.variant === 'primary' ? 'ui-btn-primary' : 'ui-btn-outline-secondary')"
                                :title="primaryAction?.title || primaryAction?.label"
                            >
                                <i v-if="primaryAction?.icon" :class="primaryAction.icon"></i>
                                <span> {{ primaryAction.label }}</span>
                            </a>

                            <Link
                                v-else-if="hasPrimaryAction"
                                :href="primaryAction.href"
                                class="ui-btn ui-btn-sm"
                                :class="primaryAction?.className || (primaryAction?.variant === 'primary' ? 'ui-btn-primary' : 'ui-btn-outline-secondary')"
                                :title="primaryAction?.title || primaryAction?.label"
                            >
                                <i v-if="primaryAction?.icon" :class="primaryAction.icon"></i>
                                <span> {{ primaryAction.label }}</span>
                            </Link>
                        </div>

                        <div v-if="hasTools" class="mt-2 u-flex gap-2">
                            <template v-for="(tool, index) in tools" :key="itemKey(tool, index, 'tool')">
                                <div
                                    v-if="tool?.kind === 'rating'"
                                    class="quick-rating-group u-flex u-items-center ml-auto"
                                    :title="tool?.title || ''"
                                >
                                    <button
                                        v-for="star in toolRatingMax(tool)"
                                        :key="`tool-${index}-star-${star}`"
                                        type="button"
                                        class="ui-btn ui-btn-link ui-btn-sm p-0 mx-1 quick-rate-btn u-z-2 u-relative base-card-tooltip-target"
                                        :class="toolRatingValue(tool) >= star ? 'u-text-warning' : 'u-text-secondary'"
                                        :disabled="Boolean(tool?.disabled)"
                                        :aria-label="`Set rating to ${star} star${star > 1 ? 's' : ''}`"
                                        :data-tooltip="`Set rating to ${star} star${star > 1 ? 's' : ''}`"
                                        @click.prevent="invoke(tool?.onRate, $event, star)"
                                    >
                                        <i class="fas fa-star"></i>
                                    </button>
                                </div>

                                <button
                                    v-else
                                    type="button"
                                    class="ui-btn ui-btn-sm u-z-2 u-relative base-card-tooltip-target"
                                    :class="toolButtonClass(tool)"
                                    :disabled="Boolean(tool?.disabled)"
                                    :aria-label="tool?.title || ''"
                                    :data-tooltip="tool?.title || ''"
                                    @click.prevent="invoke(tool?.onClick, $event)"
                                >
                                    <i :class="toolIconClass(tool)"></i>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div
                    v-if="hasSummary"
                    class="base-card-summary"
                    :class="summary?.showDivider === false ? 'base-card-summary-no-divider' : ''"
                >
                    <div v-if="Array.isArray(summary?.lines) && summary.lines.length > 0" class="base-card-summary-text">
                        <p
                            v-for="(line, index) in summary.lines"
                            :key="line?.key || `summary-line-${index}`"
                            class="base-card-summary-line"
                        >
                            <strong v-if="line?.label">{{ line.label }}</strong><span v-if="line?.label">:</span>
                            <span v-if="line?.value" class="base-card-summary-line-value"> {{ line.value }}</span>
                        </p>
                    </div>
                    <p v-else class="base-card-summary-text" :title="summary?.text">{{ summary?.text }}</p>
                </div>
            </template>

            <slot v-else />
        </div>
    </div>
</template>

<style scoped>
:deep(.ui-card-img-top),
:deep(.tag-cover-placeholder) {
    margin-bottom: 0.3rem;
}

.base-card-code {
    font-weight: 700;
}

.base-card-body {
    --base-card-gap: 0.65rem;
    display: flex;
    flex-direction: column;
    gap: var(--base-card-gap);
    min-height: 0;
}

.base-card-content {
    display: flex;
    flex-direction: column;
    gap: var(--base-card-gap);
    min-height: 0;
}

.base-card-code-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.5rem;
}

.base-card-inline-date {
    color: var(--text-2);
    font-size: 0.75rem;
    white-space: nowrap;
    margin-top: 0.1rem;
}

.base-card-title-line {
    margin-bottom: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.base-card-badge-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    align-items: flex-start;
}

.base-card-actions {
    margin: 0;
}

.base-card-summary {
    margin-top: 0;
    padding-top: 0.65rem;
    border-top: 1px solid var(--border);
}

.base-card-summary-no-divider {
    border-top: 0;
    padding-top: 0;
}

.base-card-summary-text {
    margin: 0;
    color: var(--text-2);
    font-size: 0.84rem;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.base-card-summary-line {
    margin: 0;
}

.base-card-summary-line + .base-card-summary-line {
    margin-top: 0.2rem;
}

.base-card-summary-line strong {
    font-weight: 700;
}

.base-card-summary-line-value {
    font-weight: 400;
}

.base-card-badge {
    border: 1px solid transparent;
}

.base-card-meta-row .base-card-badge,
.base-card-group-b-row .base-card-badge {
    font-weight: 400;
}

.base-card-tone-positive {
    background: #166534 !important;
    border-color: #22c55e !important;
    color: #f0fdf4 !important;
}

.base-card-tone-info {
    background: #075985 !important;
    border-color: #38bdf8 !important;
    color: #f0f9ff !important;
}

.base-card-tone-active {
    background: #b45309 !important;
    border-color: #f59e0b !important;
    color: #fffbeb !important;
}

.base-card-tone-muted {
    background: #334155 !important;
    border-color: #64748b !important;
    color: #f8fafc !important;
}

.base-card-tooltip-target {
    position: relative;
}

.base-card-corner-item {
    display: inline-flex;
    align-items: center;
    gap: 0.28rem;
}

.base-card-corner-text {
    line-height: 1;
}

@media (hover: hover) {
    .base-card-tooltip-target::after {
        content: attr(data-tooltip);
        position: absolute;
        left: 50%;
        bottom: calc(100% + 8px);
        transform: translateX(-50%) translateY(3px);
        background: rgba(15, 23, 42, 0.96);
        color: #f8fafc;
        border: 1px solid rgba(148, 163, 184, 0.45);
        border-radius: 6px;
        padding: 0.25rem 0.45rem;
        font-size: 0.72rem;
        line-height: 1.2;
        white-space: nowrap;
        z-index: 40;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.16s ease, transform 0.16s ease;
    }

    .base-card-tooltip-target::before {
        content: '';
        position: absolute;
        left: 50%;
        bottom: calc(100% + 3px);
        transform: translateX(-50%);
        border-width: 5px 5px 0 5px;
        border-style: solid;
        border-color: rgba(15, 23, 42, 0.96) transparent transparent transparent;
        z-index: 39;
        opacity: 0;
        transition: opacity 0.16s ease;
        pointer-events: none;
    }

    .base-card-tooltip-target.base-card-tooltip-target-bottom::after {
        top: calc(100% + 8px);
        bottom: auto;
        transform: translateX(-50%) translateY(-3px);
    }

    .base-card-tooltip-target.base-card-tooltip-target-bottom::before {
        top: calc(100% + 3px);
        bottom: auto;
        border-width: 0 5px 5px 5px;
        border-color: transparent transparent rgba(15, 23, 42, 0.96) transparent;
    }

    .base-card-tooltip-target:hover::after,
    .base-card-tooltip-target:hover::before,
    .base-card-tooltip-target:focus-visible::after,
    .base-card-tooltip-target:focus-visible::before {
        opacity: 1;
    }

    .base-card-tooltip-target:hover::after,
    .base-card-tooltip-target:focus-visible::after {
        transform: translateX(-50%) translateY(0);
    }

    .base-card-tooltip-target.base-card-tooltip-target-bottom:hover::after,
    .base-card-tooltip-target.base-card-tooltip-target-bottom:focus-visible::after {
        transform: translateX(-50%) translateY(0);
    }
}
</style>
