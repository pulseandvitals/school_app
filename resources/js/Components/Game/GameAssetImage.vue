<script setup>
import { computed, ref, watch } from 'vue';
import PlaceholderArt from './PlaceholderArt.vue';

/**
 * Renders a fish/plant image, preferring a stage-specific image when one is
 * configured, and falling back to placeholder art if the file is missing.
 */
const props = defineProps({
    asset: { type: Object, required: true },
    type: { type: String, required: true },
    stage: { type: Number, default: null },
    alt: { type: String, default: '' },
});

const failed = ref(false);

const src = computed(() => {
    const stageImage = props.stage ? props.asset.stage_image_urls?.[props.stage] : null;
    return stageImage || props.asset.image_url;
});

watch(src, () => {
    failed.value = false;
});

const seed = computed(() => props.asset.asset_number ?? props.asset.id ?? 1);
</script>

<template>
    <div class="relative h-full w-full">
        <img
            v-if="!failed"
            :src="src"
            :alt="alt || asset.name"
            class="h-full w-full object-contain"
            draggable="false"
            loading="lazy"
            @error="failed = true"
        />
        <PlaceholderArt v-else :type="type" :seed="seed" />
    </div>
</template>
