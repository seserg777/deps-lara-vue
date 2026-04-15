<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();

const query_display = computed(() => {
    const raw = route.query.q;
    const s = Array.isArray(raw) ? raw[0] : raw;

    return typeof s === 'string' ? s.trim() : '';
});
</script>

<template>
    <div>
        <h1 class="mb-2 text-2xl font-bold text-gray-900 dark:text-white">
            <template v-if="query_display">
                Search results for {{ query_display }}
            </template>
            <template v-else>
                Search results
            </template>
        </h1>
    </div>
</template>
