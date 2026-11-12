<script setup>
import { computed } from 'vue';

/**
 * Stand-in artwork shown until the real fish/plant PNGs are uploaded to
 * public/images. Each asset number gets its own colour.
 */
const props = defineProps({
    type: { type: String, required: true },
    seed: { type: Number, default: 1 },
});

const hue = computed(() => (props.seed * 47) % 360);
const main = computed(() => `hsl(${hue.value} 85% 58%)`);
const dark = computed(() => `hsl(${hue.value} 70% 40%)`);
const light = computed(() => `hsl(${(hue.value + 30) % 360} 95% 75%)`);
const petal = computed(() => `hsl(${hue.value} 90% 65%)`);
</script>

<template>
    <svg
        v-if="type === 'fish'"
        viewBox="0 0 120 80"
        class="h-full w-full"
        aria-hidden="true"
    >
        <g transform="translate(120 0) scale(-1 1)">
        <path :fill="dark" d="M88 40 L118 14 L112 40 L118 66 Z" />
        <path :fill="dark" d="M48 18 Q60 0 76 16 Z" />
        <ellipse cx="54" cy="40" rx="42" ry="26" :fill="main" />
        <path :fill="light" opacity="0.7" d="M30 40 Q54 66 86 44 Q54 58 30 40 Z" />
        <path :stroke="dark" stroke-width="3" fill="none" opacity="0.5" d="M44 18 Q38 40 44 62 M58 15 Q52 40 58 65" />
        <circle cx="26" cy="34" r="7" fill="white" />
        <circle cx="24" cy="34" r="3.5" fill="#1e293b" />
        <path d="M14 46 Q18 49 22 46" stroke="#1e293b" stroke-width="2" fill="none" stroke-linecap="round" />
        </g>
    </svg>

    <svg
        v-else
        viewBox="0 0 100 120"
        class="h-full w-full"
        aria-hidden="true"
    >
        <path d="M50 118 Q48 80 50 50" stroke="#15803d" stroke-width="5" fill="none" stroke-linecap="round" />
        <path d="M50 92 Q28 82 22 64 Q42 66 50 86" fill="#22c55e" />
        <path d="M50 78 Q72 70 80 52 Q58 54 50 72" fill="#16a34a" />
        <g transform="translate(50 36)">
            <ellipse v-for="angle in [0, 60, 120, 180, 240, 300]" :key="angle" :transform="`rotate(${angle}) translate(0 -15)`" rx="10" ry="15" :fill="petal" />
            <circle r="10" :fill="light" />
            <circle r="5" :fill="dark" opacity="0.6" />
        </g>
    </svg>
</template>
