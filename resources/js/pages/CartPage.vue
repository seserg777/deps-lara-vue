<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { computed, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { type CartLine, parse_price_for_total, useCartStore } from '@/stores/cart';

const cart = useCartStore();
const { lines, totalItemCount, subtotal } = storeToRefs(cart);

const checkout_open = ref(false);
const order_placed = ref(false);

const customer_name = ref('');
const customer_email = ref('');
const customer_address = ref('');

const can_checkout = computed(
    () =>
        customer_name.value.trim().length > 0
        && customer_email.value.trim().length > 0
        && customer_address.value.trim().length > 0,
);

function format_price(price: number | string | null): string {
    if (price === null || price === '') {
        return '—';
    }
    if (typeof price === 'number') {
        return Number.isFinite(price) ? price.toFixed(2) : '—';
    }

    return price;
}

function format_money(n: number): string {
    return n.toFixed(2);
}

function line_total(line: CartLine): number {
    return parse_price_for_total(line.price) * line.quantity;
}

function proceed_to_checkout(): void {
    checkout_open.value = true;
    order_placed.value = false;
}

function place_order(): void {
    if (!can_checkout.value) {
        return;
    }
    order_placed.value = true;
}

function back_to_cart(): void {
    checkout_open.value = false;
    order_placed.value = false;
}
</script>

<template>
    <div class="space-y-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Shopping cart
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ totalItemCount }} item(s)
                </p>
            </div>
            <RouterLink
                to="/"
                class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
            >
                Continue shopping
            </RouterLink>
        </div>

        <div
            v-if="lines.length === 0"
            class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-600 dark:bg-gray-800"
        >
            <p class="text-gray-700 dark:text-gray-300">
                Your cart is empty.
            </p>
            <RouterLink
                to="/"
                class="mt-4 inline-flex text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
            >
                Browse catalog
            </RouterLink>
        </div>

        <template v-else>
            <div
                v-if="!checkout_open"
                class="space-y-6"
            >
                <ul class="divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 bg-white dark:divide-gray-700 dark:border-gray-700 dark:bg-gray-800">
                    <li
                        v-for="line in lines"
                        :key="line.id"
                        class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center"
                    >
                        <div
                            class="h-24 w-24 shrink-0 overflow-hidden rounded-md border border-gray-200 bg-gray-100 dark:border-gray-600 dark:bg-gray-900"
                        >
                            <img
                                v-if="line.image_url"
                                :src="line.image_url"
                                :alt="line.title"
                                class="h-full w-full object-cover"
                                loading="lazy"
                                decoding="async"
                            >
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ line.title }}
                            </p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ format_price(line.price) }} each
                            </p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <div class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-600">
                                    <button
                                        type="button"
                                        class="px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700/80"
                                        aria-label="Decrease quantity"
                                        @click="cart.decrement(line.id)"
                                    >
                                        −
                                    </button>
                                    <span
                                        class="min-w-[2rem] px-2 py-1.5 text-center text-sm tabular-nums text-gray-900 dark:text-white"
                                    >{{ line.quantity }}</span>
                                    <button
                                        type="button"
                                        class="px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700/80"
                                        aria-label="Increase quantity"
                                        @click="cart.increment(line.id)"
                                    >
                                        +
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    class="text-sm font-medium text-red-600 hover:underline dark:text-red-400"
                                    @click="cart.removeLine(line.id)"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                        <div class="text-right sm:min-w-[6rem]">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Line total
                            </p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ format_money(line_total(line)) }}
                            </p>
                        </div>
                    </li>
                </ul>

                <div
                    class="flex flex-col items-stretch justify-between gap-4 rounded-lg border border-gray-200 bg-white p-4 sm:flex-row sm:items-center dark:border-gray-700 dark:bg-gray-800"
                >
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        Subtotal:
                        <span class="tabular-nums">{{ format_money(subtotal) }}</span>
                    </p>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600"
                        @click="proceed_to_checkout"
                    >
                        Proceed to checkout
                    </button>
                </div>
            </div>

            <div
                v-else
                class="space-y-6"
            >
                <div
                    v-if="order_placed"
                    class="rounded-lg border border-green-200 bg-green-50 p-6 text-center dark:border-green-800 dark:bg-green-950/40"
                >
                    <p class="text-lg font-semibold text-green-900 dark:text-green-100">
                        Order placed
                    </p>
                    <p class="mt-2 text-sm text-green-800 dark:text-green-200">
                        Thank you — this is a demo checkout; no payment was processed.
                    </p>
                    <RouterLink
                        to="/"
                        class="mt-6 inline-flex rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800 dark:bg-green-600 dark:hover:bg-green-500"
                    >
                        Back to catalog
                    </RouterLink>
                </div>

                <div
                    v-else
                    class="mx-auto max-w-lg space-y-6 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Checkout
                        </h2>
                        <button
                            type="button"
                            class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
                            @click="back_to_cart"
                        >
                            Back to cart
                        </button>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Demo only — details are not sent anywhere.
                    </p>
                    <form
                        class="space-y-4"
                        @submit.prevent="place_order"
                    >
                        <div>
                            <label
                                for="checkout-name"
                                class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Full name</label>
                            <input
                                id="checkout-name"
                                v-model="customer_name"
                                type="text"
                                autocomplete="name"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                required
                            >
                        </div>
                        <div>
                            <label
                                for="checkout-email"
                                class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Email</label>
                            <input
                                id="checkout-email"
                                v-model="customer_email"
                                type="email"
                                autocomplete="email"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                required
                            >
                        </div>
                        <div>
                            <label
                                for="checkout-address"
                                class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Shipping address</label>
                            <textarea
                                id="checkout-address"
                                v-model="customer_address"
                                rows="3"
                                autocomplete="street-address"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                required
                            />
                        </div>
                        <button
                            type="submit"
                            class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-blue-500 dark:hover:bg-blue-600"
                            :disabled="!can_checkout"
                        >
                            Place order
                        </button>
                    </form>
                </div>
            </div>
        </template>
    </div>
</template>
