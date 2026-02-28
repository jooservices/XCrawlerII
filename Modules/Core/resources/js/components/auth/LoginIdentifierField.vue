<script setup lang="ts">
defineProps<{
    modelValue: string;
    error?: string;
    disabled?: boolean;
    autofocus?: boolean;
}>();

const emit = defineEmits<{
    (event: 'update:modelValue', value: string): void;
}>();

const handleInput = (event: Event): void => {
    emit('update:modelValue', (event.target as HTMLInputElement).value);
};
</script>

<template>
    <div class="field-wrap">
        <label for="login" class="field-label">Username or Email</label>
        <input
            id="login"
            :value="modelValue"
            type="text"
            class="field-input"
            :disabled="disabled"
            :autofocus="autofocus"
            autocomplete="username"
            @input="handleInput"
        >
        <p v-if="error" class="field-error">{{ error }}</p>
    </div>
</template>

<style scoped>
.field-wrap {
    display: grid;
    gap: 0.5rem;
}

.field-label {
    font-size: 0.875rem;
    color: #d9e7ff;
    letter-spacing: 0.01em;
}

.field-input {
    width: 100%;
    border-radius: 0.75rem;
    border: 1px solid rgba(133, 164, 207, 0.35);
    background: rgba(8, 14, 28, 0.85);
    color: #f5f9ff;
    padding: 0.75rem 0.875rem;
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.field-input:focus {
    border-color: #4da3ff;
    box-shadow: 0 0 0 3px rgba(77, 163, 255, 0.25);
}

.field-error {
    margin: 0;
    color: #ff8ea1;
    font-size: 0.8125rem;
}
</style>
