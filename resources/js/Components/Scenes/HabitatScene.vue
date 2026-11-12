<script setup>
import { computed } from 'vue';
import AquariumScene from './AquariumScene.vue';
import GardenScene from './GardenScene.vue';

/**
 * Picks the scene component for a habitat type. Register new game
 * environments here.
 */
const props = defineProps({
    type: { type: String, required: true },
});

const scenes = {
    aquarium: AquariumScene,
    garden: GardenScene,
};

const scene = computed(() => scenes[props.type] ?? AquariumScene);
</script>

<template>
    <component :is="scene">
        <slot />
    </component>
</template>
