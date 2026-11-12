<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { BarChart3, ClipboardList, Fish, Flower2, Gauge, Plus, Sparkles, TrendingUp, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import PlaceholderArt from '@/Components/Game/PlaceholderArt.vue';
import DashboardModuleSection from '@/Components/Dashboard/ModuleSection.vue';
import { formatDate, formatPercent, themeFor } from '@/lib/habitat';

const props = defineProps({
    totals: { type: Object, required: true },
    sections: { type: Object, required: true },
    recentAssessments: { type: Array, required: true },
    milestones: { type: Array, required: true },
});

const environments = computed(() => [
    {
        type: 'aquarium',
        title: 'School of Fishes',
        tagline: 'Every student is a fish. Great scores help them grow bigger and brighter.',
        count: props.totals.aquariums,
        noun: props.totals.aquariums === 1 ? 'aquarium' : 'aquariums',
        students: props.sections.aquarium.students,
        index: route('aquariums.index'),
        create: route('aquariums.create'),
        icon: Fish,
        card: 'from-sky-400 via-cyan-500 to-blue-700',
    },
    {
        type: 'garden',
        title: 'Garden of Hope',
        tagline: 'Every student plants a seed. Hard work helps it sprout, grow and bloom.',
        count: props.totals.gardens,
        noun: props.totals.gardens === 1 ? 'garden' : 'gardens',
        students: props.sections.garden.students,
        index: route('gardens.index'),
        create: route('gardens.create'),
        icon: Flower2,
        card: 'from-lime-400 via-emerald-500 to-green-700',
    },
]);

function milestoneHref(item) {
    return route(`${item.route}.members.show`, { habitat: item.habitat_id, member: item.member_id });
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-7xl space-y-10 px-4 pt-8 sm:px-6 lg:px-8">
            <div>
                <p class="text-sm font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Welcome back</p>
                <h1 class="font-display text-3xl font-semibold text-slate-800 dark:text-slate-100 sm:text-4xl">
                    Hi {{ $page.props.auth.user.name.split(' ')[0] }}, where shall we go today?
                </h1>
            </div>

            <!-- Environment selection -->
            <div class="grid gap-6 md:grid-cols-2">
                <div
                    v-for="env in environments"
                    :key="env.type"
                    class="group relative overflow-hidden rounded-[2rem] bg-gradient-to-br p-6 text-white shadow-xl sm:p-8"
                    :class="env.card"
                >
                    <!-- Decorative mini scene -->
                    <div class="pointer-events-none absolute -right-6 bottom-0 flex h-40 w-56 items-end gap-2 opacity-90 sm:h-48 sm:w-64" aria-hidden="true">
                        <template v-if="env.type === 'aquarium'">
                            <div class="absolute right-24 top-6 h-14 w-20 animate-bob"><PlaceholderArt type="fish" :seed="3" /></div>
                            <div class="absolute right-6 top-16 h-20 w-28 animate-bob" style="animation-delay: 1s"><PlaceholderArt type="fish" :seed="7" /></div>
                            <div class="absolute bottom-6 right-36 h-10 w-14 animate-bob" style="animation-delay: 2s"><PlaceholderArt type="fish" :seed="11" /></div>
                            <span v-for="n in 6" :key="n" class="absolute bottom-0 animate-bubble-rise rounded-full border border-white/70" :style="{ right: `${n * 34}px`, width: `${6 + n}px`, height: `${6 + n}px`, animationDelay: `${n * 1.1}s` }" />
                        </template>
                        <template v-else>
                            <div class="h-16 w-14 origin-bottom animate-sway"><PlaceholderArt type="plant" :seed="2" /></div>
                            <div class="h-28 w-20 origin-bottom animate-sway" style="animation-delay: 0.8s"><PlaceholderArt type="plant" :seed="6" /></div>
                            <div class="h-36 w-24 origin-bottom animate-sway" style="animation-delay: 1.6s"><PlaceholderArt type="plant" :seed="9" /></div>
                        </template>
                    </div>

                    <div class="relative max-w-xs">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white/20 backdrop-blur">
                            <component :is="env.icon" class="h-7 w-7" />
                        </span>
                        <h2 class="mt-4 font-display text-3xl font-semibold">{{ env.title }}</h2>
                        <p class="mt-2 text-sm text-white/85">{{ env.tagline }}</p>
                        <p class="mt-4 text-sm font-semibold text-white/90">
                            {{ env.count }} {{ env.noun }} · {{ env.students }} {{ env.students === 1 ? 'student' : 'students' }}
                        </p>
                        <div class="mt-6 flex flex-wrap gap-2">
                            <Link :href="env.index" class="rounded-2xl bg-white dark:bg-slate-900 px-4 py-2.5 text-sm font-bold text-slate-800 dark:text-slate-100 shadow transition hover:-translate-y-0.5 hover:shadow-lg">
                                Enter {{ env.title }}
                            </Link>
                            <Link :href="env.create" class="inline-flex items-center gap-1.5 rounded-2xl bg-white/20 px-4 py-2.5 text-sm font-bold text-white backdrop-blur transition hover:bg-white/30">
                                <Plus class="h-4 w-4" /> New
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                <StatCard label="Students" :value="totals.students" :icon="Users" color="from-violet-500 to-fuchsia-500" />
                <StatCard label="Aquariums" :value="totals.aquariums" :icon="Fish" color="from-cyan-500 to-blue-600" />
                <StatCard label="Gardens" :value="totals.gardens" :icon="Flower2" color="from-lime-500 to-emerald-600" />
                <StatCard label="Assessments" :value="totals.assessments" :icon="ClipboardList" color="from-amber-400 to-orange-500" />
                <StatCard label="Class average" :value="formatPercent(totals.average)" :icon="Gauge" color="from-rose-400 to-pink-500" class="col-span-2 lg:col-span-1" />
            </div>

            <!-- Module sections -->
            <div class="grid gap-6 xl:grid-cols-2">
                <DashboardModuleSection type="aquarium" :section="sections.aquarium" />
                <DashboardModuleSection type="garden" :section="sections.garden" />
            </div>

            <!-- Activity -->
            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800">
                    <h2 class="flex items-center gap-2 font-display text-xl font-semibold text-slate-800 dark:text-slate-100">
                        <BarChart3 class="h-5 w-5 text-amber-500" /> Recent assessments
                    </h2>
                    <ul v-if="recentAssessments.length" class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                        <li v-for="item in recentAssessments" :key="item.id">
                            <Link
                                :href="route(`${item.route}.assessments.show`, { habitat: item.habitat_id, assessment: item.id })"
                                class="-mx-2 flex items-center gap-3 rounded-xl px-2 py-3 transition hover:bg-slate-50 dark:hover:bg-slate-800/60"
                            >
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :class="themeFor(item.habitat_type).chip">
                                    <component :is="item.habitat_type === 'aquarium' ? Fish : Flower2" class="h-5 w-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold text-slate-800 dark:text-slate-100">{{ item.title }}</p>
                                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ item.habitat_name }} · {{ item.type_label }} · {{ formatDate(item.assessed_on) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-display font-semibold text-slate-700 dark:text-slate-200">{{ formatPercent(item.average) }}</p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500">{{ item.scores_count }} scored</p>
                                </div>
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="mt-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 p-6 text-center text-sm text-slate-500 dark:text-slate-400">
                        No assessments yet. Open an aquarium or garden to create one.
                    </p>
                </section>

                <section class="rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800">
                    <h2 class="flex items-center gap-2 font-display text-xl font-semibold text-slate-800 dark:text-slate-100">
                        <Sparkles class="h-5 w-5 text-yellow-500" /> Growth milestones
                    </h2>
                    <ul v-if="milestones.length" class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                        <li v-for="item in milestones" :key="item.id">
                            <Link :href="milestoneHref(item)" class="-mx-2 flex items-center gap-3 rounded-xl px-2 py-3 transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :class="themeFor(item.habitat_type).chip">
                                    <TrendingUp class="h-5 w-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm text-slate-700 dark:text-slate-200">
                                        <strong class="text-slate-900 dark:text-white">{{ item.name }}</strong> reached
                                        <strong>{{ item.stage_name }}</strong>
                                    </p>
                                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ item.habitat_name }} · {{ formatDate(item.created_at) }}</p>
                                </div>
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="mt-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 p-6 text-center text-sm text-slate-500 dark:text-slate-400">
                        Stage-ups will appear here as your students' fish and plants grow.
                    </p>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
