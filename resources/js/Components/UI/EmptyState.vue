<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
});
</script>

<template>
    <div class="flex flex-col items-center rounded-3xl border-2 border-dashed border-slate-200 bg-white/70 px-6 dark:border-slate-700 dark:bg-slate-900/70 py-12 text-center">
        <span v-if="icon" class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
            <component :is="icon" class="h-7 w-7" />
        </span>
        <h3 class="mt-4 font-display text-xl font-semibold text-slate-800 dark:text-slate-100">{{ title }}</h3>
        <p v-if="description" class="mt-1 max-w-md text-sm text-slate-500 dark:text-slate-400">{{ description }}</p>
        <div v-if="$slots.default" class="mt-5">
            <slot />
        </div>
    </div>
</template>
