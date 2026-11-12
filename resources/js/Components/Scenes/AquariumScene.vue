<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import SwimmingFish from './SwimmingFish.vue';

/**
 * Interactive aquarium: every member is a fish that wanders around the tank.
 * Movement runs in a single requestAnimationFrame loop that writes transforms
 * directly to the DOM (no reactive updates per frame).
 */
const props = defineProps({
    members: { type: Array, required: true },
    theme: { type: String, default: 'coral-reef' },
    selectedId: { type: Number, default: null },
    showNames: { type: Boolean, default: true },
});

const emit = defineEmits(['select']);

/** Direction the source fish images face. Flip to 'left' if your PNGs face left. */
const IMAGES_FACE = 'right';

const themes = {
    'coral-reef': { water: 'from-sky-300 via-cyan-500 to-blue-700', sand: '#f6d8a8', sandDark: '#e9bf80', plant: '#14b8a6', coral: ['#fb7185', '#f97316', '#e879f9'] },
    'deep-ocean': { water: 'from-blue-600 via-indigo-800 to-slate-950', sand: '#475569', sandDark: '#334155', plant: '#0e7490', coral: ['#6366f1', '#22d3ee', '#a78bfa'] },
    'sunny-lagoon': { water: 'from-cyan-100 via-teal-300 to-teal-600', sand: '#fde7b0', sandDark: '#f5d38a', plant: '#10b981', coral: ['#fda4af', '#fcd34d', '#f472b6'] },
    'kelp-forest': { water: 'from-teal-300 via-emerald-700 to-slate-900', sand: '#a8a29e', sandDark: '#78716c', plant: '#65a30d', coral: ['#84cc16', '#facc15', '#22c55e'] },
};
const palette = computed(() => themes[props.theme] ?? themes['coral-reef']);

const container = ref(null);
const fishRefs = new Map();
const states = new Map();
const hovered = new Set();
let width = 0;
let height = 0;
let frame = null;
let lastTime = 0;
let resizeObserver = null;
const reducedMotion = typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

const random = (min, max) => min + Math.random() * (max - min);

/** Swim zone in % of the tank, leaving room for the sand and name labels. */
const ZONE = { minX: 8, maxX: 92, minY: 10, maxY: 70 };

function newTarget(state) {
    state.tx = random(ZONE.minX, ZONE.maxX);
    state.ty = random(ZONE.minY, ZONE.maxY);
    state.speed = random(0.012, 0.03) * (1.15 - Math.min(state.scale, 1.3) * 0.25);
}

function ensureStates() {
    const ids = new Set(props.members.map((member) => member.id));
    for (const id of states.keys()) {
        if (!ids.has(id)) states.delete(id);
    }
    props.members.forEach((member, index) => {
        if (states.has(member.id)) {
            states.get(member.id).scale = member.growth.scale;
            return;
        }
        const state = {
            x: reducedMotion ? ZONE.minX + ((index * 37) % (ZONE.maxX - ZONE.minX)) : random(ZONE.minX, ZONE.maxX),
            y: reducedMotion ? ZONE.minY + ((index * 23) % (ZONE.maxY - ZONE.minY)) : random(ZONE.minY, ZONE.maxY),
            facing: Math.random() > 0.5 ? 1 : -1,
            scale: member.growth.scale,
        };
        newTarget(state);
        states.set(member.id, state);
    });
}

function paint(id, state) {
    const el = fishRefs.get(id);
    if (!el) return;
    el.style.transform = `translate(${(state.x / 100) * width}px, ${(state.y / 100) * height}px) translate(-50%, -50%)`;
    el.style.zIndex = String(10 + Math.round(state.y));
    const flip = el.querySelector('[data-flip]');
    if (flip) {
        const mirrored = IMAGES_FACE === 'right' ? state.facing < 0 : state.facing > 0;
        flip.style.transform = mirrored ? 'scaleX(-1)' : 'scaleX(1)';
    }
}

function tick(time) {
    const delta = Math.min(time - (lastTime || time), 64) / 16.67;
    lastTime = time;

    for (const [id, state] of states) {
        if (!hovered.has(id) && id !== props.selectedId) {
            const dx = state.tx - state.x;
            const dy = state.ty - state.y;
            const distance = Math.hypot(dx, dy);
            if (distance < 1.5) {
                newTarget(state);
            } else {
                const step = Math.min(state.speed * delta * 10, distance);
                state.x += (dx / distance) * step;
                state.y += (dy / distance) * step * 0.6;
                if (Math.abs(dx) > 2) state.facing = dx > 0 ? 1 : -1;
            }
        }
        paint(id, state);
    }

    frame = requestAnimationFrame(tick);
}

function measure() {
    if (!container.value) return;
    width = container.value.clientWidth;
    height = container.value.clientHeight;
    container.value.style.setProperty('--fish-base', `${Math.max(56, Math.min(width * 0.1, 130))}px`);
    for (const [id, state] of states) paint(id, state);
}

