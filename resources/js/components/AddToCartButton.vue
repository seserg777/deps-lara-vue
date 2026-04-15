<script setup lang="ts">
import type { ProductListItem } from '@/api/catalogClient';
import CartIcon from '@/components/CartIcon.vue';
import { useCartStore } from '@/stores/cart';

const props = defineProps<{
    product: ProductListItem;
}>();

const cart = useCartStore();

function on_click(): void {
    cart.addProduct(props.product);
}
</script>

<template>
    <button
        type="button"
        class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 active:scale-[0.98] dark:bg-blue-500 dark:hover:bg-blue-600"
        @click="on_click"
    >
        <CartIcon size_class="h-5 w-5" />
        Add to cart
    </button>
</template>
