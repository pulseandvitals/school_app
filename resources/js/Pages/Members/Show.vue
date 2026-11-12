<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ClipboardCheck, Fish, Flower2, Gauge, History, Lock, Pencil, Trash2, TrendingDown, TrendingUp } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import StudentGameProfile from '@/Components/Game/StudentGameProfile.vue';
import { formatDate, formatPercent, habitatMeta, habitatRoute, performanceFor, performanceStyles, themeFor } from '@/lib/habitat';
import { achievementIcon } from '@/lib/icons';

const props = defineProps({
    habitat: { type: Object, required: true },
    member: { type: Object, required: true },
    stats: { type: Object, required: true },
    scores: { type: Array, required: true },
    timeline: { type: Array, required: true },
    achievements: { type: Array, required: true },
    otherHabitats: { type: Array, required: true },
});

const meta = computed(() => habitatMeta(props.habitat.type));
const theme = computed(() => themeFor(props.habitat.type));
const noun = computed(() => meta.value.member_noun);
const performance = computed(() => performanceFor(props.stats.average));
const earnedCount = computed(() => props.achievements.filter((item) => item.awarded_at).length);
const pointsFromScores = computed(() => props.scores.reduce((sum, score) => sum + score.points, 0));

const confirmingRemoval = ref(false);

function remove() {
    router.delete(habitatRoute(props.habitat, 'members.destroy', { member: props.member.id }), {
        onFinish: () => (confirmingRemoval.value = false),
    });
}

const reasonLabels = {
    score_recorded: 'Score recorded',
    score_updated: 'Score corrected',
    score_removed: 'Score removed',
    rules_changed: 'Rules updated',
};
</script>