function setFishRef(id, el) {
    if (el) fishRefs.set(id, el.$el ?? el);
    else fishRefs.delete(id);
}

function onHover(member, isHovering) {
    if (isHovering) hovered.add(member.id);
    else hovered.delete(member.id);
}

watch(() => props.members, () => {
    ensureStates();
    requestAnimationFrame(measure);
}, { deep: false });

onMounted(() => {
    ensureStates();
    measure();
    resizeObserver = new ResizeObserver(measure);
    resizeObserver.observe(container.value);
    if (!reducedMotion) frame = requestAnimationFrame(tick);
});

onBeforeUnmount(() => {
    if (frame) cancelAnimationFrame(frame);
    resizeObserver?.disconnect();
});

const bubbles = Array.from({ length: 16 }, (_, i) => ({
    left: `${(i * 61) % 100}%`,
    size: 4 + ((i * 7) % 10),
    delay: (i * 1.3) % 9,
    duration: 7 + ((i * 3) % 7),
}));

const seaweed = [
    { left: '4%', height: 38, delay: 0 },
    { left: '9%', height: 26, delay: 1.2 },
    { left: '27%', height: 20, delay: 0.6 },
    { left: '63%', height: 30, delay: 2.1 },
    { left: '88%', height: 42, delay: 0.4 },
    { left: '94%', height: 24, delay: 1.7 },
];
</script>

<template>
    <div
        ref="container"
        class="relative isolate overflow-hidden bg-gradient-to-b"
        :class="palette.water"
        @click="emit('select', null)"
    >
        <!-- Light rays -->
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div
                v-for="n in 5"
                :key="`ray-${n}`"
                class="absolute -top-10 h-[120%] w-16 origin-top animate-light-ray bg-gradient-to-b from-white/50 to-transparent blur-md"
                :style="{ left: `${n * 18 - 6}%`, transform: `rotate(${12 - n * 3}deg)`, animationDelay: `${n * 1.3}s` }"
            />
        </div>

        <!-- Bubbles -->
        <div class="pointer-events-none absolute inset-x-0 bottom-0 top-0 -z-10">
            <span
                v-for="(bubble, i) in bubbles"
                :key="`bubble-${i}`"
                class="absolute bottom-0 animate-bubble-rise rounded-full border border-white/70 bg-white/20"
                :style="{ left: bubble.left, width: `${bubble.size}px`, height: `${bubble.size}px`, animationDelay: `${bubble.delay}s`, animationDuration: `${bubble.duration}s` }"
            />
        </div>

        <!-- Sea floor -->
        <svg class="pointer-events-none absolute inset-x-0 bottom-0 -z-10 h-[22%] w-full" viewBox="0 0 400 80" preserveAspectRatio="none" aria-hidden="true">
            <path :fill="palette.sandDark" d="M0 30 Q50 18 100 28 T200 26 T300 30 T400 22 V80 H0 Z" />
            <path :fill="palette.sand" d="M0 42 Q60 30 120 40 T240 38 T400 36 V80 H0 Z" />
            <ellipse cx="70" cy="44" rx="18" ry="9" fill="#94a3b8" />
            <ellipse cx="84" cy="47" rx="10" ry="6" fill="#64748b" />
            <ellipse cx="320" cy="42" rx="14" ry="7" fill="#94a3b8" />
            <path :fill="palette.coral[0]" d="M150 46 q-4 -18 2 -26 q4 10 2 14 q6 -14 12 -16 q-2 14 -8 22 q8 -6 12 -4 q-6 8 -14 12 z" />
            <path :fill="palette.coral[1]" d="M250 44 q-2 -14 6 -20 q0 10 -2 12 q8 -10 12 -8 q-4 10 -10 14 z" />
            <circle cx="210" cy="44" r="5" :fill="palette.coral[2]" />
            <circle cx="216" cy="40" r="3.5" :fill="palette.coral[2]" />
        </svg>

        <!-- Seaweed -->
        <div class="pointer-events-none absolute inset-x-0 bottom-0 -z-10 h-full">
            <svg
                v-for="(weed, i) in seaweed"
                :key="`weed-${i}`"
                class="absolute bottom-[6%] w-6 origin-bottom animate-sway-slow"
                :style="{ left: weed.left, height: `${weed.height}%`, animationDelay: `${weed.delay}s` }"
                viewBox="0 0 20 100"
                preserveAspectRatio="none"
                aria-hidden="true"
            >
                <path :fill="palette.plant" opacity="0.85" d="M10 100 C2 80 18 65 8 45 C0 28 16 15 10 0 C20 15 12 30 16 45 C22 65 8 80 14 100 Z" />
            </svg>
        </div>

        <!-- Fish -->
        <SwimmingFish
            v-for="(member, i) in members"
            :key="member.id"
            :ref="(el) => setFishRef(member.id, el)"
            :member="member"
            :selected="member.id === selectedId"
            :show-name="showNames"
            :bob-delay="(i * 0.37) % 3"
            @select="emit('select', $event)"
            @hover="onHover"
        />

        <slot />
    </div>
</template>
