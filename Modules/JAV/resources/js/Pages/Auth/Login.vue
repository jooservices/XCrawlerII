<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

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

    
        <div class="ui-row u-justify-center">
            <div class="ui-col-md-4">
                <div class="ui-card mt-5">
                    <div class="ui-card-header u-text-center">
                        <h3>Login</h3>
                    </div>
                    <div class="ui-card-body">
                        <div v-if="Object.keys(form.errors || {}).length > 0" class="ui-alert ui-alert-danger">
                            <ul class="mb-0">
                                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                            </ul>
                        </div>

                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label for="login" class="ui-form-label">Username or Email</label>
                                <input id="login" v-model="form.login" type="text" class="ui-form-control" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="ui-form-label">Password</label>
                                <input id="password" v-model="form.password" type="password" class="ui-form-control" required>
                            </div>
                            <div class="u-grid">
                                <button type="submit" class="ui-btn ui-btn-primary" :disabled="form.processing">Login</button>
                            </div>
                        </form>

                        <div class="u-text-center mt-3">
                            <p>Don't have an account? <Link :href="route('jav.vue.register')">Register here</Link></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
</template>
