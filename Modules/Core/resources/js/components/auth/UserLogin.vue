<script setup lang="ts">
import LoginIdentifierField from './LoginIdentifierField.vue';
import LoginPasswordField from './LoginPasswordField.vue';

defineProps<{
    login: string;
    password: string;
    errors: Record<string, string | undefined>;
    processing: boolean;
    loginHint?: string;
}>();

const emit = defineEmits<{
    (event: 'update:login', value: string): void;
    (event: 'update:password', value: string): void;
    (event: 'submit'): void;
}>();
</script>

<template>
    <section class="login-card">
        <header class="login-header">
            <h1>
                <FontAwesomeIcon icon="fa-solid fa-user-shield" class="title-icon" />
                <span>Sign In</span>
            </h1>
            <p>Access your account securely.</p>
        </header>

        <form class="login-form" @submit.prevent="emit('submit')">
            <LoginIdentifierField
                :model-value="login"
                :error="errors.login"
                :disabled="processing"
                autofocus
                @update:model-value="emit('update:login', $event)"
            />
            <p v-if="loginHint" class="field-hint">{{ loginHint }}</p>

            <LoginPasswordField
                :model-value="password"
                :error="errors.password"
                :disabled="processing"
                @update:model-value="emit('update:password', $event)"
            />

            <button class="submit-btn" type="submit" :disabled="processing">
                <FontAwesomeIcon icon="fa-solid fa-right-to-bracket" />
                <span>{{ processing ? 'Signing in...' : 'Sign In' }}</span>
            </button>
        </form>
    </section>
</template>

<style scoped>
.login-card {
    border: 1px solid rgba(113, 145, 190, 0.28);
    border-radius: 1rem;
    background: linear-gradient(180deg, rgba(18, 27, 48, 0.94) 0%, rgba(9, 15, 28, 0.94) 100%);
    padding: 1.5rem;
    box-shadow: 0 20px 55px rgba(0, 0, 0, 0.45);
    display: grid;
    gap: 1.25rem;
}

.login-header h1 {
    margin: 0;
    font-size: 1.5rem;
    letter-spacing: 0.01em;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.login-header p {
    margin: 0.4rem 0 0;
    color: #9db1cc;
    font-size: 0.9375rem;
}

.login-form {
    display: grid;
    gap: 1rem;
}

.field-hint {
    margin: -0.35rem 0 0;
    font-size: 0.8rem;
    color: #9db1cc;
}

.submit-btn {
    border: 0;
    border-radius: 0.75rem;
    min-height: 2.75rem;
    padding: 0.65rem 1rem;
    font-size: 0.94rem;
    font-weight: 600;
    color: #031327;
    background: linear-gradient(135deg, #5ab0ff 0%, #7ad7ff 100%);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.submit-btn:disabled {
    cursor: not-allowed;
    opacity: 0.7;
}

.title-icon {
    color: #8fccff;
}
</style>
