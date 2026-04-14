<script setup lang="ts">
import { ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
    categoryPathFromIds,
    fetchCategoryChildren,
    parseCategoryPathParam,
    type CategoryItem,
} from '@/api/catalogClient';

const props = defineProps<{
    pathMatch?: string | string[];
}>();

const parent = ref<CategoryItem | null>(null);
const breadcrumb = ref<CategoryItem[]>([]);
const children = ref<CategoryItem[]>([]);
const loading = ref(true);
const error_message = ref<string | null>(null);
const not_found = ref(false);

function ids_equal(a: number[], b: number[]): boolean {
    if (a.length !== b.length) {
        return false;
    }

    return a.every((v, i) => v === b[i]);
}

async function load(): Promise<void> {
    not_found.value = false;
    error_message.value = null;
    const url_ids = parseCategoryPathParam(props.pathMatch);
    if (url_ids === null) {
        not_found.value = true;
        loading.value = false;
        parent.value = null;
        breadcrumb.value = [];
        children.value = [];

        return;
    }
    const last_id = url_ids[url_ids.length - 1];
    loading.value = true;
    try {
        const res = await fetchCategoryChildren(last_id);
        const canonical_ids = res.breadcrumb.map((b) => b.id);
        if (!ids_equal(url_ids, canonical_ids)) {
            not_found.value = true;
            parent.value = null;
            breadcrumb.value = [];
            children.value = [];

            return;
        }
        parent.value = res.parent;
        breadcrumb.value = res.breadcrumb;
        children.value = res.children;
    } catch (e) {
        error_message.value = e instanceof Error ? e.message : 'Failed to load';
    } finally {
        loading.value = false;
    }
}

watch(
    () => props.pathMatch,
    () => {
        void load();
    },
    { immediate: true },
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
            <template
                v-for="(seg, idx) in breadcrumb"
                :key="seg.id"
            >
                <span class="mx-2">/</span>
                <RouterLink
                    v-if="idx < breadcrumb.length - 1"
                    :to="categoryPathFromIds(breadcrumb.slice(0, idx + 1).map((s) => s.id))"
                    class="text-blue-600 hover:underline dark:text-blue-400"
                >
                    {{ seg.title }}
                </RouterLink>
                <span
                    v-else
                    class="text-gray-900 dark:text-white"
                >{{ seg.title }}</span>
            </template>
        </nav>

        <template v-if="!not_found">
            <h1 class="mb-2 text-2xl font-bold text-gray-900 dark:text-white">
                {{ parent?.title ?? 'Category' }}
            </h1>
            <p class="mb-6 text-gray-600 dark:text-gray-400">Subcategories</p>
        </template>

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
            v-else-if="not_found"
            class="rounded-lg border border-gray-200 bg-white p-6 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
        >
            Category not found.
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
                :to="categoryPathFromIds([...breadcrumb.map((b) => b.id), c.id])"
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
