<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

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

    
        <div class="ui-row u-justify-center">
            <div class="ui-col-md-5">
                <div class="ui-card mt-5">
                    <div class="ui-card-header u-text-center">
                        <h3>Register</h3>
                    </div>
                    <div class="ui-card-body">
                        <div v-if="Object.keys(form.errors || {}).length > 0" class="ui-alert ui-alert-danger">
                            <ul class="mb-0">
                                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                            </ul>
                        </div>

                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label for="name" class="ui-form-label">Name</label>
                                <input id="name" v-model="form.name" type="text" class="ui-form-control" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="username" class="ui-form-label">Username</label>
                                <input id="username" v-model="form.username" type="text" class="ui-form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="ui-form-label">Email address</label>
                                <input id="email" v-model="form.email" type="email" class="ui-form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="ui-form-label">Password</label>
                                <input id="password" v-model="form.password" type="password" class="ui-form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password_confirmation" class="ui-form-label">Confirm Password</label>
                                <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="ui-form-control" required>
                            </div>
                            <div class="u-grid">
                                <button type="submit" class="ui-btn ui-btn-success" :disabled="form.processing">Register</button>
                            </div>
                        </form>

                        <div class="u-text-center mt-3">
                            <p>Already have an account? <Link :href="route('jav.vue.login')">Login here</Link></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
</template>
