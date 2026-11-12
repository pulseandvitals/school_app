<script setup>
import { Sparkles } from 'lucide-vue-next';
import { computed } from 'vue';
import { themeFor } from '@/lib/habitat';

/**
 * Stage badge + progress towards the next stage, with a plain-language
 * explanation of where the growth comes from.
 */
const props = defineProps({
    growth: { type: Object, required: true },
    points: { type: Number, required: true },
    habitatType: { type: String, required: true },
    compact: { type: Boolean, default: false },
});

const theme = computed(() => themeFor(props.habitatType));
const isMax = computed(() => props.growth.points_to_next === null);
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold" :class="theme.chip">
                <Sparkles v-if="isMax" class="h-3.5 w-3.5" />
                Stage {{ growth.stage }} · {{ growth.name }}
            </span>
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ points }} pts</span>
        </div>

        <div class="flex gap-1" aria-hidden="true">
            <span
                v-for="n in growth.max_stage"
                :key="n"
                class="h-1.5 flex-1 rounded-full"
                :class="n <= growth.stage ? theme.bar : 'bg-slate-200 dark:bg-slate-700'"
            />
        </div>

        <template v-if="!compact">
            <div
                class="h-2.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                role="progressbar"
                :aria-valuenow="growth.progress"
                aria-valuemin="0"
                aria-valuemax="100"
                :aria-label="`Progress to stage ${growth.stage + 1}`"
            >
                <div class="h-full rounded-full transition-all duration-700" :class="theme.bar" :style="{ width: `${growth.progress}%` }" />
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                <template v-if="isMax">Fully grown — the highest stage!</template>
                <template v-else>
                    <strong class="text-slate-700 dark:text-slate-200">{{ growth.points_to_next }} more points</strong> to reach stage {{ growth.stage + 1 }}.
                </template>
            </p>
        </template>
    </div>
</template>
