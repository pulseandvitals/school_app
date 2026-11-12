<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, ArchiveRestore, ClipboardList, Fish, Flower2, Plus, Trash2, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import PlaceholderArt from '@/Components/Game/PlaceholderArt.vue';
import { habitatMeta, habitatRoute, scenePreviews, themeFor } from '@/lib/habitat';

const props = defineProps({
    habitatType: { type: String, required: true },
    habitats: { type: Array, required: true },
    archived: { type: Array, required: true },
});

const meta = computed(() => habitatMeta(props.habitatType));
const theme = computed(() => themeFor(props.habitatType));
const icon = computed(() => (props.habitatType === 'aquarium' ? Fish : Flower2));
const assetType = computed(() => (props.habitatType === 'aquarium' ? 'fish' : 'plant'));
const singular = computed(() => meta.value.singular?.toLowerCase());

const showArchived = ref(false);
const deleting = ref(null);

function toggleArchive(habitat) {
    router.patch(habitatRoute(habitat, 'archive'), {}, { preserveScroll: true });
}

function destroy() {
    router.delete(habitatRoute(deleting.value, 'destroy'), {
        preserveScroll: true,
        onFinish: () => (deleting.value = null),
    });
}
</script>

<template>
    <Head :title="meta.label" />

    <AuthenticatedLayout :theme="habitatType">
        <template #header>
            <PageHeader
                :title="meta.label"
                :subtitle="habitatType === 'aquarium'
                    ? 'Each aquarium is a class. Every student gets a fish that grows with their scores.'
                    : 'Each garden is a class. Every student gets a plant that grows with their scores.'"
            >
                <template #icon>
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow" :class="theme.gradient">
                        <component :is="icon" class="h-6 w-6" />
                    </span>
                </template>
                <template #actions>
                    <AppButton :href="route(`${meta.route}.create`)" :tone="habitatType">
                        <Plus class="h-4 w-4" /> New {{ singular }}
                    </AppButton>
                </template>
            </PageHeader>
        </template>

        <div class="mx-auto max-w-7xl space-y-8 px-4 pt-6 sm:px-6 lg:px-8">
            <div v-if="habitats.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="habitat in habitats"
                    :key="habitat.id"
                    :href="habitatRoute(habitat, 'show')"
                    class="group overflow-hidden rounded-3xl bg-white dark:bg-slate-900 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800 transition hover:-translate-y-1 hover:shadow-xl"
                >
                    <div class="relative h-36 overflow-hidden bg-gradient-to-b" :class="scenePreviews[habitat.theme]">
                        <template v-if="habitatType === 'aquarium'">
                            <div class="absolute left-[15%] top-8 h-10 w-16 animate-bob"><PlaceholderArt type="fish" :seed="habitat.id * 3" /></div>
                            <div class="absolute right-[18%] top-14 h-14 w-20 animate-bob" style="animation-delay: 1.2s"><PlaceholderArt type="fish" :seed="habitat.id * 5 + 1" /></div>
                            <div class="absolute inset-x-0 bottom-0 h-6 bg-amber-100/80" />
                        </template>
                        <template v-else>
                            <div class="absolute inset-x-0 bottom-0 h-10 bg-gradient-to-b from-lime-400 to-green-600" />
                            <div class="absolute bottom-4 left-[18%] h-16 w-12 origin-bottom animate-sway"><PlaceholderArt type="plant" :seed="habitat.id * 3" /></div>
                            <div class="absolute bottom-4 left-[42%] h-24 w-16 origin-bottom animate-sway" style="animation-delay: 0.7s"><PlaceholderArt type="plant" :seed="habitat.id * 5 + 2" /></div>
                            <div class="absolute bottom-4 right-[14%] h-12 w-10 origin-bottom animate-sway" style="animation-delay: 1.4s"><PlaceholderArt type="plant" :seed="habitat.id * 7" /></div>
                        </template>
                        <span class="absolute right-3 top-3 rounded-full bg-white/80 px-2.5 py-1 text-[11px] font-bold text-slate-700 backdrop-blur">
                            {{ habitat.theme_label }}
                        </span>
                    </div>
                    <div class="p-5">
                        <h2 class="truncate font-display text-xl font-semibold text-slate-800 dark:text-slate-100">{{ habitat.name }}</h2>
                        <p class="mt-1 line-clamp-2 min-h-[2.5rem] text-sm text-slate-500 dark:text-slate-400">{{ habitat.description || 'No description yet.' }}</p>
                        <div class="mt-4 flex flex-wrap gap-2 text-xs font-bold">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1" :class="theme.chip">
                                <Users class="h-3.5 w-3.5" /> {{ habitat.members_count }} students
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-slate-600 dark:text-slate-300">
                                <ClipboardList class="h-3.5 w-3.5" /> {{ habitat.assessments_count }} assessments
                            </span>
                            <span v-if="habitat.average_stage" class="rounded-full bg-amber-50 dark:bg-amber-950/40 px-2.5 py-1 text-amber-700 dark:text-amber-300">
                                Avg stage {{ habitat.average_stage }}
                            </span>
                        </div>
                    </div>
                </Link>
            </div>

            <EmptyState
                v-else
                :icon="icon"
                :title="`No ${meta.plural?.toLowerCase()} yet`"
                :description="`Create your first ${singular} for a class or section, then add your students and pick their ${assetType === 'fish' ? 'fish' : 'plants'}.`"
            >
                <AppButton :href="route(`${meta.route}.create`)" :tone="habitatType">
                    <Plus class="h-4 w-4" /> Create {{ singular }}
                </AppButton>
            </EmptyState>

            <section v-if="archived.length" class="rounded-3xl bg-white/70 p-5 ring-1 ring-slate-100 dark:bg-slate-900/70 dark:ring-slate-800">
                <button type="button" class="flex w-full items-center justify-between text-left" @click="showArchived = !showArchived">
                    <span class="flex items-center gap-2 font-display text-lg font-semibold text-slate-700 dark:text-slate-200">
                        <Archive class="h-5 w-5 text-slate-400 dark:text-slate-500" /> Archived ({{ archived.length }})
                    </span>
                    <span class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ showArchived ? 'Hide' : 'Show' }}</span>
                </button>
                <ul v-if="showArchived" class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                    <li v-for="habitat in archived" :key="habitat.id" class="flex flex-wrap items-center gap-3 py-3">
                        <Link :href="habitatRoute(habitat, 'show')" class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-slate-700 dark:text-slate-200 hover:underline">{{ habitat.name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Archived {{ habitat.archived_at }} · {{ habitat.members_count }} students</p>
                        </Link>
                        <AppButton size="sm" variant="secondary" @click="toggleArchive(habitat)">
                            <ArchiveRestore class="h-4 w-4" /> Restore
                        </AppButton>
                        <AppButton size="sm" variant="ghost" class="text-rose-600 hover:bg-rose-50 dark:!text-rose-400 dark:hover:bg-rose-950/40 hover:text-rose-700 dark:hover:text-rose-300" @click="deleting = habitat">
                            <Trash2 class="h-4 w-4" /> Delete
                        </AppButton>
                    </li>
                </ul>
            </section>
        </div>

        <ConfirmDialog
            :show="!!deleting"
            :title="`Delete ${deleting?.name}?`"
            :message="`This removes the ${singular} and hides its students' ${assetType === 'fish' ? 'fish' : 'plants'} and scores. Students stay in your roster.`"
            :confirm-label="`Delete ${singular}`"
            @close="deleting = null"
            @confirm="destroy"
        />
    </AuthenticatedLayout>
</template>
