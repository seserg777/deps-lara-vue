<script setup lang="ts">
import ProductCard from '@/components/ProductCard.vue';
import type { ProductListItem } from '@/api/catalogClient';

defineProps<{
    items: ProductListItem[];
}>();
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <ProductCard
            v-for="p in items"
            :key="p.id"
            :product="p"
        />
    </div>
</template>
