<script setup>
import { computed } from 'vue';
import GameAssetImage from '@/Components/Game/GameAssetImage.vue';
import { stageEffects } from '@/lib/habitat';

/**
 * One student's fish. Position and facing are driven by the parent scene's
 * animation loop through the root element's transform.
 */
const props = defineProps({
    member: { type: Object, required: true },
    selected: { type: Boolean, default: false },
    showName: { type: Boolean, default: true },
    bobDelay: { type: Number, default: 0 },
});

defineEmits(['select', 'hover']);

const effects = computed(() => stageEffects(props.member.growth.stage, props.member.growth.max_stage));
const size = computed(() => `calc(var(--fish-base) * ${props.member.growth.scale})`);
</script>

<template>
    <button
        type="button"
        class="fish group absolute left-0 top-0 flex flex-col items-center outline-none will-change-transform"
        :style="{ width: size }"
        :aria-label="`${member.student.name}, stage ${member.growth.stage} ${member.growth.name}`"
        :aria-pressed="selected"
        @click.stop="$emit('select', member)"
        @mouseenter="$emit('hover', member, true)"
        @mouseleave="$emit('hover', member, false)"
        @focus="$emit('hover', member, true)"
        @blur="$emit('hover', member, false)"
    >
        <div class="w-full animate-bob" :style="{ animationDelay: `${bobDelay}s` }">
            <div
                data-flip
                class="relative aspect-[3/2] w-full transition-transform duration-500"
                :style="{ filter: effects.filter }"
            >
                <div
                    v-if="effects.glow"
                    class="absolute inset-0 -z-10 rounded-full blur-xl"
                    :class="effects.sparkle ? 'bg-yellow-200/60' : 'bg-white/30'"
                />
                <GameAssetImage :asset="member.asset" type="fish" :stage="member.growth.stage" />
                <span
                    v-if="effects.sparkle"
                    class="absolute -right-1 top-0 h-2 w-2 animate-twinkle rounded-full bg-yellow-100 shadow-[0_0_10px_3px_rgba(254,240,138,0.9)]"
                />
                <span
                    v-if="effects.sparkle"
                    class="absolute bottom-1 left-2 h-1.5 w-1.5 animate-twinkle rounded-full bg-white shadow-[0_0_8px_2px_rgba(255,255,255,0.9)]"
                    style="animation-delay: 1.1s"
                />
            </div>
        </div>
        <span
            v-show="showName || selected"
            class="mt-0.5 max-w-[9rem] truncate rounded-full px-2 py-0.5 text-[11px] font-bold shadow-sm backdrop-blur transition"
            :class="selected ? 'bg-yellow-300 text-slate-900' : 'bg-white/80 text-slate-800 group-hover:bg-white group-focus-visible:bg-white'"
        >
            {{ member.student.name }}
        </span>
        <span
            v-if="selected"
            class="pointer-events-none absolute -inset-2 -z-10 rounded-full ring-4 ring-yellow-300/70"
        />
    </button>
</template>
