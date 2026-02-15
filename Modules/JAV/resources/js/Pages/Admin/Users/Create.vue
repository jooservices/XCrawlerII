<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import PageShell from '@jav/Components/UI/PageShell.vue';
import SectionHeader from '@jav/Components/UI/SectionHeader.vue';

const props = defineProps({
    roles: Array,
});

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    roles: [],
});

const submit = () => {
    form.post(route('admin.users.store'));
};
</script>

<template>
    <Head title="Create User" />

    <PageShell>
        <template #header>
            <SectionHeader title="Create User" subtitle="Add a new user account and assign roles" />
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
                        <label class="ui-form-label">Username</label>
                        <input v-model="form.username" type="text" class="ui-form-control" required>
                        <div v-if="form.errors.username" class="u-text-danger small">{{ form.errors.username }}</div>
                    </div>
                    <div class="ui-col-md-6">
                        <label class="ui-form-label">Email</label>
                        <input v-model="form.email" type="email" class="ui-form-control" required>
                        <div v-if="form.errors.email" class="u-text-danger small">{{ form.errors.email }}</div>
                    </div>
                    <div class="ui-col-md-6">
                        <label class="ui-form-label">Password</label>
                        <input v-model="form.password" type="password" class="ui-form-control" required>
                        <div v-if="form.errors.password" class="u-text-danger small">{{ form.errors.password }}</div>
                    </div>
                </div>

                <div class="mt-4">
                    <h5>Assign Roles</h5>
                    <div class="ui-row ui-g-2">
                        <div v-for="role in roles" :key="role.id" class="ui-col-md-4">
                            <div class="ui-form-check">
                                <input :id="`role-${role.id}`" v-model="form.roles" class="ui-form-check-input" type="checkbox" :value="role.id">
                                <label class="ui-form-check-label" :for="`role-${role.id}`">{{ role.name }}</label>
                            </div>
                        </div>
                    </div>
                    <div v-if="form.errors.roles" class="u-text-danger small">{{ form.errors.roles }}</div>
                </div>
            </div>
            <div class="ui-card-footer u-flex gap-2">
                <button type="submit" class="ui-btn ui-btn-primary" :disabled="form.processing">Create</button>
                <Link :href="route('admin.users.index')" class="ui-btn ui-btn-outline-secondary">Cancel</Link>
            </div>
        </form>
    </PageShell>
</template>
