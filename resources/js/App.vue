<script setup lang="ts">
import { RouterView } from 'vue-router';
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <header class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                <RouterLink
                    to="/"
                    class="text-xl font-semibold text-gray-900 dark:text-white"
                >
                    Catalog
                </RouterLink>
            </nav>
        </header>
        <main class="mx-auto max-w-6xl px-4 py-8">
            <RouterView />
        </main>
    </div>
</template>
