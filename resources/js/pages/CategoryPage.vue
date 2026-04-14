<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { fetchCategoryChildren, type CategoryItem } from '@/api/catalogClient';

const props = defineProps<{
    id: string;
}>();

const route = useRoute();
const parent = ref<CategoryItem | null>(null);
const children = ref<CategoryItem[]>([]);
const loading = ref(true);
const error_message = ref<string | null>(null);

async function load(): Promise<void> {
    const id = Number(props.id);
    if (Number.isNaN(id)) {
        error_message.value = 'Invalid category';
        loading.value = false;

        return;
    }
    loading.value = true;
    error_message.value = null;
    try {
        const res = await fetchCategoryChildren(id);
        parent.value = res.parent;
        children.value = res.children;
    } catch (e) {
        error_message.value = e instanceof Error ? e.message : 'Failed to load';
    } finally {
        loading.value = false;
    }
}

onMounted(load);
watch(
    () => route.params.id,
    () => {
        void load();
    },
);
</script>

<template>
    <div>
        <nav class="mb-6 text-sm text-gray-500 dark:text-gray-400">
            <RouterLink
                to="/"
                class="text-blue-600 hover:underline dark:text-blue-400"
            >
                Home
            </RouterLink>
            <span class="mx-2">/</span>
            <span class="text-gray-900 dark:text-white">{{ parent?.title ?? 'Category' }}</span>
        </nav>

        <h1 class="mb-2 text-2xl font-bold text-gray-900 dark:text-white">
            {{ parent?.title ?? 'Category' }}
        </h1>
        <p class="mb-6 text-gray-600 dark:text-gray-400">Subcategories</p>

        <div
            v-if="loading"
            class="flex items-center p-8 text-gray-500"
            role="status"
        >
            <svg
                class="mr-3 h-6 w-6 animate-spin text-gray-400"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                />
                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                />
            </svg>
            Loading…
        </div>

        <div
            v-else-if="error_message"
            class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200"
        >
            {{ error_message }}
        </div>

        <div
            v-else-if="children.length === 0"
            class="rounded-lg border border-gray-200 bg-white p-6 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
        >
            No subcategories.
        </div>

        <div
            v-else
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <RouterLink
                v-for="c in children"
                :key="c.id"
                :to="{ name: 'category', params: { id: c.id } }"
                class="block max-w-sm rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700"
            >
                <h2 class="mb-2 text-lg font-semibold tracking-tight text-gray-900 dark:text-white">
                    {{ c.title }}
                </h2>
                <span class="text-sm text-blue-600 dark:text-blue-400">Open →</span>
            </RouterLink>
        </div>
    </div>
</template>
