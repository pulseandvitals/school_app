<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Copy, Fish, Flower2, Lock, Pencil, Plus, Settings2, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { habitatMeta, themeFor } from '@/lib/habitat';

const props = defineProps({
    rules: { type: Array, required: true },
});

const groups = computed(() => ['aquarium', 'garden'].map((type) => ({
    type,
    meta: habitatMeta(type),
    theme: themeFor(type),
    rules: props.rules.filter((rule) => rule.habitat_type === type),
})));

const deleting = ref(null);

function destroy() {
    router.delete(route('growth-rules.destroy', deleting.value.id), {
        preserveScroll: true,
        onFinish: () => (deleting.value = null),
    });
}
</script>

<template>
    <Head title="Growth Rules" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                title="Growth rules"
                subtitle="Decide how many growth points each score earns and how many points each stage needs. Every aquarium and garden can use its own rules."
            >
                <template #icon>
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow">
                        <Settings2 class="h-6 w-6" />
                    </span>
                </template>
            </PageHeader>
        </template>

        <div class="mx-auto max-w-7xl space-y-10 px-4 pt-6 sm:px-6 lg:px-8">
            <section v-for="group in groups" :key="group.type">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="flex items-center gap-2 font-display text-2xl font-semibold text-slate-800 dark:text-slate-100">
                        <component :is="group.type === 'aquarium' ? Fish : Flower2" class="h-6 w-6" :class="group.theme.text" />
                        {{ group.meta.label }}
                    </h2>
                    <AppButton size="sm" :tone="group.type" :href="route('growth-rules.create', { type: group.type })">
                        <Plus class="h-4 w-4" /> New rule
                    </AppButton>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <article v-for="rule in group.rules" :key="rule.id" class="rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-display text-lg font-semibold text-slate-800 dark:text-slate-100">{{ rule.name }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    <template v-if="rule.is_default">
                                        <Lock class="-mt-0.5 inline h-3 w-3" /> System default · used when no rule is chosen
                                    </template>
                                    <template v-else>Used by {{ rule.habitats_count }} {{ rule.habitats_count === 1 ? group.meta.singular.toLowerCase() : group.meta.plural.toLowerCase() }}</template>
                                </p>
                            </div>
                            <div class="flex gap-1">
                                <template v-if="!rule.is_default">
                                    <AppButton size="sm" variant="ghost" :href="route('growth-rules.edit', rule.id)" aria-label="Edit"><Pencil class="h-4 w-4" /></AppButton>
                                    <AppButton size="sm" variant="ghost" class="text-rose-600 hover:bg-rose-50 dark:!text-rose-400 dark:hover:bg-rose-950/40" aria-label="Delete" @click="deleting = rule"><Trash2 class="h-4 w-4" /></AppButton>
                                </template>
                                <AppButton v-else size="sm" variant="secondary" :href="route('growth-rules.create', { type: group.type })">
                                    <Copy class="h-4 w-4" /> Customise
                                </AppButton>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">Points per score</p>
                                <ul class="space-y-0.5">
                                    <li v-for="band in rule.point_bands" :key="band.min" class="flex justify-between">
                                        <span class="text-slate-600 dark:text-slate-300">≥ {{ band.min }}%</span>
                                        <strong class="text-slate-800 dark:text-slate-100">+{{ band.points }}</strong>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">Stages</p>
                                <ul class="space-y-0.5">
                                    <li v-for="stage in rule.stages" :key="stage.stage" class="flex justify-between gap-2">
                                        <span class="truncate text-slate-600 dark:text-slate-300">{{ stage.stage }}. {{ stage.name }}</span>
                                        <strong class="shrink-0 text-slate-800 dark:text-slate-100">{{ stage.min_points }}+</strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </div>

        <ConfirmDialog
            :show="!!deleting"
            :title="`Delete ${deleting?.name}?`"
            message="Aquariums or gardens using this rule switch back to the default rules and their growth is recalculated."
            confirm-label="Delete rule"
            @close="deleting = null"
            @confirm="destroy"
        />
    </AuthenticatedLayout>
</template>
