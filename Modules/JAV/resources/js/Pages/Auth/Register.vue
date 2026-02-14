<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@jav/Layouts/DashboardLayout.vue';

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head title="Register" />

    <DashboardLayout>
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card mt-5">
                    <div class="card-header text-center">
                        <h3>Register</h3>
                    </div>
                    <div class="card-body">
                        <div v-if="Object.keys(form.errors || {}).length > 0" class="alert alert-danger">
                            <ul class="mb-0">
                                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                            </ul>
                        </div>

                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input id="name" v-model="form.name" type="text" class="form-control" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input id="username" v-model="form.username" type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input id="email" v-model="form.email" type="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input id="password" v-model="form.password" type="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success" :disabled="form.processing">Register</button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <p>Already have an account? <Link :href="route('jav.vue.login')">Login here</Link></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
