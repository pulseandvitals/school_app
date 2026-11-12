<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Save, Trash2, UserPlus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import GameAssetImage from '@/Components/Game/GameAssetImage.vue';
import StudentAvatar from '@/Components/StudentAvatar.vue';
import { formatDate, formatPercent, habitatRoute, performanceFor, performanceStyles, pointsForPercentage, themeFor } from '@/lib/habitat';

const props = defineProps({
    habitat: { type: Object, required: true },
    assessment: { type: Object, required: true },
    members: { type: Array, required: true },
    scores: { type: [Object, Array], required: true },
    pointBands: { type: Array, required: true },
});

const theme = computed(() => themeFor(props.habitat.type));
const assetType = computed(() => (props.habitat.type === 'aquarium' ? 'fish' : 'plant'));

function initialScores() {
    return Object.fromEntries(props.members.map((member) => [member.id, props.scores[member.id]?.score ?? '']));
}

const form = useForm({ scores: initialScores() });

const inputs = ref([]);

function preview(memberId) {
    const raw = form.scores[memberId];
    if (raw === '' || raw === null || Number.isNaN(Number(raw))) return null;
    const percentage = Math.min(Math.max((Number(raw) / props.assessment.total_score) * 100, 0), 100);
    return { percentage: Math.round(percentage * 100) / 100, points: pointsForPercentage(props.pointBands, percentage) };
}

function isChanged(memberId) {
    const saved = props.scores[memberId]?.score ?? '';
    const current = form.scores[memberId];
    return current === '' || current === null ? saved !== '' : Number(current) !== saved;
}

const changedCount = computed(() => props.members.filter((member) => isChanged(member.id)).length);
const enteredCount = computed(() => props.members.filter((member) => preview(member.id)).length);

function focusNext(index) {
    inputs.value[index + 1]?.focus();
    inputs.value[index + 1]?.select();
}

function save() {
    form.put(habitatRoute(props.habitat, 'assessments.scores.update', { assessment: props.assessment.id }), {
        preserveScroll: true,
        onSuccess: () => form.defaults({ scores: initialScores() }),
    });
}

const confirmingDelete = ref(false);

function destroy() {
    router.delete(habitatRoute(props.habitat, 'assessments.destroy', { assessment: props.assessment.id }), {
        onFinish: () => (confirmingDelete.value = false),
    });
}
</script>

