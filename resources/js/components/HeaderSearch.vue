<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { searchProducts, type ProductListItem } from '@/api/catalogClient';

const DEBOUNCE_MS = 300;
const MIN_CHARS = 2;
const PREVIEW_LIMIT = 5;
const LISTBOX_ID = 'header-search-listbox';
const INPUT_ID = 'header-search-input';

const query = ref('');
const results = ref<ProductListItem[]>([]);
const loading = ref(false);
const error_message = ref<string | null>(null);
const panel_open = ref(false);
const active_index = ref(-1);

let debounce_timer: ReturnType<typeof setTimeout> | null = null;

let search_abort: AbortController | null = null;
let search_generation = 0;

const root_el = ref<HTMLElement | null>(null);

function isAbortError(e: unknown): boolean {
    if (e instanceof DOMException && e.name === 'AbortError') {
        return true;
    }

    return e instanceof Error && e.name === 'AbortError';
}

const emit = defineEmits<{
    showToast: [message: string];
}>();

const trimmed_query = computed(() => query.value.trim());

const show_more_results = computed(
    () => results.value.length > PREVIEW_LIMIT,
);

const visible_results = computed(() =>
    show_more_results.value
        ? results.value.slice(0, PREVIEW_LIMIT)
        : results.value,
);

const expanded = computed(
    () => panel_open.value && trimmed_query.value.length >= MIN_CHARS,
);

const active_option_id = computed(() =>
    active_index.value >= 0 ? `header-search-opt-${active_index.value}` : undefined,
);

function clearDebounce(): void {
    if (debounce_timer !== null) {
        clearTimeout(debounce_timer);
        debounce_timer = null;
    }
}

async function runSearch(): Promise<void> {
    const q = trimmed_query.value;
    active_index.value = -1;
    if (q.length < MIN_CHARS) {
        search_abort?.abort();
        search_abort = null;
        search_generation += 1;
        results.value = [];
        loading.value = false;
        error_message.value = null;
        panel_open.value = false;

        return;
    }

    search_abort?.abort();
    const controller = new AbortController();
    search_abort = controller;
    const my_generation = (search_generation += 1);

    loading.value = true;
    error_message.value = null;
    try {
        const res = await searchProducts(q, 10, { signal: controller.signal });
        if (my_generation !== search_generation) {
            return;
        }
        results.value = res.data;
        panel_open.value = true;
    } catch (e) {
        if (isAbortError(e)) {
            return;
        }
        if (my_generation !== search_generation) {
            return;
        }
        error_message.value = e instanceof Error ? e.message : 'Search failed';
        results.value = [];
        panel_open.value = true;
    } finally {
        if (my_generation === search_generation) {
            loading.value = false;
        }
    }
}

function scheduleSearch(): void {
    clearDebounce();
    debounce_timer = setTimeout(() => {
        debounce_timer = null;
        void runSearch();
    }, DEBOUNCE_MS);
}

function onInput(): void {
    if (trimmed_query.value.length >= MIN_CHARS) {
        panel_open.value = true;
    }
    scheduleSearch();
}

function closePanel(): void {
    panel_open.value = false;
    active_index.value = -1;
}

function selectProduct(p: ProductListItem): void {
    closePanel();
    emit('showToast', `Opening product page soon — ${p.title}`);
}

function onKeydown(e: KeyboardEvent): void {
    const count = visible_results.value.length;

    if (e.key === 'Escape') {
        e.preventDefault();
        closePanel();

        return;
    }

    if (!expanded.value) {
        return;
    }

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (count === 0) {
            return;
        }
        active_index.value = Math.min(active_index.value + 1, count - 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        active_index.value = Math.max(active_index.value - 1, -1);
    } else if (e.key === 'Enter') {
        if (active_index.value >= 0 && visible_results.value[active_index.value]) {
            e.preventDefault();
            selectProduct(visible_results.value[active_index.value]);
        }
    }
}

function onDocumentPointerDown(ev: PointerEvent): void {
    const root = root_el.value;
    const t = ev.target;
    if (!root || !(t instanceof Node) || root.contains(t)) {
        return;
    }
    closePanel();
}

onMounted(() => {
    document.addEventListener('pointerdown', onDocumentPointerDown);
});

onUnmounted(() => {
    clearDebounce();
    search_abort?.abort();
    search_abort = null;
    search_generation += 1;
    document.removeEventListener('pointerdown', onDocumentPointerDown);
});
</script>

<template>
    <div
        ref="root_el"
        class="relative w-full min-w-0 flex-1 max-w-md"
    >
        <label
            class="sr-only"
            :for="INPUT_ID"
        >Search products</label>
        <input
            :id="INPUT_ID"
            v-model="query"
            type="search"
            role="combobox"
            autocomplete="off"
            aria-autocomplete="list"
            :aria-expanded="expanded"
            :aria-controls="LISTBOX_ID"
            :aria-activedescendant="active_option_id"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-blue-400 dark:focus:ring-blue-400/30"
            placeholder="Search products…"
            @input="onInput"
            @keydown="onKeydown"
        >
        <div
            v-show="expanded"
            class="absolute left-0 right-0 top-full z-50 mt-1 max-h-80 overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-800"
        >
            <ul
                v-if="loading"
                :id="LISTBOX_ID"
                role="listbox"
                class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400"
                aria-busy="true"
            >
                <li role="presentation">Searching…</li>
            </ul>
            <ul
                v-else-if="error_message"
                :id="LISTBOX_ID"
                role="listbox"
                class="px-3 py-4 text-sm text-red-600 dark:text-red-400"
            >
                <li role="presentation">{{ error_message }}</li>
            </ul>
            <ul
                v-else-if="results.length === 0"
                :id="LISTBOX_ID"
                role="listbox"
                class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400"
            >
                <li role="presentation">No products found.</li>
            </ul>
            <div
                v-else
                class="overflow-hidden rounded-lg"
            >
                <ul
                    :id="LISTBOX_ID"
                    role="listbox"
                    class="py-1"
                >
                    <li
                        v-for="(p, i) in visible_results"
                        :id="`header-search-opt-${i}`"
                        :key="p.id"
                        role="option"
                        :aria-selected="active_index === i"
                        class="flex cursor-pointer items-center gap-3 px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                        :class="
                            active_index === i ? 'bg-gray-100 dark:bg-gray-700' : ''
                        "
                        @mousedown.prevent
                        @click="selectProduct(p)"
                    >
                        <div
                            class="h-12 w-12 shrink-0 overflow-hidden rounded border border-gray-200 bg-gray-100 dark:border-gray-600 dark:bg-gray-900"
                        >
                            <img
                                v-if="p.image_url"
                                :src="p.image_url"
                                alt=""
                                class="h-full w-full object-cover"
                            >
                        </div>
                        <span class="line-clamp-2 text-gray-900 dark:text-gray-100">{{
                            p.title
                        }}</span>
                    </li>
                </ul>
                <div
                    v-if="show_more_results"
                    class="border-t border-gray-200 dark:border-gray-600"
                >
                    <RouterLink
                        class="block px-3 py-2.5 text-center text-sm font-medium text-blue-600 hover:bg-gray-50 dark:text-blue-400 dark:hover:bg-gray-700/80"
                        :to="{ name: 'search', query: { q: trimmed_query } }"
                        @mousedown.prevent
                        @click="closePanel"
                    >
                        More results
                    </RouterLink>
                </div>
            </div>
        </div>
    </div>
</template>
