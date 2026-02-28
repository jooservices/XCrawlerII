<script setup lang="ts">
import MasterLayout from '../../layouts/MasterLayout.vue';
import UserLogin from '../../components/auth/UserLogin.vue';
import SeoHead from '../../components/shared/SeoHead.vue';
import { useLoginForm } from '../../composables/auth/useLoginForm';

const { form, errors, loginHint, submit } = useLoginForm();
</script>

<template>
    <MasterLayout>
        <SeoHead
            :seo="{
                title: 'Sign In',
                description: 'Sign in to access your XCrawler workspace securely.',
                indexable: false,
                robots: 'noindex,nofollow',
                type: 'website',
            }"
        />
        <UserLogin
            :login="form.login"
            :password="form.password"
            :errors="errors"
            :processing="form.processing"
            :login-hint="loginHint"
            @update:login="form.login = $event"
            @update:password="form.password = $event"
            @submit="submit"
        />
    </MasterLayout>
</template>
