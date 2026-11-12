<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight, ClipboardList, Plus } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { formatDate, formatPercent, habitatRoute, performanceFor, performanceStyles } from '@/lib/habitat';

defineProps({
    habitat: { type: Object, required: true },
    assessments: { type: Array, required: true },
});
</script>

<template>
    <Head :title="`Assessments · ${habitat.name}`" />

    <AuthenticatedLayout :theme="habitat.type">
        <template #header>
            <PageHeader
                title="Assessments"
                :eyebrow="habitat.name"
                :back-href="habitatRoute(habitat, 'show')"
                :back-label="`Back to ${habitat.name}`"
            >
                <template #actions>
                    <AppButton :href="habitatRoute(habitat, 'assessments.create')" :tone="habitat.type">
                        <Plus class="h-4 w-4" /> New assessment
                    </AppButton>
                </template>
            </PageHeader>
        </template>

        <div class="mx-auto max-w-5xl px-4 pt-6 sm:px-6 lg:px-8">
            <ul v-if="assessments.length" class="space-y-3">
                <li v-for="assessment in assessments" :key="assessment.id">
                    <Link
                        :href="habitatRoute(habitat, 'assessments.show', { assessment: assessment.id })"
                        class="flex items-center gap-4 rounded-3xl bg-white dark:bg-slate-900 p-5 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800 transition hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300">
                            <ClipboardList class="h-6 w-6" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-display text-lg font-semibold text-slate-800 dark:text-slate-100">{{ assessment.title }}</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                {{ assessment.type_label }} · {{ formatDate(assessment.assessed_on) }} · out of {{ assessment.total_score }}
                            </p>
                        </div>
                        <div class="hidden text-right sm:block">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">Scored</p>
                            <p class="font-semibold text-slate-700 dark:text-slate-200">{{ assessment.scores_count }}/{{ habitat.members_count }}</p>
                        </div>
                        <span
                            v-if="assessment.average_percentage !== null"
                            class="rounded-full px-3 py-1 text-sm font-bold"
                            :class="performanceStyles[performanceFor(assessment.average_percentage)].chip"
                        >
                            {{ formatPercent(assessment.average_percentage) }}
                        </span>
                        <span v-else class="rounded-full bg-slate-100 dark:bg-slate-800 px-3 py-1 text-xs font-bold text-slate-500 dark:text-slate-400">Not scored</span>
                        <ChevronRight class="h-5 w-5 text-slate-300" />
                    </Link>
                </li>
            </ul>

            <EmptyState
                v-else
                :icon="ClipboardList"
                title="No assessments yet"
                description="Create a quiz, exam or activity, enter the scores, and watch your class grow."
            >
                <AppButton :href="habitatRoute(habitat, 'assessments.create')" :tone="habitat.type">
                    <Plus class="h-4 w-4" /> New assessment
                </AppButton>
            </EmptyState>
        </div>
    </AuthenticatedLayout>
</template>
