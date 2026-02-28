<script setup lang="ts">
import { onMounted, onBeforeUnmount } from 'vue';

onMounted(() => {
    document.body.classList.add('app-dark');
});

onBeforeUnmount(() => {
    document.body.classList.remove('app-dark');
});
</script>

<template>
    <div class="master-layout min-h-screen">
        <main class="layout-main">
            <slot />
        </main>
    </div>
</template>

<style scoped>
.master-layout {
    --bg-0: #060a13;
    --bg-1: #121a2f;
    --surface: rgba(18, 26, 47, 0.84);
    --text-main: #f7fbff;
    --text-muted: #9db1cc;
    --accent: #4da3ff;
    --danger: #ff667d;
    min-height: 100vh;
    background: radial-gradient(circle at 10% 20%, #1a2442 0%, var(--bg-0) 42%, #03050c 100%);
    color: var(--text-main);
    display: grid;
    place-items: center;
    padding: 1.25rem;
}

.layout-main {
    width: min(100%, 28rem);
}
</style>
