<script setup>
import { computed } from 'vue';
import GrowingPlant from './GrowingPlant.vue';

/**
 * Interactive garden: each member is a plant growing on its own plot.
 */
const props = defineProps({
    members: { type: Array, required: true },
    theme: { type: String, default: 'sunny-meadow' },
    selectedId: { type: Number, default: null },
    showNames: { type: Boolean, default: true },
});

const emit = defineEmits(['select']);

const themes = {
    'sunny-meadow': { sky: 'from-sky-400 via-sky-200 to-lime-100', sun: '#fde047', hillBack: '#86efac', hillFront: '#4ade80', grass: 'from-lime-400 to-green-600', tree: '#16a34a', petals: false },
    'spring-blossom': { sky: 'from-pink-300 via-rose-100 to-lime-50', sun: '#fef9c3', hillBack: '#bbf7d0', hillFront: '#86efac', grass: 'from-lime-300 to-green-500', tree: '#f9a8d4', petals: true },
    'golden-sunset': { sky: 'from-orange-400 via-amber-200 to-yellow-50', sun: '#fb923c', hillBack: '#bef264', hillFront: '#84cc16', grass: 'from-lime-400 to-green-700', tree: '#15803d', petals: false },
    'zen-garden': { sky: 'from-teal-300 via-emerald-100 to-stone-100', sun: '#fef3c7', hillBack: '#a7f3d0', hillFront: '#6ee7b7', grass: 'from-emerald-300 to-emerald-600', tree: '#0f766e', petals: false },
};
const palette = computed(() => themes[props.theme] ?? themes['sunny-meadow']);

const clouds = [
    { top: '8%', scale: 1, delay: -5, duration: 70 },
    { top: '18%', scale: 0.7, delay: -35, duration: 90 },
    { top: '4%', scale: 0.55, delay: -60, duration: 110 },
];

const butterflies = [
    { left: '12%', top: '26%', hue: 320, delay: 0 },
    { left: '58%', top: '18%', hue: 45, delay: 1.8 },
    { left: '80%', top: '34%', hue: 200, delay: 3.1 },
];

const petals = Array.from({ length: 12 }, (_, i) => ({
    left: `${(i * 41) % 100}%`,
    delay: (i * 0.9) % 8,
    duration: 6 + (i % 5),
}));

const columns = computed(() => {
    const count = props.members.length;
    if (count <= 4) return 'grid-cols-2 sm:grid-cols-4';
    if (count <= 10) return 'grid-cols-3 sm:grid-cols-5';
    return 'grid-cols-3 sm:grid-cols-5 lg:grid-cols-7';
});
</script>

<template>
    <div class="relative isolate flex flex-col overflow-hidden bg-gradient-to-b" :class="palette.sky" @click="emit('select', null)">
        <!-- Sky -->
        <div class="pointer-events-none relative h-40 shrink-0 sm:h-48">
            <div class="absolute right-[8%] top-6 h-16 w-16 rounded-full sm:h-20 sm:w-20" :style="{ backgroundColor: palette.sun, boxShadow: `0 0 60px 20px ${palette.sun}88` }" />

            <svg
                v-for="(cloud, i) in clouds"
                :key="`cloud-${i}`"
                class="absolute left-0 w-32 animate-drift opacity-90"
                :style="{ top: cloud.top, animationDelay: `${cloud.delay}s`, animationDuration: `${cloud.duration}s`, scale: cloud.scale }"
                viewBox="0 0 120 50"
                aria-hidden="true"
            >
                <path fill="white" d="M20 45 Q0 45 6 32 Q8 20 24 24 Q28 6 50 10 Q66 0 78 16 Q100 10 104 28 Q120 32 110 45 Z" />
            </svg>

            <svg class="absolute inset-x-0 bottom-0 h-24 w-full" viewBox="0 0 400 100" preserveAspectRatio="none" aria-hidden="true">
                <path :fill="palette.hillBack" d="M0 70 Q80 20 170 60 T400 40 V100 H0 Z" />
                <path :fill="palette.hillFront" d="M0 85 Q120 50 230 80 T400 70 V100 H0 Z" />
            </svg>

            <!-- Trees -->
            <svg class="absolute bottom-2 left-[3%] h-28 w-20" viewBox="0 0 60 90" aria-hidden="true">
                <rect x="26" y="50" width="8" height="40" rx="2" fill="#78350f" />
                <circle cx="30" cy="36" r="24" :fill="palette.tree" />
                <circle cx="18" cy="46" r="14" :fill="palette.tree" />
                <circle cx="42" cy="46" r="14" :fill="palette.tree" />
            </svg>
            <svg class="absolute bottom-2 right-[4%] hidden h-24 w-16 sm:block" viewBox="0 0 60 90" aria-hidden="true">
                <rect x="26" y="50" width="8" height="40" rx="2" fill="#78350f" />
                <path :fill="palette.tree" d="M30 4 L54 56 H6 Z" />
                <path :fill="palette.tree" d="M30 20 L58 70 H2 Z" opacity="0.9" />
            </svg>

            <!-- Butterflies -->
            <svg
                v-for="(fly, i) in butterflies"
                :key="`fly-${i}`"
                class="absolute h-5 w-5 animate-flutter"
                :style="{ left: fly.left, top: fly.top, animationDelay: `${fly.delay}s` }"
                viewBox="0 0 20 20"
                aria-hidden="true"
            >
                <ellipse cx="6" cy="7" rx="5" ry="6" :fill="`hsl(${fly.hue} 85% 65%)`" />
                <ellipse cx="14" cy="7" rx="5" ry="6" :fill="`hsl(${fly.hue} 85% 65%)`" />
                <ellipse cx="7" cy="14" rx="3.5" ry="4" :fill="`hsl(${fly.hue} 80% 55%)`" />
                <ellipse cx="13" cy="14" rx="3.5" ry="4" :fill="`hsl(${fly.hue} 80% 55%)`" />
                <rect x="9.3" y="4" width="1.4" height="13" rx="0.7" fill="#334155" />
            </svg>
        </div>

        <!-- Falling petals -->
        <div v-if="palette.petals" class="pointer-events-none absolute inset-0 z-20">
            <span
                v-for="(petal, i) in petals"
                :key="`petal-${i}`"
                class="absolute top-0 h-2.5 w-2 animate-fall rounded-full bg-pink-200"
                :style="{ left: petal.left, animationDelay: `${petal.delay}s`, animationDuration: `${petal.duration}s`, animationIterationCount: 'infinite' }"
            />
        </div>

        <!-- Ground with plots -->
        <div class="relative flex-1 bg-gradient-to-b px-4 pb-8 pt-4 sm:px-8" :class="palette.grass">
            <!-- Fence -->
            <div class="pointer-events-none absolute inset-x-0 -top-6 flex h-8 items-end justify-between px-2 opacity-80" aria-hidden="true">
                <span v-for="n in 24" :key="`post-${n}`" class="h-6 w-1.5 rounded-t-full bg-amber-100" />
                <span class="absolute inset-x-0 bottom-3 h-1 bg-amber-100" />
            </div>

            <div class="grid gap-x-4 gap-y-6 [--plant-height:7rem] sm:[--plant-height:9rem]" :class="columns">
                <GrowingPlant
                    v-for="(member, i) in members"
                    :key="member.id"
                    :member="member"
                    :selected="member.id === selectedId"
                    :show-name="showNames"
                    :sway-delay="(i * 0.53) % 4"
                    @select="emit('select', $event)"
                />
            </div>

            <slot />
        </div>
    </div>
</template>
