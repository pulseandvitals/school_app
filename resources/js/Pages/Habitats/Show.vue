<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, ArchiveRestore, BarChart3, ChevronRight, ClipboardList, Eye, EyeOff, Info, Pencil, Plus, UserPlus, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import GameAssetImage from '@/Components/Game/GameAssetImage.vue';
import GrowthStageDisplay from '@/Components/Game/GrowthStageDisplay.vue';
import StudentGameProfile from '@/Components/Game/StudentGameProfile.vue';
import HabitatScene from '@/Components/Scenes/HabitatScene.vue';
import StudentAvatar from '@/Components/StudentAvatar.vue';
import { formatDate, formatPercent, habitatMeta, habitatRoute, themeFor } from '@/lib/habitat';

const props = defineProps({
    habitat: { type: Object, required: true },
    members: { type: Array, required: true },
    recentAssessments: { type: Array, required: true },
    growthRule: { type: Object, required: true },
});

const meta = computed(() => habitatMeta(props.habitat.type));
const theme = computed(() => themeFor(props.habitat.type));
const assetType = computed(() => (props.habitat.type === 'aquarium' ? 'fish' : 'plant'));
const noun = computed(() => meta.value.member_noun);

const selectedId = ref(null);
// Names crowd small screens, so they start hidden on phones.
const showNames = ref(typeof window === 'undefined' || window.innerWidth >= 640);
const selected = computed(() => props.members.find((member) => member.id === selectedId.value) ?? null);

function select(member) {
    selectedId.value = member?.id ?? null;
}

function toggleArchive() {
    router.patch(habitatRoute(props.habitat, 'archive'));
}

const sortedBands = computed(() => [...props.growthRule.point_bands].sort((a, b) => b.min - a.min));

function bandLabel(band, index) {
    const upper = index === 0 ? 100 : sortedBands.value[index - 1].min - 1;
    return band.min === upper ? `${band.min}%` : `${band.min}–${upper}%`;
}
</script>

