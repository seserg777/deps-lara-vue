import { defineStore } from 'pinia';
import type { ProductListItem } from '@/api/catalogClient';

export type CartLine = ProductListItem & { quantity: number };

export function parse_price_for_total(price: number | string | null): number {
    if (price === null || price === '') {
        return 0;
    }
    if (typeof price === 'number') {
        return Number.isFinite(price) ? price : 0;
    }
    const n = Number.parseFloat(String(price));

    return Number.isFinite(n) ? n : 0;
}

export const useCartStore = defineStore('cart', {
    state: () => ({
        lines: [] as CartLine[],
    }),
    getters: {
        totalItemCount(state): number {
            return state.lines.reduce((sum, line) => sum + line.quantity, 0);
        },
        subtotal(state): number {
            return state.lines.reduce(
                (sum, line) => sum + parse_price_for_total(line.price) * line.quantity,
                0,
            );
        },
    },
    actions: {
        addProduct(product: ProductListItem): void {
            const existing = this.lines.find((l) => l.id === product.id);
            if (existing) {
                existing.quantity += 1;
            } else {
                this.lines.push({
                    ...product,
                    quantity: 1,
                });
            }
        },
        removeLine(product_id: number): void {
            this.lines = this.lines.filter((l) => l.id !== product_id);
        },
        setQuantity(product_id: number, quantity: number): void {
            const q = Math.max(0, Math.floor(quantity));
            const line = this.lines.find((l) => l.id === product_id);
            if (!line) {
                return;
            }
            if (q === 0) {
                this.removeLine(product_id);

                return;
            }
            line.quantity = q;
        },
        increment(product_id: number): void {
            const line = this.lines.find((l) => l.id === product_id);
            if (line) {
                line.quantity += 1;
            }
        },
        decrement(product_id: number): void {
            const line = this.lines.find((l) => l.id === product_id);
            if (!line) {
                return;
            }
            if (line.quantity <= 1) {
                this.removeLine(product_id);
            } else {
                line.quantity -= 1;
            }
        },
    },
});
