<script setup lang="ts">
import type { ProductListItem } from '@/api/catalogClient';

const props = defineProps<{
    product: ProductListItem;
}>();

function format_price(price: number | string | null): string {
    if (price === null || price === '') {
        return '—';
    }
    if (typeof price === 'number') {
        return Number.isFinite(price) ? price.toFixed(2) : '—';
    }

    return price;
}
</script>

<template>
    <article
        class="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >
        <div class="aspect-square bg-gray-100 dark:bg-gray-900">
            <img
                v-if="props.product.image_url"
                :src="props.product.image_url"
                :alt="props.product.title"
                class="h-full w-full object-cover"
                loading="lazy"
                decoding="async"
            >
        </div>
        <div class="flex flex-1 flex-col p-4">
            <h2 class="mb-2 line-clamp-2 text-base font-semibold text-gray-900 dark:text-white">
                {{ props.product.title }}
            </h2>
            <p class="mt-auto text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ format_price(props.product.price) }}
            </p>
        </div>
    </article>
</template>