<template>
    <Head :title="habitat.name" />

    <AuthenticatedLayout :theme="habitat.type">
        <template #header>
            <PageHeader
                :title="habitat.name"
                :subtitle="habitat.description"
                :eyebrow="meta.label"
                :back-href="route(`${habitat.route}.index`)"
                :back-label="`All ${meta.plural.toLowerCase()}`"
            >
                <template #actions>
                    <AppButton :href="habitatRoute(habitat, 'members.create')" :tone="habitat.type">
                        <UserPlus class="h-4 w-4" /> Add student
                    </AppButton>
                    <AppButton :href="habitatRoute(habitat, 'assessments.create')" variant="secondary">
                        <Plus class="h-4 w-4" /> New assessment
                    </AppButton>
                    <AppButton :href="habitatRoute(habitat, 'report')" variant="secondary">
                        <BarChart3 class="h-4 w-4" /> Report
                    </AppButton>
                    <AppButton :href="habitatRoute(habitat, 'edit')" variant="ghost" aria-label="Edit">
                        <Pencil class="h-4 w-4" />
                    </AppButton>
                    <AppButton variant="ghost" :aria-label="habitat.is_archived ? 'Restore' : 'Archive'" @click="toggleArchive">
                        <ArchiveRestore v-if="habitat.is_archived" class="h-4 w-4" />
                        <Archive v-else class="h-4 w-4" />
                    </AppButton>
                </template>
            </PageHeader>
        </template>

        <div class="mx-auto max-w-7xl space-y-8 px-4 pt-6 sm:px-6 lg:px-8">
            <div v-if="habitat.is_archived" class="flex items-center gap-2 rounded-2xl bg-amber-50 dark:bg-amber-950/40 px-4 py-3 text-sm font-medium text-amber-800 dark:text-amber-200 ring-1 ring-amber-100 dark:ring-amber-900/60">
                <Archive class="h-4 w-4" /> This {{ meta.singular.toLowerCase() }} is archived. Restore it to show it in your list again.
            </div>

            <!-- Scene -->
            <div class="relative overflow-hidden rounded-[2rem] shadow-xl ring-4 ring-white">
                <HabitatScene
                    :type="habitat.type"
                    :members="members"
                    :theme="habitat.theme"
                    :selected-id="selectedId"
                    :show-names="showNames"
                    :class="habitat.type === 'aquarium' ? 'h-[440px] sm:h-[560px]' : 'min-h-[440px]'"
                    @select="select"
                />

                <!-- Toolbar -->
                <div class="absolute left-4 top-4 z-[90] flex items-center gap-2">
                    <span class="rounded-full bg-white/85 px-3 py-1.5 text-xs font-bold text-slate-700 shadow backdrop-blur">
                        {{ members.length }} {{ members.length === 1 ? noun : (noun === 'fish' ? 'fish' : `${noun}s`) }}
                    </span>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full bg-white/85 px-3 py-1.5 text-xs font-bold text-slate-700 shadow backdrop-blur transition hover:bg-white"
                        :aria-pressed="showNames"
                        @click="showNames = !showNames"
                    >
                        <component :is="showNames ? EyeOff : Eye" class="h-3.5 w-3.5" />
                        {{ showNames ? 'Hide names' : 'Show names' }}
                    </button>
                </div>

                <!-- Empty -->
                <div v-if="!members.length" class="absolute inset-0 z-[80] flex items-center justify-center p-4">
                    <div class="max-w-sm rounded-3xl bg-white/90 p-6 dark:bg-slate-900/90 text-center shadow-xl backdrop-blur">
                        <p class="font-display text-xl font-semibold text-slate-800 dark:text-slate-100">
                            {{ habitat.type === 'aquarium' ? 'The water is waiting!' : 'The soil is ready!' }}
                        </p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Add your first student and choose their {{ noun }}.</p>
                        <AppButton :href="habitatRoute(habitat, 'members.create')" :tone="habitat.type" class="mt-4">
                            <UserPlus class="h-4 w-4" /> Add student
                        </AppButton>
                    </div>
                </div>

                <!-- Selected student -->
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="translate-x-6 opacity-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-to-class="translate-x-6 opacity-0"
                >
                    <div
                        v-if="selected"
                        class="absolute bottom-4 right-4 z-[95] w-[calc(100%-2rem)] max-w-xs rounded-3xl bg-white/95 p-4 dark:bg-slate-900/95 shadow-2xl backdrop-blur sm:bottom-auto sm:top-4"
                        @click.stop
                    >
                        <button type="button" class="absolute right-3 top-3 z-10 rounded-full p-1 text-slate-400 dark:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200" aria-label="Close" @click="select(null)">
                            <X class="h-4 w-4" />
                        </button>
                        <StudentGameProfile :member="selected" :habitat-type="habitat.type">
                            <div class="flex gap-2">
                                <AppButton size="sm" :tone="habitat.type" class="flex-1" :href="habitatRoute(habitat, 'members.show', { member: selected.id })">
                                    View report
                                </AppButton>
                                <AppButton size="sm" variant="secondary" :href="habitatRoute(habitat, 'members.edit', { member: selected.id })" aria-label="Edit student">
                                    <Pencil class="h-4 w-4" />
                                </AppButton>
                            </div>
                        </StudentGameProfile>
                    </div>
                </Transition>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Roster -->
                <section class="rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800 lg:col-span-2">
                    <div class="flex items-center justify-between">
                        <h2 class="font-display text-xl font-semibold text-slate-800 dark:text-slate-100">Students</h2>
                        <Link :href="habitatRoute(habitat, 'members.create')" class="text-sm font-semibold" :class="theme.text">+ Add</Link>
                    </div>
                    <ul v-if="members.length" class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                        <li v-for="member in members" :key="member.id">
                            <Link
                                :href="habitatRoute(habitat, 'members.show', { member: member.id })"
                                class="-mx-2 grid grid-cols-[auto_1fr_auto] items-center gap-3 rounded-2xl px-2 py-3 transition hover:bg-slate-50 dark:hover:bg-slate-800/60 sm:grid-cols-[auto_1fr_200px_auto]"
                            >
                                <div class="relative">
                                    <StudentAvatar :name="member.student.name" :photo-url="member.student.photo_url" />
                                    <span class="absolute -bottom-1 -right-2 h-7 w-7 rounded-full bg-white dark:bg-slate-900 p-0.5 shadow">
                                        <GameAssetImage :asset="member.asset" :type="assetType" :stage="member.growth.stage" />
                                    </span>
                                </div>
                                <div class="min-w-0 pl-1">
                                    <p class="truncate font-semibold text-slate-800 dark:text-slate-100">{{ member.student.name }}</p>
                                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                        Avg {{ formatPercent(member.average_percentage) }} · {{ member.scores_count }} assessments
                                    </p>
                                </div>
                                <div class="hidden sm:block">
                                    <GrowthStageDisplay :growth="member.growth" :points="member.growth_points" :habitat-type="habitat.type" compact />
                                </div>
                                <ChevronRight class="h-4 w-4 text-slate-300" />
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="mt-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 p-6 text-center text-sm text-slate-500 dark:text-slate-400">No students yet.</p>
                </section>

                <div class="space-y-6">
                    <!-- Assessments -->
                    <section class="rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800">
                        <div class="flex items-center justify-between">
                            <h2 class="flex items-center gap-2 font-display text-xl font-semibold text-slate-800 dark:text-slate-100">
                                <ClipboardList class="h-5 w-5 text-amber-500" /> Assessments
                            </h2>
                            <Link :href="habitatRoute(habitat, 'assessments.index')" class="text-sm font-semibold" :class="theme.text">See all</Link>
                        </div>
                        <ul v-if="recentAssessments.length" class="mt-3 space-y-1">
                            <li v-for="assessment in recentAssessments" :key="assessment.id">
                                <Link
                                    :href="habitatRoute(habitat, 'assessments.show', { assessment: assessment.id })"
                                    class="-mx-2 flex items-center justify-between gap-3 rounded-xl px-2 py-2 transition hover:bg-slate-50 dark:hover:bg-slate-800/60"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ assessment.title }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ assessment.type_label }} · {{ formatDate(assessment.assessed_on) }}</p>
                                    </div>
                                    <span class="shrink-0 text-sm font-bold text-slate-600 dark:text-slate-300">{{ formatPercent(assessment.average_percentage) }}</span>
                                </Link>
                            </li>
                        </ul>
                        <div v-else class="mt-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 p-4 text-center text-sm text-slate-500 dark:text-slate-400">
                            Record a quiz or exam to start growing.
                            <AppButton size="sm" class="mt-3" :tone="habitat.type" :href="habitatRoute(habitat, 'assessments.create')">
                                <Plus class="h-4 w-4" /> New assessment
                            </AppButton>
                        </div>
                    </section>

                    <!-- How growth works -->
                    <section class="rounded-3xl p-6 ring-1" :class="[theme.soft, theme.softBorder]">
                        <h2 class="flex items-center gap-2 font-display text-lg font-semibold text-slate-800 dark:text-slate-100">
                            <Info class="h-5 w-5" :class="theme.text" /> How growth works
                        </h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Rules: {{ growthRule.name }}</p>
                        <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Each score earns</p>
                                <ul class="space-y-0.5">
                                    <li v-for="(band, i) in sortedBands" :key="band.min" class="flex justify-between">
                                        <span class="text-slate-600 dark:text-slate-300">{{ bandLabel(band, i) }}</span>
                                        <strong class="text-slate-800 dark:text-slate-100">+{{ band.points }}</strong>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Stages</p>
                                <ul class="space-y-0.5">
                                    <li v-for="stage in growthRule.stages" :key="stage.stage" class="flex justify-between gap-2">
                                        <span class="truncate text-slate-600 dark:text-slate-300">{{ stage.name }}</span>
                                        <strong class="shrink-0 text-slate-800 dark:text-slate-100">{{ stage.min_points }}+</strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
