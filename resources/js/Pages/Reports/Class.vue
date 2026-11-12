<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { CircleAlert, ClipboardList, Gauge, Printer, RotateCcw, Users } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import GameAssetImage from '@/Components/Game/GameAssetImage.vue';
import { formatDate, formatPercent, habitatRoute, performanceFor, performanceStyles, themeFor } from '@/lib/habitat';

const props = defineProps({
    habitat: { type: Object, required: true },
    filters: { type: Object, required: true },
    rows: { type: Array, required: true },
    assessments: { type: Array, required: true },
    summary: { type: Object, required: true },
    options: { type: Object, required: true },
});

const theme = computed(() => themeFor(props.habitat.type));
const assetType = computed(() => (props.habitat.type === 'aquarium' ? 'fish' : 'plant'));
const showMatrix = ref(false);

const filterState = reactive({
    member: props.filters.member ?? '',
    assessment: props.filters.assessment ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    performance: props.filters.performance ?? '',
    stage: props.filters.stage ?? '',
});

const hasFilters = computed(() => Object.values(filterState).some((value) => value !== '' && value !== null));

function apply() {
    const query = Object.fromEntries(Object.entries(filterState).filter(([, value]) => value !== '' && value !== null));
    router.get(habitatRoute(props.habitat, 'report'), query, { preserveState: true, preserveScroll: true, replace: true });
}

function reset() {
    Object.keys(filterState).forEach((key) => (filterState[key] = ''));
    apply();
}

function printReport() {
    window.print();
}

const selectClass = 'w-full rounded-xl border-slate-200 dark:border-slate-700 text-sm focus:border-slate-400 focus:ring-slate-300';
</script>

