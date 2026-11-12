<script setup>
import GameAssetCard from './GameAssetCard.vue';

/**
 * Shared fish/plant picker. `type` is the asset collection ('fish' | 'plant');
 * `assets` comes from the game_assets table via the controller.
 */
const props = defineProps({
    assets: { type: Array, required: true },
    type: { type: String, required: true },
    label: { type: String, default: '' },
});

const model = defineModel({ type: [Number, null], default: null });

function select(asset) {
    model.value = asset.id;
}

function onKeydown(event) {
    const index = props.assets.findIndex((asset) => asset.id === model.value);
    const step = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[event.key];
    if (!step) return;
    event.preventDefault();
    const next = props.assets[(index + step + props.assets.length) % props.assets.length];
    model.value = next.id;
}
</script>

<template>
    <div>
        <div
            role="radiogroup"
            :aria-label="label || `Choose a ${type}`"
            class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6"
            @keydown="onKeydown"
        >
            <GameAssetCard
                v-for="asset in assets"
                :key="asset.id"
                :asset="asset"
                :type="type"
                :selected="asset.id === model"
                @select="select"
            />
        </div>
        <p v-if="!assets.length" class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-6 text-center text-sm text-slate-500 dark:text-slate-400">
            No {{ type === 'fish' ? 'fish' : 'plants' }} are available right now.
        </p>
    </div>
</template>
