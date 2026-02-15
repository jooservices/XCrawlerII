<template>
    <footer class="footer u-bg-dark u-text-white u-text-center py-3 mt-auto">
        <div class="ui-container">
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
