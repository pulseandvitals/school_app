<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: { type: String, required: true },
    photoUrl: { type: String, default: null },
    size: { type: String, default: 'md' },
});

const initials = computed(() =>
    props.name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join(''),
);

const hue = computed(() => [...props.name].reduce((sum, char) => sum + char.charCodeAt(0), 0) % 360);

const sizes = { sm: 'h-8 w-8 text-xs', md: 'h-10 w-10 text-sm', lg: 'h-16 w-16 text-xl' };
</script>

<template>
    <img
        v-if="photoUrl"
        :src="photoUrl"
        :alt="name"
        class="shrink-0 rounded-full object-cover ring-2 ring-white"
        :class="sizes[size]"
    />
    <span
        v-else
        class="inline-flex shrink-0 items-center justify-center rounded-full font-bold text-white ring-2 ring-white"
        :class="sizes[size]"
        :style="{ backgroundColor: `hsl(${hue} 65% 55%)` }"
        aria-hidden="true"
    >
        {{ initials }}
    </span>
</template>
