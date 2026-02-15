<script setup>
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { useUIStore } from '@jav/Stores/ui';

const props = defineProps({
    preferences: Object,
});

const uiStore = useUIStore();
const form = useForm({
    show_cover: props.preferences.show_cover ?? false,
    compact_mode: props.preferences.compact_mode || false,
    text_preference: props.preferences.text_preference || 'detailed',
});

const submit = () => {
    form.post(route('jav.preferences.save'), {
        preserveScroll: true,
        onSuccess: () => {
            uiStore.showToast('Preferences updated.', 'success');
        },
        onError: () => {
            uiStore.showToast('Failed to save preferences.', 'error');
        },
    });
};
</script>

<template>
    <Head title="Preferences" />

    
        <div class="ui-container-fluid">
            <div class="ui-row mb-4">
                <div class="ui-col-md-12">
                    <h2><i class="fas fa-sliders-h mr-2"></i>User Preferences</h2>
                    <p class="u-text-muted mb-0">Control cover visibility and dashboard display behavior.</p>
                </div>
            </div>

            <div class="ui-card">
                <div class="ui-card-body">
                    <form @submit.prevent="submit">
                        <div class="ui-row ui-g-3 mb-3">
                            <div class="ui-col-md-6">
                                <label class="ui-form-label">Text Preference</label>
                                <select v-model="form.text_preference" name="text_preference" class="ui-form-select">
                                    <option value="detailed">Detailed</option>
                                    <option value="concise">Concise</option>
                                </select>
                            </div>
                        </div>

                        <hr>

                        <div class="ui-form-check mb-2">
                            <input id="show_cover" v-model="form.show_cover" class="ui-form-check-input" type="checkbox" name="show_cover" :value="1">
                            <label class="ui-form-check-label" for="show_cover">Show cover images (overrides `SHOW_COVER`)</label>
                        </div>

                        <div class="ui-form-check mb-4">
                            <input id="compact_mode" v-model="form.compact_mode" class="ui-form-check-input" type="checkbox" name="compact_mode" :value="1">
                            <label class="ui-form-check-label" for="compact_mode">Compact mode (smaller cards)</label>
                        </div>

                        <button type="submit" class="ui-btn ui-btn-primary" :disabled="form.processing">
                            <i class="fas fa-save mr-1"></i>Save Preferences
                        </button>
                    </form>
                </div>
            </div>
        </div>
    
</template>
