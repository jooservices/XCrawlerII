<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useUIStore } from '@jav/Stores/ui';

const props = defineProps({
    itemType: {
        type: String,
        required: true,
    },
    itemId: {
        type: [Number, String],
        required: true,
    },
    itemLabel: {
        type: String,
        default: '',
    },
    inline: {
        type: Boolean,
        default: false,
    },
    quickToggle: {
        type: Boolean,
        default: true,
    },
    defaultGroup: {
        type: String,
        default: 'recent',
    },
});

const uiStore = useUIStore();
const page = usePage();
const roles = computed(() => page.props.auth?.user?.roles || []);
const isAdmin = computed(() => roles.value.includes('admin'));

const isOpen = ref(false);
const isLoading = ref(false);
const isSaving = ref(false);
const selectedGroup = ref(props.defaultGroup);
const featuredItems = ref([]);
const popoverRef = ref(null);
const hasLoaded = ref(false);

const groupOptions = ['recent', 'trending', 'top', 'staff_pick'];

const hasFeatured = computed(() => featuredItems.value.length > 0);
const activeEntry = computed(() => featuredItems.value.find(item => item.group === selectedGroup.value));

const openPopover = async () => {
    if (!isAdmin.value) {
        return;
    }

    isOpen.value = true;

    if (!hasLoaded.value) {
        await loadFeatured();
    }
};

const togglePopover = async () => {
    if (!isAdmin.value) {
        return;
    }

    isOpen.value = !isOpen.value;

    if (isOpen.value && !hasLoaded.value) {
        await loadFeatured();
    }
};

const handleOutsideClick = (event) => {
    if (!isOpen.value) {
        return;
    }

    if (popoverRef.value && !popoverRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleOutsideClick);
});

