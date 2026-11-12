<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
    backHref: { type: String, default: null },
    backLabel: { type: String, default: 'Back' },
});
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <Link
                v-if="backHref"
                :href="backHref"
                class="mb-2 inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 dark:text-slate-400 transition hover:text-slate-800 dark:hover:text-slate-100"
            >
                <ArrowLeft class="h-4 w-4" />
                {{ backLabel }}
            </Link>
            <p v-if="eyebrow" class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ eyebrow }}</p>
            <h1 class="flex items-center gap-3 font-display text-3xl font-semibold text-slate-800 dark:text-slate-100 sm:text-4xl">
                <slot name="icon" />
                <span class="truncate">{{ title }}</span>
            </h1>
            <p v-if="subtitle" class="mt-1 max-w-2xl text-slate-500 dark:text-slate-400">{{ subtitle }}</p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
