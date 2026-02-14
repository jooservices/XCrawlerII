<script setup>
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';

const props = defineProps({
    preferences: Object,
});

const form = useForm({
    hide_actors: props.preferences.hide_actors || false,
    hide_tags: props.preferences.hide_tags || false,
    compact_mode: props.preferences.compact_mode || false,
    text_preference: props.preferences.text_preference || 'detailed',
    language: props.preferences.language || 'en',
});

const submit = () => {
    form.post(route('jav.preferences.save'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Preferences" />

    <DashboardLayout>
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-md-12">
                    <h2><i class="fas fa-sliders-h me-2"></i>User Preferences</h2>
                    <p class="text-muted mb-0">Control dashboard display, text style, and saved behavior.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form @submit.prevent="submit">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Text Preference</label>
                                <select v-model="form.text_preference" name="text_preference" class="form-select">
                                    <option value="detailed">Detailed</option>
                                    <option value="concise">Concise</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Language Preference</label>
                                <select v-model="form.language" name="language" class="form-select">
                                    <option value="en">English UI</option>
                                    <option value="jp">Japanese-first metadata</option>
                                </select>
                            </div>
                        </div>

                        <hr>

                        <div class="form-check mb-2">
                            <input id="hide_actors" v-model="form.hide_actors" class="form-check-input" type="checkbox" name="hide_actors" :value="1">
                            <label class="form-check-label" for="hide_actors">Hide actor chips on movie cards</label>
                        </div>

                        <div class="form-check mb-2">
                            <input id="hide_tags" v-model="form.hide_tags" class="form-check-input" type="checkbox" name="hide_tags" :value="1">
                            <label class="form-check-label" for="hide_tags">Hide tag chips on movie cards</label>
                        </div>

                        <div class="form-check mb-4">
                            <input id="compact_mode" v-model="form.compact_mode" class="form-check-input" type="checkbox" name="compact_mode" :value="1">
                            <label class="form-check-label" for="compact_mode">Compact mode (smaller cards)</label>
                        </div>

                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            <i class="fas fa-save me-1"></i>Save Preferences
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
