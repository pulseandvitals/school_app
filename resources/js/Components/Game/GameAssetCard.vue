<script setup>
import { Check } from 'lucide-vue-next';
import { computed } from 'vue';
import GameAssetImage from './GameAssetImage.vue';
import { themeFor } from '@/lib/habitat';

const props = defineProps({
    asset: { type: Object, required: true },
    type: { type: String, required: true },
    selected: { type: Boolean, default: false },
});

defineEmits(['select']);

const habitatType = computed(() => (props.type === 'fish' ? 'aquarium' : 'garden'));
const theme = computed(() => themeFor(habitatType.value));
</script>

<template>
    <button
        type="button"
        role="radio"
        :aria-checked="selected"
        :aria-label="asset.name"
        class="group relative flex flex-col items-center gap-1 rounded-2xl border-2 p-2 transition duration-200 focus:outline-none focus-visible:ring-4"
        :class="[
            selected
                ? ['scale-[1.03] shadow-lg', type === 'fish' ? 'border-cyan-500 bg-cyan-50 focus-visible:ring-cyan-200 dark:bg-cyan-950/50' : 'border-emerald-500 bg-emerald-50 focus-visible:ring-emerald-200 dark:bg-emerald-950/50']
                : 'border-transparent bg-white dark:bg-slate-900 shadow-sm hover:-translate-y-0.5 hover:shadow-md focus-visible:ring-slate-200',
        ]"
        @click="$emit('select', asset)"
    >
        <div
            class="flex aspect-square w-full items-center justify-center rounded-xl p-2"
            :class="type === 'fish' ? 'bg-gradient-to-b from-sky-100 to-cyan-200' : 'bg-gradient-to-b from-lime-50 to-emerald-100'"
        >
            <div class="h-full w-full transition duration-300 group-hover:scale-110" :class="type === 'fish' ? 'group-hover:animate-bob' : 'origin-bottom group-hover:animate-sway'">
                <GameAssetImage :asset="asset" :type="type" />
            </div>
        </div>
        <span class="truncate text-xs font-semibold text-slate-700 dark:text-slate-200">{{ asset.name }}</span>
        <span
            v-if="selected"
            class="absolute -right-1.5 -top-1.5 flex h-6 w-6 items-center justify-center rounded-full text-white shadow"
            :class="theme.accent"
        >
            <Check class="h-4 w-4" stroke-width="3" />
        </span>
        <span v-if="asset.is_active === false" class="absolute left-1.5 top-1.5 rounded-full bg-slate-700/80 px-1.5 text-[10px] font-semibold text-white">Retired</span>
    </button>
</template>
