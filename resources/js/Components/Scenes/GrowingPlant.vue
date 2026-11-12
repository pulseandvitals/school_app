<script setup>
import { computed } from 'vue';
import GameAssetImage from '@/Components/Game/GameAssetImage.vue';
import { stageEffects } from '@/lib/habitat';

/**
 * One student's plant on its soil plot. Grows from the soil line upwards;
 * the first stage shows a seed mound with a tiny sprout of the chosen plant.
 */
const props = defineProps({
    member: { type: Object, required: true },
    selected: { type: Boolean, default: false },
    showName: { type: Boolean, default: true },
    swayDelay: { type: Number, default: 0 },
});

defineEmits(['select']);

const effects = computed(() => stageEffects(props.member.growth.stage, props.member.growth.max_stage));
const isSeed = computed(() => props.member.growth.stage === 1);
</script>

<template>
    <button
        type="button"
        class="group relative flex w-full flex-col items-center outline-none"
        :aria-label="`${member.student.name}, stage ${member.growth.stage} ${member.growth.name}`"
        :aria-pressed="selected"
        @click.stop="$emit('select', member)"
    >
        <!-- Plant -->
        <div class="relative flex h-[var(--plant-height)] w-full items-end justify-center">
            <div
                v-if="effects.glow"
                class="absolute bottom-2 left-1/2 h-2/3 w-2/3 -translate-x-1/2 rounded-full blur-2xl"
                :class="effects.sparkle ? 'bg-yellow-200/70' : 'bg-white/50'"
            />
            <div
                class="relative w-full origin-bottom transition-transform duration-1000 ease-out group-hover:-translate-y-1"
                :style="{ transform: `scale(${member.growth.scale})` }"
            >
                <div
                    class="aspect-[4/5] w-full origin-bottom animate-sway"
                    :style="{ animationDelay: `${swayDelay}s`, filter: effects.filter }"
                >
                    <GameAssetImage :asset="member.asset" type="plant" :stage="member.growth.stage" />
                </div>
            </div>

            <template v-if="effects.sparkle">
                <span class="absolute left-[18%] top-[12%] h-2 w-2 animate-twinkle rounded-full bg-yellow-100 shadow-[0_0_10px_3px_rgba(254,240,138,0.9)]" />
                <span class="absolute right-[16%] top-[30%] h-1.5 w-1.5 animate-twinkle rounded-full bg-white shadow-[0_0_8px_2px_rgba(255,255,255,0.9)]" style="animation-delay: 0.8s" />
                <span class="absolute left-[30%] top-[45%] h-1.5 w-1.5 animate-twinkle rounded-full bg-pink-100 shadow-[0_0_8px_2px_rgba(251,207,232,0.9)]" style="animation-delay: 1.5s" />
            </template>
        </div>

        <!-- Soil mound -->
        <svg class="-mt-2 h-5 w-[85%]" viewBox="0 0 100 24" preserveAspectRatio="none" aria-hidden="true">
            <ellipse cx="50" cy="16" rx="48" ry="8" fill="#5b3a1e" />
            <ellipse cx="50" cy="12" rx="40" ry="7" fill="#7c4a24" />
            <template v-if="isSeed">
                <ellipse cx="44" cy="9" rx="4" ry="3" fill="#a16207" />
                <ellipse cx="57" cy="10" rx="3" ry="2.2" fill="#ca8a04" />
            </template>
        </svg>

        <span
            v-show="showName || selected"
            class="mt-1 max-w-full truncate rounded-full px-2 py-0.5 text-[11px] font-bold shadow-sm transition"
            :class="selected ? 'bg-yellow-300 text-slate-900' : 'bg-white/85 text-slate-800 group-hover:bg-white group-focus-visible:bg-white'"
        >
            {{ member.student.name }}
        </span>

        <span
            v-if="selected"
            class="pointer-events-none absolute -inset-1 rounded-3xl ring-4 ring-yellow-300/80"
        />
    </button>
</template>
