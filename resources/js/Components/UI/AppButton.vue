<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Button or link in the app's rounded game style.
 * variant: primary | secondary | danger | ghost; tone: slate | aquarium | garden
 */
const props = defineProps({
    href: { type: String, default: null },
    variant: { type: String, default: 'primary' },
    tone: { type: String, default: 'slate' },
    type: { type: String, default: 'button' },
    size: { type: String, default: 'md' },
    disabled: { type: Boolean, default: false },
});

const tones = {
    slate: 'bg-slate-900 hover:bg-slate-700 focus-visible:outline-slate-900 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white dark:focus-visible:outline-slate-100',
    aquarium: 'bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 focus-visible:outline-cyan-600',
    garden: 'bg-gradient-to-r from-lime-500 to-emerald-600 hover:from-lime-600 hover:to-emerald-700 focus-visible:outline-emerald-600',
};

const classes = computed(() => [
    'inline-flex items-center justify-center gap-2 rounded-2xl font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
    props.size === 'sm' ? 'px-3 py-1.5 text-sm' : 'px-4 py-2.5 text-sm',
    {
        primary: ['text-white shadow-md', tones[props.tone] ?? tones.slate],
        secondary: 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/60 focus-visible:outline-slate-400',
        danger: 'bg-rose-600 text-white shadow-sm hover:bg-rose-700 focus-visible:outline-rose-600',
        ghost: 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white focus-visible:outline-slate-400',
    }[props.variant],
]);
</script>

<template>
    <Link v-if="href" :href="href" :class="classes">
        <slot />
    </Link>
    <button v-else :type="type" :class="classes" :disabled="disabled">
        <slot />
    </button>
</template>