<template>
    <Head :title="member.student.name" />

    <AuthenticatedLayout :theme="habitat.type">
        <template #header>
            <PageHeader
                :title="member.student.name"
                :eyebrow="`Student report · ${habitat.name}`"
                :back-href="habitatRoute(habitat, 'show')"
                :back-label="`Back to ${habitat.name}`"
            >
                <template #actions>
                    <AppButton variant="secondary" :href="habitatRoute(habitat, 'members.edit', { member: member.id })">
                        <Pencil class="h-4 w-4" /> Edit
                    </AppButton>
                    <AppButton variant="ghost" class="text-rose-600 hover:bg-rose-50 dark:!text-rose-400 dark:hover:bg-rose-950/40 hover:text-rose-700 dark:hover:text-rose-300" @click="confirmingRemoval = true">
                        <Trash2 class="h-4 w-4" /> Remove
                    </AppButton>
                </template>
            </PageHeader>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 pt-6 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-5">
                <section class="rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800 lg:col-span-3">
                    <StudentGameProfile :member="member" :habitat-type="habitat.type" size="lg" />
                </section>

                <section class="rounded-3xl p-6 ring-1 lg:col-span-2" :class="[theme.soft, theme.softBorder]">
                    <h2 class="font-display text-lg font-semibold text-slate-800 dark:text-slate-100">Why stage {{ member.growth.stage }}?</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                        {{ member.student.name }}'s {{ noun }} has <strong>{{ member.growth_points }} growth points</strong>
                        from {{ stats.completed }} {{ stats.completed === 1 ? 'assessment' : 'assessments' }}.
                        Every assessment adds points based on its percentage, and
                        <strong>{{ member.growth.name }}</strong> is reached with enough points.
                    </p>
                    <p v-if="member.growth.points_to_next !== null" class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                        <strong>{{ member.growth.points_to_next }} more points</strong> are needed for the next stage.
                    </p>
                    <p v-else class="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-200">This {{ noun }} has reached its final stage!</p>
                    <p v-if="pointsFromScores !== member.growth_points" class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                        Totals are recalculated from scores whenever scores or rules change.
                    </p>
                    <div v-if="performance" class="mt-4">
                        <span class="rounded-full px-3 py-1 text-xs font-bold" :class="performanceStyles[performance].chip">
                            {{ performanceStyles[performance].label }} performance
                        </span>
                    </div>
                </section>
            </div>

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Assessments" :value="stats.completed" :icon="ClipboardCheck" color="from-amber-400 to-orange-500" />
                <StatCard label="Average" :value="formatPercent(stats.average)" :icon="Gauge" color="from-violet-500 to-fuchsia-500" />
                <StatCard label="Highest" :value="formatPercent(stats.highest)" :icon="TrendingUp" color="from-emerald-400 to-green-600" />
                <StatCard label="Lowest" :value="formatPercent(stats.lowest)" :icon="TrendingDown" color="from-rose-400 to-pink-500" />
            </div>

            <!-- Achievements -->
            <section class="rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800">
                <h2 class="font-display text-xl font-semibold text-slate-800 dark:text-slate-100">
                    Achievements <span class="text-base text-slate-400 dark:text-slate-500">{{ earnedCount }}/{{ achievements.length }}</span>
                </h2>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-7">
                    <div
                        v-for="achievement in achievements"
                        :key="achievement.id"
                        class="flex flex-col items-center rounded-2xl p-3 text-center transition"
                        :class="achievement.awarded_at ? 'bg-gradient-to-b from-yellow-50 to-amber-100 ring-1 ring-amber-200 dark:from-amber-950/40 dark:to-amber-900/40 dark:ring-amber-800/60' : 'bg-slate-50 dark:bg-slate-800/60 opacity-70'"
                        :title="achievement.description"
                    >
                        <span
                            class="flex h-12 w-12 items-center justify-center rounded-full"
                            :class="achievement.awarded_at ? 'bg-gradient-to-br from-yellow-300 to-orange-400 text-white shadow' : 'bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500'"
                        >
                            <component :is="achievement.awarded_at ? achievementIcon(achievement.icon) : Lock" class="h-6 w-6" />
                        </span>
                        <p class="mt-2 text-xs font-bold text-slate-700 dark:text-slate-200">{{ achievement.name }}</p>
                        <p class="mt-0.5 text-[11px] leading-tight text-slate-500 dark:text-slate-400">
                            {{ achievement.awarded_at ? `Earned ${formatDate(achievement.awarded_at)}` : achievement.description }}
                        </p>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-5">
                <!-- Scores -->
                <section class="rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800 lg:col-span-3">
                    <h2 class="font-display text-xl font-semibold text-slate-800 dark:text-slate-100">Academic history</h2>
                    <div v-if="scores.length" class="-mx-6 mt-4 overflow-x-auto">
                        <table class="w-full min-w-[520px] text-sm">
                            <thead>
                                <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                    <th class="px-6 py-2">Assessment</th>
                                    <th class="px-3 py-2">Date</th>
                                    <th class="px-3 py-2 text-right">Score</th>
                                    <th class="px-3 py-2 text-right">%</th>
                                    <th class="px-6 py-2 text-right">Growth</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="score in scores" :key="score.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
                                    <td class="px-6 py-3">
                                        <Link :href="habitatRoute(habitat, 'assessments.show', { assessment: score.assessment_id })" class="font-semibold text-slate-800 dark:text-slate-100 hover:underline">
                                            {{ score.title }}
                                        </Link>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ score.type_label }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-slate-600 dark:text-slate-300">{{ formatDate(score.assessed_on) }}</td>
                                    <td class="px-3 py-3 text-right text-slate-600 dark:text-slate-300">{{ score.score }}/{{ score.total_score }}</td>
                                    <td class="px-3 py-3 text-right">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-bold" :class="performanceStyles[performanceFor(score.percentage)].chip">
                                            {{ formatPercent(score.percentage) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right font-bold" :class="theme.text">+{{ score.points }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="mt-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 p-6 text-center text-sm text-slate-500 dark:text-slate-400">No scores recorded yet.</p>
                </section>

                <!-- Timeline -->
                <section class="rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800 lg:col-span-2">
                    <h2 class="flex items-center gap-2 font-display text-xl font-semibold text-slate-800 dark:text-slate-100">
                        <History class="h-5 w-5 text-slate-400 dark:text-slate-500" /> Growth timeline
                    </h2>
                    <ol v-if="timeline.length" class="relative mt-4 space-y-4 border-l-2 border-slate-100 dark:border-slate-800 pl-5">
                        <li v-for="entry in timeline" :key="entry.id" class="relative">
                            <span
                                class="absolute -left-[1.85rem] top-0.5 flex h-6 w-6 items-center justify-center rounded-full text-white"
                                :class="entry.delta >= 0 ? 'bg-emerald-500' : 'bg-rose-500'"
                            >
                                <ArrowUp v-if="entry.delta >= 0" class="h-3.5 w-3.5" />
                                <ArrowDown v-else class="h-3.5 w-3.5" />
                            </span>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                {{ reasonLabels[entry.reason] }} · {{ formatDate(entry.created_at) }}
                            </p>
                            <p class="text-sm text-slate-700 dark:text-slate-200">{{ entry.description }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ entry.delta >= 0 ? '+' : '' }}{{ entry.delta }} points → {{ entry.points_after }} total
                                <span v-if="entry.stage_after !== entry.stage_before" class="ml-1 rounded-full px-2 py-0.5 font-bold" :class="entry.stage_after > entry.stage_before ? 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-200' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'">
                                    Stage {{ entry.stage_before }} → {{ entry.stage_after }}
                                </span>
                            </p>
                        </li>
                    </ol>
                    <p v-else class="mt-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 p-6 text-center text-sm text-slate-500 dark:text-slate-400">Growth changes will appear here.</p>
                </section>
            </div>

            <section v-if="otherHabitats.length" class="rounded-3xl bg-white/70 p-6 ring-1 ring-slate-100 dark:bg-slate-900/70 dark:ring-slate-800">
                <h2 class="text-sm font-bold text-slate-600 dark:text-slate-300">Also in</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    <Link
                        v-for="other in otherHabitats"
                        :key="other.member_id"
                        :href="route(`${other.route}.members.show`, { habitat: other.habitat_id, member: other.member_id })"
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold"
                        :class="themeFor(other.type).chip"
                    >
                        <component :is="other.type === 'aquarium' ? Fish : Flower2" class="h-3.5 w-3.5" /> {{ other.name }}
                    </Link>
                </div>
            </section>
        </div>

        <ConfirmDialog
            :show="confirmingRemoval"
            :title="`Remove ${member.student.name} from ${habitat.name}?`"
            :message="`Their ${noun} leaves the ${meta.singular.toLowerCase()}. Scores and growth are kept and come back if you add them again.`"
            confirm-label="Remove"
            @close="confirmingRemoval = false"
            @confirm="remove"
        />
    </AuthenticatedLayout>
</template>
