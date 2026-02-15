<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageShell from '@jav/Components/UI/PageShell.vue';
import SectionHeader from '@jav/Components/UI/SectionHeader.vue';

const props = defineProps({
    role: Object,
    permissions: Object,
});

const form = useForm({
    name: props.role.name || '',
    slug: props.role.slug || '',
    description: props.role.description || '',
    permissions: (props.role.permissions || []).map((permission) => permission.id),
});

const categories = computed(() => Object.entries(props.permissions || {}));

const submit = () => {
    form.put(route('admin.roles.update', props.role.id));
};
</script>

<template>
    <Head :title="`Edit Role: ${role.name}`" />

    <PageShell>
        <template #header>
            <SectionHeader :title="`Edit Role: ${role.name}`" subtitle="Update role metadata and permissions" />
        </template>

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
                        <input v-model="form.slug" type="text" class="ui-form-control" required>
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
                <button type="submit" class="ui-btn ui-btn-primary" :disabled="form.processing">Update</button>
                <Link :href="route('admin.roles.index')" class="ui-btn ui-btn-outline-secondary">Cancel</Link>
            </div>
        </form>
    </PageShell>
</template>
