<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { computed, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import CartIcon from '@/components/CartIcon.vue';
import { useCartStore } from '@/stores/cart';

const cart = useCartStore();
const { totalItemCount } = storeToRefs(cart);

const badge_pop = ref(false);
const icon_bounce = ref(false);
let prev_count = totalItemCount.value;

watch(totalItemCount, (next) => {
    if (next > prev_count) {
        badge_pop.value = true;
        icon_bounce.value = true;
        window.setTimeout(() => {
            badge_pop.value = false;
        }, 400);
        window.setTimeout(() => {
            icon_bounce.value = false;
        }, 600);
    } else if (next < prev_count) {
        badge_pop.value = true;
        window.setTimeout(() => {
            badge_pop.value = false;
        }, 350);
    }
    prev_count = next;
});

const badge_class = computed(() =>
    [
        'cart-badge-transition inline-flex min-w-[1.25rem] items-center justify-center rounded-full px-1.5 text-xs font-semibold tabular-nums',
        'bg-blue-600 text-white dark:bg-blue-500',
        badge_pop.value ? 'cart-badge-pop' : '',
    ].join(' '),
);

const icon_wrapper_class = computed(() =>
    ['relative inline-flex text-gray-700 dark:text-gray-200', icon_bounce.value ? 'cart-icon-bounce' : ''].join(' '),
);
</script>

<template>
    <component
        :is="totalItemCount > 0 ? RouterLink : 'span'"
        v-bind="totalItemCount > 0 ? { to: { name: 'cart' } } : {}"
        class="relative inline-flex shrink-0 items-center justify-center rounded-lg p-2 transition-colors hover:bg-gray-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 dark:hover:bg-gray-700/80"
        :class="totalItemCount === 0 ? 'cursor-default opacity-80' : ''"
        :aria-disabled="totalItemCount === 0 ? true : undefined"
        :aria-label="
            totalItemCount > 0
                ? `Shopping cart, ${totalItemCount} items`
                : 'Shopping cart, empty'
        "
    >
        <span :class="icon_wrapper_class">
            <CartIcon />
            <span
                v-if="totalItemCount > 0"
                :key="totalItemCount"
                :class="badge_class"
                style="position: absolute; top: -0.125rem; right: -0.125rem"
            >{{ totalItemCount > 99 ? '99+' : totalItemCount }}</span>
        </span>
    </component>
</template>

<style scoped>
@keyframes cart-badge-pop {
    0% {
        transform: scale(1);
    }
    45% {
        transform: scale(1.35);
    }
    100% {
        transform: scale(1);
    }
}

@keyframes cart-icon-bounce {
    0%,
    100% {
        transform: translateY(0);
    }
    35% {
        transform: translateY(-4px);
    }
    55% {
        transform: translateY(0);
    }
    75% {
        transform: translateY(-2px);
    }
}

.cart-badge-pop {
    animation: cart-badge-pop 0.4s ease-out;
}

.cart-icon-bounce {
    animation: cart-icon-bounce 0.55s ease-out;
}

.cart-badge-transition {
    transition: transform 0.15s ease-out;
}
</style>
