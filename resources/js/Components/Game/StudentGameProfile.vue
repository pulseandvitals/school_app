<script setup>
import { computed } from 'vue';
import GameAssetImage from './GameAssetImage.vue';
import GrowthStageDisplay from './GrowthStageDisplay.vue';
import StudentAvatar from '@/Components/StudentAvatar.vue';
import { formatPercent, stageEffects } from '@/lib/habitat';

/**
 * Student card with their fish/plant — used in scene popovers and reports.
 */
const props = defineProps({
    member: { type: Object, required: true },
    habitatType: { type: String, required: true },
    size: { type: String, default: 'md' },
});

const assetType = computed(() => (props.habitatType === 'aquarium' ? 'fish' : 'plant'));
const effects = computed(() => stageEffects(props.member.growth.stage, props.member.growth.max_stage));
</script>

<template>
    <div class="flex flex-col gap-4" :class="size === 'lg' ? 'sm:flex-row sm:items-center' : ''">
        <div
            class="relative mx-auto flex shrink-0 items-center justify-center overflow-hidden rounded-2xl"
            :class="[
                size === 'lg' ? 'h-40 w-40' : 'h-24 w-full',
                habitatType === 'aquarium' ? 'bg-gradient-to-b from-sky-200 to-cyan-400' : 'bg-gradient-to-b from-sky-100 via-lime-100 to-emerald-200',
            ]"
        >
            <div
                class="h-3/4 w-3/4"
                :class="habitatType === 'aquarium' ? 'animate-bob' : 'origin-bottom animate-sway'"
                :style="{ filter: effects.filter }"
            >
                <GameAssetImage :asset="member.asset" :type="assetType" :stage="member.growth.stage" />
            </div>
            <span v-if="effects.sparkle" class="absolute right-3 top-3 h-2 w-2 animate-twinkle rounded-full bg-yellow-200 shadow-[0_0_12px_4px_rgba(253,224,71,0.8)]" />
        </div>

        <div class="min-w-0 flex-1 space-y-3">
            <div class="flex items-center gap-3">
                <StudentAvatar :name="member.student.name" :photo-url="member.student.photo_url" />
                <div class="min-w-0">
                    <p class="truncate font-display text-lg font-semibold text-slate-800 dark:text-slate-100">{{ member.student.name }}</p>
                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                        {{ member.asset.name }}<template v-if="member.student.student_number"> · #{{ member.student.student_number }}</template>
                    </p>
                </div>
            </div>

            <GrowthStageDisplay :growth="member.growth" :points="member.growth_points" :habitat-type="habitatType" />

            <dl v-if="member.scores_count !== undefined" class="grid grid-cols-3 gap-2 text-center">
                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-2">
                    <dt class="text-[11px] font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">Average</dt>
                    <dd class="font-display text-base font-semibold text-slate-700 dark:text-slate-200">{{ formatPercent(member.average_percentage) }}</dd>
                </div>
                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-2">
                    <dt class="text-[11px] font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">Assessments</dt>
                    <dd class="font-display text-base font-semibold text-slate-700 dark:text-slate-200">{{ member.scores_count }}</dd>
                </div>
                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-2">
                    <dt class="text-[11px] font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">Badges</dt>
                    <dd class="font-display text-base font-semibold text-slate-700 dark:text-slate-200">{{ member.achievements_count ?? 0 }}</dd>
                </div>
            </dl>

            <slot />
        </div>
    </div>
</template>