<template>
    <Head :title="`Class report · ${habitat.name}`" />

    <AuthenticatedLayout :theme="habitat.type">
        <template #header>
            <PageHeader
                title="Class report"
                :eyebrow="habitat.name"
                :back-href="habitatRoute(habitat, 'show')"
                :back-label="`Back to ${habitat.name}`"
            >
                <template #actions>
                    <AppButton variant="secondary" class="print:hidden" @click="printReport">
                        <Printer class="h-4 w-4" /> Print
                    </AppButton>
                </template>
            </PageHeader>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 pt-6 sm:px-6 lg:px-8">
            <!-- Filters -->
            <form class="grid gap-3 rounded-3xl bg-white dark:bg-slate-900 p-5 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800 sm:grid-cols-3 lg:grid-cols-7 print:hidden" @submit.prevent="apply">
                <label class="text-xs font-bold text-slate-500 dark:text-slate-400">
                    Student
                    <select v-model="filterState.member" :class="selectClass" @change="apply">
                        <option value="">All students</option>
                        <option v-for="option in options.members" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </label>
                <label class="text-xs font-bold text-slate-500 dark:text-slate-400">
                    Assessment
                    <select v-model="filterState.assessment" :class="selectClass" @change="apply">
                        <option value="">All assessments</option>
                        <option v-for="option in options.assessments" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </label>
                <label class="text-xs font-bold text-slate-500 dark:text-slate-400">
                    From
                    <input v-model="filterState.from" type="date" :class="selectClass" @change="apply" />
                </label>
                <label class="text-xs font-bold text-slate-500 dark:text-slate-400">
                    To
                    <input v-model="filterState.to" type="date" :class="selectClass" @change="apply" />
                </label>
                <label class="text-xs font-bold text-slate-500 dark:text-slate-400">
                    Performance
                    <select v-model="filterState.performance" :class="selectClass" @change="apply">
                        <option value="">Any</option>
                        <option v-for="option in options.performance" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </label>
                <label class="text-xs font-bold text-slate-500 dark:text-slate-400">
                    Growth stage
                    <select v-model="filterState.stage" :class="selectClass" @change="apply">
                        <option value="">Any</option>
                        <option v-for="option in options.stages" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </label>
                <div class="flex items-end">
                    <AppButton v-if="hasFilters" variant="ghost" class="w-full" @click="reset">
                        <RotateCcw class="h-4 w-4" /> Reset
                    </AppButton>
                </div>
            </form>

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Students" :value="summary.students" :icon="Users" color="from-violet-500 to-fuchsia-500" />
                <StatCard label="Assessments" :value="summary.assessments" :icon="ClipboardList" color="from-amber-400 to-orange-500" />
                <StatCard label="Class average" :value="formatPercent(summary.class_average)" :icon="Gauge" color="from-sky-400 to-blue-600" />
                <StatCard label="Need help" :value="summary.needs_improvement" :icon="CircleAlert" color="from-rose-400 to-pink-500" />
            </div>

            <section class="overflow-hidden rounded-3xl bg-white dark:bg-slate-900 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 px-6 py-4">
                    <h2 class="font-display text-xl font-semibold text-slate-800 dark:text-slate-100">Students</h2>
                    <label v-if="assessments.length" class="flex items-center gap-2 text-sm font-semibold text-slate-600 dark:text-slate-300 print:hidden">
                        <input v-model="showMatrix" type="checkbox" class="rounded border-slate-300 text-slate-900 dark:text-white focus:ring-slate-400" />
                        Show each assessment
                    </label>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                <th class="sticky left-0 bg-white dark:bg-slate-900 px-6 py-3">Student</th>
                                <th class="px-3 py-3">Stage</th>
                                <th class="px-3 py-3 text-right">Points</th>
                                <th class="px-3 py-3 text-right">Done</th>
                                <th class="px-3 py-3 text-right">Average</th>
                                <th class="px-3 py-3 text-right">High</th>
                                <th class="px-3 py-3 text-right">Low</th>
                                <template v-if="showMatrix">
                                    <th v-for="assessment in assessments" :key="assessment.id" class="whitespace-nowrap px-3 py-3 text-right" :title="`${assessment.title} · ${formatDate(assessment.assessed_on)}`">
                                        {{ assessment.title.length > 14 ? `${assessment.title.slice(0, 13)}…` : assessment.title }}
                                    </th>
                                </template>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="row in rows" :key="row.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
                                <td class="sticky left-0 bg-white dark:bg-slate-900 px-6 py-3">
                                    <Link :href="habitatRoute(habitat, 'members.show', { member: row.id })" class="flex items-center gap-3 font-semibold text-slate-800 dark:text-slate-100 hover:underline">
                                        <span class="h-8 w-8 shrink-0"><GameAssetImage :asset="{ image_url: row.image_url, id: row.id }" :type="assetType" /></span>
                                        {{ row.name }}
                                    </Link>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-bold" :class="theme.chip">{{ row.stage }} · {{ row.stage_name }}</span>
                                </td>
                                <td class="px-3 py-3 text-right font-semibold text-slate-700 dark:text-slate-200">{{ row.points }}</td>
                                <td class="px-3 py-3 text-right text-slate-600 dark:text-slate-300">{{ row.completed }}</td>
                                <td class="px-3 py-3 text-right">
                                    <span v-if="row.performance" class="rounded-full px-2 py-0.5 text-xs font-bold" :class="performanceStyles[row.performance].chip">{{ formatPercent(row.average) }}</span>
                                    <span v-else class="text-slate-400 dark:text-slate-500">—</span>
                                </td>
                                <td class="px-3 py-3 text-right text-slate-600 dark:text-slate-300">{{ formatPercent(row.highest) }}</td>
                                <td class="px-3 py-3 text-right text-slate-600 dark:text-slate-300">{{ formatPercent(row.lowest) }}</td>
                                <template v-if="showMatrix">
                                    <td v-for="assessment in assessments" :key="assessment.id" class="px-3 py-3 text-right">
                                        <span
                                            v-if="row.by_assessment[assessment.id] !== undefined"
                                            class="rounded px-1.5 py-0.5 text-xs font-semibold"
                                            :class="performanceStyles[performanceFor(row.by_assessment[assessment.id])].chip"
                                        >
                                            {{ formatPercent(row.by_assessment[assessment.id]) }}
                                        </span>
                                        <span v-else class="text-slate-300">—</span>
                                    </td>
                                </template>
                            </tr>
                            <tr v-if="!rows.length">
                                <td colspan="7" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">No students match these filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
