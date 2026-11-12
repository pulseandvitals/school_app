<script setup>
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { useTheme } from '@/lib/theme';

const { mode, setMode } = useTheme();

const options = [
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
    { value: 'system', label: 'System', icon: Monitor },
];
</script>

<template>
    <div class="inline-flex rounded-full bg-slate-100 p-1 dark:bg-slate-800" role="radiogroup" aria-label="Colour theme">
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            role="radio"
            :aria-checked="mode === option.value"
            :title="`${option.label} theme`"
            class="flex h-7 w-7 items-center justify-center rounded-full transition"
            :class="mode === option.value
                ? 'bg-white text-slate-900 shadow dark:bg-slate-600 dark:text-white'
                : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100'"
            @click="setMode(option.value)"
        >
            <component :is="option.icon" class="h-4 w-4" />
            <span class="sr-only">{{ option.label }}</span>
        </button>
    </div>
</template>
