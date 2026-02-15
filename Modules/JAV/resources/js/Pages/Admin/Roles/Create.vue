<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    permissions: Object,
});

const form = useForm({
    name: '',
    slug: '',
    description: '',
    permissions: [],
});

const categories = computed(() => Object.entries(props.permissions || {}));

const submit = () => {
    form.post(route('admin.roles.store'));
};
</script>

<template>
    <Head title="Create Role" />

    <div class="ui-container-fluid">
        <h2 class="mb-3">Create Role</h2>

        <form class="ui-card" @submit.prevent="submit">
            <div class="ui-card-body">
                <div class="ui-row ui-g-3">
                    <div class="ui-col-md-6">
                        <label class="ui-form-label">Name</label>
                        <input v-model="form.name" type="text" class="ui-form-control" required>
                        <div v-if="form.errors.name" class="u-text-danger small">{{ form.errors.name }}</div>
                    </div>
                    <div class="ui-col-md-6">
                        <label class="ui-form-label">Slug</label>
                        <input v-model="form.slug" type="text" class="ui-form-control" placeholder="Auto-generated if empty">
                        <div v-if="form.errors.slug" class="u-text-danger small">{{ form.errors.slug }}</div>
                    </div>
                    <div class="ui-col-12">
                        <label class="ui-form-label">Description</label>
                        <textarea v-model="form.description" class="ui-form-control" rows="3" />
                        <div v-if="form.errors.description" class="u-text-danger small">{{ form.errors.description }}</div>
                    </div>
                </div>

                <div class="mt-4">
                    <h5>Permissions</h5>
                    <div v-for="[category, perms] in categories" :key="category" class="ui-card mb-3">
                        <div class="ui-card-header u-capitalize">{{ category }}</div>
                        <div class="ui-card-body">
                            <div class="ui-row ui-g-2">
                                <div v-for="permission in perms" :key="permission.id" class="ui-col-md-6">
                                    <div class="ui-form-check">
                                        <input
                                            :id="`perm-${permission.id}`"
                                            v-model="form.permissions"
                                            class="ui-form-check-input"
                                            type="checkbox"
                                            :value="permission.id"
                                        >
                                        <label class="ui-form-check-label" :for="`perm-${permission.id}`">
                                            {{ permission.name }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="form.errors.permissions" class="u-text-danger small">{{ form.errors.permissions }}</div>
                </div>
            </div>
            <div class="ui-card-footer u-flex gap-2">
                <button type="submit" class="ui-btn ui-btn-primary" :disabled="form.processing">Create</button>
                <Link :href="route('admin.roles.index')" class="ui-btn ui-btn-outline-secondary">Cancel</Link>
            </div>
        </form>
    </div>
</template>
