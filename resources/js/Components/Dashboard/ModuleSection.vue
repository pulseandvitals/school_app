<script setup>
import { Link } from '@inertiajs/vue3';
import { CircleAlert, Fish, Flower2, Trophy } from 'lucide-vue-next';
import { computed } from 'vue';
import GameAssetImage from '@/Components/Game/GameAssetImage.vue';
import { formatPercent, habitatMeta, themeFor } from '@/lib/habitat';

const props = defineProps({
    type: { type: String, required: true },
    section: { type: Object, required: true },
});

const meta = computed(() => habitatMeta(props.type));
const theme = computed(() => themeFor(props.type));
const assetType = computed(() => (props.type === 'aquarium' ? 'fish' : 'plant'));

function memberHref(item) {
    return route(`${item.route}.members.show`, { habitat: item.habitat_id, member: item.id });
}
</script>

<template>
    <section class="overflow-hidden rounded-3xl bg-white dark:bg-slate-900 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800">
        <div class="flex items-center justify-between bg-gradient-to-r px-6 py-4 text-white" :class="theme.gradient">
            <h2 class="flex items-center gap-2 font-display text-xl font-semibold">
                <component :is="type === 'aquarium' ? Fish : Flower2" class="h-5 w-5" />
                {{ meta.label }}
            </h2>
            <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-bold">Average {{ formatPercent(section.average) }}</span>
        </div>

        <div class="grid gap-6 p-6 sm:grid-cols-2">
            <div>
                <h3 class="flex items-center gap-1.5 text-sm font-bold text-slate-700 dark:text-slate-200">
                    <Trophy class="h-4 w-4 text-amber-500" /> Highest growth
                </h3>
                <ul v-if="section.topGrowers.length" class="mt-3 space-y-1">
                    <li v-for="(item, index) in section.topGrowers" :key="item.id">
                        <Link :href="memberHref(item)" class="flex items-center gap-3 rounded-xl p-1.5 transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
                            <span class="w-4 text-center text-xs font-bold text-slate-400 dark:text-slate-500">{{ index + 1 }}</span>
                            <span class="h-8 w-8 shrink-0"><GameAssetImage :asset="{ image_url: item.image_url, id: item.id }" :type="assetType" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ item.name }}</span>
                                <span class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ item.stage_name }} · {{ item.habitat_name }}</span>
                            </span>
                            <span class="text-xs font-bold" :class="theme.text">{{ item.points }} pts</span>
                        </Link>
                    </li>
                </ul>
                <p v-else class="mt-3 text-sm text-slate-400 dark:text-slate-500">No students yet.</p>
            </div>

            <div>
                <h3 class="flex items-center gap-1.5 text-sm font-bold text-slate-700 dark:text-slate-200">
                    <CircleAlert class="h-4 w-4 text-rose-500" /> Needs a little help
                </h3>
                <ul v-if="section.needsImprovement.length" class="mt-3 space-y-1">
                    <li v-for="item in section.needsImprovement" :key="item.id">
                        <Link :href="memberHref(item)" class="flex items-center gap-3 rounded-xl p-1.5 transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
                            <span class="h-8 w-8 shrink-0"><GameAssetImage :asset="{ image_url: item.image_url, id: item.id }" :type="assetType" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ item.name }}</span>
                                <span class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ item.habitat_name }}</span>
                            </span>
                            <span class="rounded-full bg-rose-50 dark:bg-rose-950/40 px-2 py-0.5 text-xs font-bold text-rose-700 dark:text-rose-300">{{ formatPercent(item.average) }}</span>
                        </Link>
                    </li>
                </ul>
                <p v-else class="mt-3 text-sm text-slate-400 dark:text-slate-500">Everyone is averaging 60% or more. Great work!</p>
            </div>
        </div>

        <div v-if="section.habitats.length" class="flex flex-wrap gap-2 border-t border-slate-100 dark:border-slate-800 px-6 py-4">
            <Link
                v-for="habitat in section.habitats"
                :key="habitat.id"
                :href="route(`${habitat.route}.show`, habitat.id)"
                class="rounded-full px-3 py-1.5 text-xs font-bold transition hover:opacity-80"
                :class="theme.chip"
            >
                {{ habitat.name }} · {{ habitat.members_count }}
            </Link>
        </div>
    </section>
</template>
