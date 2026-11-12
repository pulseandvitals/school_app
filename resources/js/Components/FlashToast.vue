<script setup>
import { usePage } from '@inertiajs/vue3';
import { CircleCheck, X } from 'lucide-vue-next';
import { onBeforeUnmount, ref, watch } from 'vue';

const page = usePage();
const message = ref(null);
let timer = null;

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash?.success) return;
        message.value = flash.success;
        clearTimeout(timer);
        timer = setTimeout(() => (message.value = null), 4500);
    },
    { immediate: true },
);

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-4 opacity-0"
        leave-active-class="transition duration-200 ease-in"
        leave-to-class="translate-y-4 opacity-0"
    >
        <div
            v-if="message"
            role="status"
            class="fixed bottom-5 left-1/2 z-50 flex w-[calc(100%-2rem)] max-w-md -translate-x-1/2 items-start gap-3 rounded-2xl bg-slate-900 px-4 py-3 text-sm text-white shadow-2xl dark:bg-slate-800 dark:ring-1 dark:ring-slate-700"
        >
            <CircleCheck class="mt-0.5 h-5 w-5 shrink-0 text-emerald-400" />
            <p class="flex-1">{{ message }}</p>
            <button type="button" class="rounded-lg p-0.5 text-slate-400 dark:text-slate-500 hover:text-white" aria-label="Dismiss" @click="message = null">
                <X class="h-4 w-4" />
            </button>
        </div>
    </Transition>
</template>
