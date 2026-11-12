<script setup>
import { usePage } from '@inertiajs/vue3';
import { PartyPopper } from 'lucide-vue-next';
import { ref, watch } from 'vue';

/**
 * Shown after scores are saved when any fish or plant reached a new stage.
 */
const page = usePage();
const items = ref([]);

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.celebrations?.length) items.value = flash.celebrations;
    },
    { immediate: true },
);

const confetti = Array.from({ length: 36 }, (_, i) => ({
    left: `${(i * 29) % 100}%`,
    delay: (i % 12) * 0.12,
    color: ['#22d3ee', '#a3e635', '#facc15', '#f472b6', '#818cf8', '#fb923c'][i % 6],
    duration: 2.4 + (i % 5) * 0.3,
}));
</script>

<template>
    <Transition
        enter-active-class="transition duration-300"
        enter-from-class="opacity-0"
        leave-active-class="transition duration-200"
        leave-to-class="opacity-0"
    >
        <div
            v-if="items.length"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden bg-slate-900/50 p-4 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-labelledby="celebration-title"
            @click.self="items = []"
            @keydown.esc="items = []"
        >
            <span
                v-for="(piece, i) in confetti"
                :key="i"
                class="pointer-events-none absolute top-0 h-3 w-2 animate-fall rounded-sm motion-reduce:hidden"
                :style="{ left: piece.left, backgroundColor: piece.color, animationDelay: `${piece.delay}s`, animationDuration: `${piece.duration}s` }"
            />
            <div class="relative w-full max-w-md animate-pop-in rounded-3xl bg-white dark:bg-slate-900 p-6 text-center shadow-2xl">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-yellow-300 to-orange-400 text-white shadow-lg">
                    <PartyPopper class="h-8 w-8" />
                </div>
                <h2 id="celebration-title" class="mt-4 font-display text-2xl font-semibold text-slate-800 dark:text-slate-100">Growth milestone!</h2>
                <ul class="mt-4 space-y-2 text-left">
                    <li v-for="item in items" :key="item.name + item.stage" class="rounded-2xl bg-slate-50 dark:bg-slate-800/60 px-4 py-2 text-sm text-slate-700 dark:text-slate-200">
                        <strong class="text-slate-900 dark:text-white">{{ item.name }}</strong> grew to
                        <strong class="text-emerald-700 dark:text-emerald-300">Stage {{ item.stage }} · {{ item.stage_name }}</strong>
                    </li>
                </ul>
                <button
                    type="button"
                    class="mt-6 w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white transition hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white"
                    autofocus
                    @click="items = []"
                >
                    Wonderful!
                </button>
            </div>
        </div>
    </Transition>
</template>