<template>
    <Head :title="assessment.title" />

    <AuthenticatedLayout :theme="habitat.type">
        <template #header>
            <PageHeader
                :title="assessment.title"
                :eyebrow="`${assessment.type_label} · ${formatDate(assessment.assessed_on)} · out of ${assessment.total_score}`"
                :subtitle="assessment.description"
                :back-href="habitatRoute(habitat, 'assessments.index')"
                back-label="All assessments"
            >
                <template #actions>
                    <AppButton variant="secondary" :href="habitatRoute(habitat, 'assessments.edit', { assessment: assessment.id })">
                        <Pencil class="h-4 w-4" /> Edit
                    </AppButton>
                    <AppButton variant="ghost" class="text-rose-600 hover:bg-rose-50 dark:!text-rose-400 dark:hover:bg-rose-950/40 hover:text-rose-700 dark:hover:text-rose-300" @click="confirmingDelete = true">
                        <Trash2 class="h-4 w-4" /> Delete
                    </AppButton>
                </template>
            </PageHeader>
        </template>

        <div class="mx-auto max-w-5xl px-4 pt-6 sm:px-6 lg:px-8">
            <form v-if="members.length" @submit.prevent="save">
                <div class="overflow-hidden rounded-3xl bg-white dark:bg-slate-900 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 px-6 py-4">
                        <p class="text-sm text-slate-600 dark:text-slate-300">
                            <strong class="text-slate-800 dark:text-slate-100">{{ enteredCount }}/{{ members.length }}</strong> scores entered.
                            Leave a box empty to skip a student, or clear it to remove a score.
                        </p>
                        <AppButton type="submit" :tone="habitat.type" :disabled="form.processing || changedCount === 0">
                            <Save class="h-4 w-4" />
                            {{ changedCount ? `Save ${changedCount} ${changedCount === 1 ? 'change' : 'changes'}` : 'All saved' }}
                        </AppButton>
                    </div>

                    <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                        <li
                            v-for="(member, index) in members"
                            :key="member.id"
                            class="grid grid-cols-[auto_1fr_auto] items-center gap-3 px-4 py-3 sm:grid-cols-[auto_1fr_140px_160px] sm:px-6"
                            :class="isChanged(member.id) ? 'bg-amber-50/60 dark:bg-amber-950/30' : ''"
                        >
                            <div class="relative">
                                <StudentAvatar :name="member.student.name" :photo-url="member.student.photo_url" />
                                <span class="absolute -bottom-1 -right-2 h-6 w-6 rounded-full bg-white dark:bg-slate-900 p-0.5 shadow">
                                    <GameAssetImage :asset="member.asset" :type="assetType" />
                                </span>
                            </div>
                            <label :for="`score-${member.id}`" class="min-w-0 pl-1">
                                <span class="block truncate font-semibold text-slate-800 dark:text-slate-100">{{ member.student.name }}</span>
                                <span class="block truncate text-xs text-slate-500 dark:text-slate-400">Stage {{ member.growth.stage }} · {{ member.growth.name }} · {{ member.growth_points }} pts</span>
                            </label>
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <input
                                        :id="`score-${member.id}`"
                                        :ref="(el) => (inputs[index] = el)"
                                        v-model="form.scores[member.id]"
                                        type="number"
                                        inputmode="decimal"
                                        min="0"
                                        :max="assessment.total_score"
                                        step="any"
                                        class="w-20 rounded-xl border-slate-200 dark:border-slate-700 text-right font-semibold focus:border-slate-400 focus:ring-slate-300"
                                        :class="form.errors[`scores.${member.id}`] ? 'border-rose-400' : ''"
                                        @keydown.enter.prevent="focusNext(index)"
                                    />
                                    <span class="text-sm text-slate-400 dark:text-slate-500">/ {{ assessment.total_score }}</span>
                                </div>
                                <p v-if="form.errors[`scores.${member.id}`]" class="mt-1 text-xs text-rose-600">{{ form.errors[`scores.${member.id}`] }}</p>
                            </div>
                            <div class="col-span-3 flex items-center gap-2 sm:col-span-1 sm:justify-end">
                                <template v-if="preview(member.id)">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-bold" :class="performanceStyles[performanceFor(preview(member.id).percentage)].chip">
                                        {{ formatPercent(preview(member.id).percentage) }}
                                    </span>
                                    <span class="text-sm font-bold" :class="theme.text">+{{ preview(member.id).points }} pts</span>
                                </template>
                                <span v-else class="text-xs text-slate-400 dark:text-slate-500">No score</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Sticky save bar on small screens -->
                <div v-if="changedCount" class="fixed inset-x-4 bottom-4 z-30 sm:hidden">
                    <AppButton type="submit" :tone="habitat.type" class="w-full shadow-2xl" :disabled="form.processing">
                        <Save class="h-4 w-4" /> Save {{ changedCount }} {{ changedCount === 1 ? 'change' : 'changes' }}
                    </AppButton>
                </div>
            </form>

            <EmptyState v-else :icon="UserPlus" title="No students to score" description="Add students first, then come back to enter their scores.">
                <AppButton :href="habitatRoute(habitat, 'members.create')" :tone="habitat.type">
                    <UserPlus class="h-4 w-4" /> Add student
                </AppButton>
            </EmptyState>
        </div>

        <ConfirmDialog
            :show="confirmingDelete"
            :title="`Delete ${assessment.title}?`"
            message="All scores for this assessment are removed and growth is recalculated. This is recorded in each student's growth timeline."
            confirm-label="Delete assessment"
            @close="confirmingDelete = false"
            @confirm="destroy"
        />
    </AuthenticatedLayout>
</template>
