<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    icon: { type: [Object, Function], default: null },
    color: { type: String, default: 'from-slate-700 to-slate-900' },
    hint: { type: String, default: '' },
});
</script>

<template>
    <div class="flex items-center gap-4 rounded-3xl bg-white dark:bg-slate-900 p-4 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800 sm:p-5">
        <span v-if="icon" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow" :class="color">
            <component :is="icon" class="h-6 w-6" />
        </span>
        <div class="min-w-0">
            <p class="truncate text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ label }}</p>
            <p class="font-display text-2xl font-semibold text-slate-800 dark:text-slate-100">{{ value }}</p>
            <p v-if="hint" class="truncate text-xs text-slate-500 dark:text-slate-400">{{ hint }}</p>
        </div>
    </div>
</template>