const loadFeatured = async () => {
    isLoading.value = true;

    try {
        const response = await axios.get(route('jav.api.admin.featured-items.lookup'), {
            params: {
                item_type: props.itemType,
                item_id: props.itemId,
            },
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        featuredItems.value = response.data?.items || [];
        hasLoaded.value = true;
    } catch (error) {
        uiStore.showToast(error.response?.data?.message || 'Failed to load featured status.', 'error');
    } finally {
        isLoading.value = false;
    }
};

const addToGroup = async (groupOverride = null) => {
    isSaving.value = true;
    const group = groupOverride || selectedGroup.value;

    try {
        const payload = {
            item_type: props.itemType,
            item_id: Number(props.itemId),
            group,
            rank: 0,
            is_active: true,
        };

        const response = await axios.post(route('jav.api.admin.featured-items.store'), payload, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        featuredItems.value = [response.data, ...featuredItems.value];
        uiStore.showToast('Added to featured group.', 'success');
    } catch (error) {
        uiStore.showToast(error.response?.data?.message || 'Failed to feature item.', 'error');
    } finally {
        isSaving.value = false;
    }
};

const removeFromGroup = async (entryId) => {
    if (!entryId) {
        return;
    }

    isSaving.value = true;

    try {
        await axios.delete(route('jav.api.admin.featured-items.destroy', entryId), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        featuredItems.value = featuredItems.value.filter(item => item.id !== entryId);
        uiStore.showToast('Removed from featured group.', 'success');
    } catch (error) {
        uiStore.showToast(error.response?.data?.message || 'Failed to remove featured item.', 'error');
    } finally {
        isSaving.value = false;
    }
};

const removeAllFromGroups = async () => {
    if (!featuredItems.value.length) {
        return;
    }

    isSaving.value = true;

    try {
        await Promise.all(
            featuredItems.value.map((entry) => {
                return axios.delete(route('jav.api.admin.featured-items.destroy', entry.id), {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
            })
        );

        featuredItems.value = [];
        uiStore.showToast('Removed from featured groups.', 'success');
    } catch (error) {
        uiStore.showToast(error.response?.data?.message || 'Failed to remove featured item.', 'error');
    } finally {
        isSaving.value = false;
    }
};

const handlePrimaryClick = async () => {
    if (!isAdmin.value || isLoading.value || isSaving.value) {
        return;
    }

    if (!props.quickToggle) {
        await togglePopover();
        return;
    }

    if (!hasLoaded.value) {
        await loadFeatured();
    }

    if (!featuredItems.value.length) {
        await addToGroup(props.defaultGroup);
        return;
    }

    if (featuredItems.value.length > 1) {
        await removeAllFromGroups();
        return;
    }

    await removeFromGroup(featuredItems.value[0]?.id);
};
</script>

<template>
    <div v-if="isAdmin" ref="popoverRef" class="featured-quick" :class="{ 'is-inline': inline }">
        <button
            type="button"
            class="featured-quick__btn"
            :class="{ 'is-active': hasFeatured }"
            @click.stop="handlePrimaryClick"
        >
            <i class="fas" :class="hasFeatured ? 'fa-star' : 'fa-star-half-alt'"></i>
        </button>

        <div v-if="isOpen" class="featured-quick__popover" @click.stop>
            <div class="featured-quick__title">Feature</div>
            <div v-if="itemLabel" class="featured-quick__label">{{ itemLabel }}</div>

            <div class="featured-quick__control">
                <label class="ui-form-label">Group</label>
                <select v-model="selectedGroup" class="ui-form-select ui-form-select-sm">
                    <option v-for="group in groupOptions" :key="group" :value="group">{{ group }}</option>
                </select>
            </div>

            <div class="featured-quick__actions">
                <button
                    type="button"
                    class="ui-btn ui-btn-sm ui-btn-primary"
                    :disabled="isLoading || isSaving"
                    @click="addToGroup()"
                >
                    Add
                </button>
                <button
                    v-if="activeEntry"
                    type="button"
                    class="ui-btn ui-btn-sm ui-btn-outline-danger"
                    :disabled="isLoading || isSaving"
                    @click="removeFromGroup(activeEntry.id)"
                >
                    Remove
                </button>
            </div>

            <div v-if="featuredItems.length" class="featured-quick__meta">
                <div class="featured-quick__meta-title">Featured in</div>
                <div class="featured-quick__tags">
                    <span v-for="item in featuredItems" :key="item.id" class="featured-quick__tag">{{ item.group }}</span>
                </div>
            </div>

            <div v-if="isLoading" class="featured-quick__loading">Loading...</div>
        </div>
    </div>
</template>

<style scoped>
.featured-quick {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 5;
}

.featured-quick.is-inline {
    position: relative;
    top: auto;
    left: auto;
    z-index: 1;
}

.featured-quick__btn {
    width: 32px;
    height: 32px;
    border-radius: 999px;
    border: 1px solid var(--border);
    background: var(--surface);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--text-2);
    transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}


.featured-quick__btn.is-active {
    color: var(--primary-strong);
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--primary-strong) 25%, transparent);
}

.featured-quick__btn:hover {
    background: var(--surface-raised);
    color: var(--text-1);
}

.featured-quick__popover {
    position: absolute;
    top: 38px;
    left: 0;
    min-width: 220px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    box-shadow: var(--card-hover-shadow);
    padding: 12px;
}

.featured-quick.is-inline .featured-quick__popover {
    left: auto;
    right: 0;
}

.featured-quick__title {
    font-weight: 600;
    margin-bottom: 4px;
}

.featured-quick__label {
    font-size: 12px;
    color: var(--text-3);
    margin-bottom: 8px;
}

.featured-quick__control {
    margin-bottom: 10px;
}

.featured-quick__actions {
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
}

.featured-quick__meta-title {
    font-size: 12px;
    color: var(--text-3);
    margin-bottom: 4px;
}

.featured-quick__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.featured-quick__tag {
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 999px;
    background: var(--surface-raised);
    color: var(--text-2);
}

.featured-quick__loading {
    font-size: 12px;
    color: var(--text-3);
    margin-top: 8px;
}
</style>
