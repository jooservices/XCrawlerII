<template>
    <footer class="footer bg-dark text-white text-center py-3 mt-auto">
        <div class="container">
            <p class="mb-0">
                &copy; {{ currentYear }} JAV Collection. All rights reserved.
            </p>
        </div>
    </footer>
</template>

<script setup>
import { computed } from 'vue';

const currentYear = computed(() => new Date().getFullYear());
</script>

<style scoped>
.footer {
    margin-top: 3rem;
}
</style>
