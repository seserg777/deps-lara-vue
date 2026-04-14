<script setup lang="ts">
import { onUnmounted, ref } from 'vue';
import { RouterView } from 'vue-router';
import HeaderSearch from '@/components/HeaderSearch.vue';

const toast_message = ref<string | null>(null);
let toast_timer: ReturnType<typeof setTimeout> | null = null;

function onShowToast(message: string): void {
    toast_message.value = message;
    if (toast_timer !== null) {
        clearTimeout(toast_timer);
    }
    toast_timer = setTimeout(() => {
        toast_message.value = null;
        toast_timer = null;
    }, 2800);
}

onUnmounted(() => {
    if (toast_timer !== null) {
        clearTimeout(toast_timer);
    }
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <header class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <nav
                class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:gap-4"
            >
                <RouterLink
                    to="/"
                    class="shrink-0 text-xl font-semibold text-gray-900 dark:text-white"
                >
                    Catalog
                </RouterLink>
                <HeaderSearch @show-toast="onShowToast" />
            </nav>
        </header>
        <div
            v-if="toast_message"
            class="border-b border-gray-200 bg-gray-800 px-4 py-2 text-center text-sm text-white dark:border-gray-700"
            role="status"
        >
            {{ toast_message }}
        </div>
        <main class="mx-auto max-w-6xl px-4 py-8">
            <RouterView />
        </main>
    </div>
</template>
