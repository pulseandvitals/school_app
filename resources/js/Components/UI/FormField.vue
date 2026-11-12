<script setup>
import InputError from '@/Components/InputError.vue';

defineProps({
    label: { type: String, required: true },
    for: { type: String, default: null },
    error: { type: String, default: null },
    hint: { type: String, default: '' },
    optional: { type: Boolean, default: false },
});
</script>

<template>
    <div>
        <label :for="$props.for" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">
            {{ label }}
            <span v-if="optional" class="font-normal text-slate-400 dark:text-slate-500">(optional)</span>
        </label>
        <slot />
        <p v-if="hint && !error" class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ hint }}</p>
        <InputError :message="error" class="mt-1.5" />
    </div>
</template>
