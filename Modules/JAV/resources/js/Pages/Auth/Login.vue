<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';

const form = useForm({
    login: '',
    password: '',
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <Head title="Login" />

    <DashboardLayout>
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card mt-5">
                    <div class="card-header text-center">
                        <h3>Login</h3>
                    </div>
                    <div class="card-body">
                        <div v-if="Object.keys(form.errors || {}).length > 0" class="alert alert-danger">
                            <ul class="mb-0">
                                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                            </ul>
                        </div>

                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label for="login" class="form-label">Username or Email</label>
                                <input id="login" v-model="form.login" type="text" class="form-control" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input id="password" v-model="form.password" type="password" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" :disabled="form.processing">Login</button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <p>Don't have an account? <Link :href="route('jav.vue.register')">Register here</Link></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
