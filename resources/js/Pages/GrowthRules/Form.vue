<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus, X } from 'lucide-vue-next';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormField from '@/Components/UI/FormField.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import PlaceholderArt from '@/Components/Game/PlaceholderArt.vue';
import { habitatMeta } from '@/lib/habitat';

const props = defineProps({
    rule: { type: Object, default: null },
    template: { type: Object, default: null },
});

const source = props.rule ?? props.template;
const isEditing = computed(() => !!props.rule);

const form = useForm({
    name: source.name,
    habitat_type: source.habitat_type,
    point_bands: source.point_bands.map((band) => ({ ...band })),
    stages: source.stages.map((stage) => ({ name: stage.name, min_points: stage.min_points, scale: stage.scale })),
});

const meta = computed(() => habitatMeta(form.habitat_type));
const assetType = computed(() => (form.habitat_type === 'aquarium' ? 'fish' : 'plant'));

function switchType(type) {
    if (type === form.habitat_type) return;
    router.get(route('growth-rules.create'), { type }, { replace: true });
}

function addBand() {
    form.point_bands.push({ min: 0, points: 0 });
}

function addStage() {
    const last = form.stages[form.stages.length - 1];
    form.stages.push({ name: 'New stage', min_points: (last?.min_points ?? 0) + 20, scale: Math.min((last?.scale ?? 1) + 0.15, 2) });
}

function submit() {
    if (isEditing.value) {
        form.transform(({ habitat_type, ...data }) => data).put(route('growth-rules.update', props.rule.id));
    } else {
        form.post(route('growth-rules.store'));
    }
}

function fieldError(prefix, index, field) {
    return form.errors[`${prefix}.${index}.${field}`];
}

const inputClass = 'w-full rounded-xl border-slate-200 dark:border-slate-700 text-sm focus:border-slate-400 focus:ring-slate-300';
</script>

<template>
    <Head :title="isEditing ? `Edit ${rule.name}` : 'New growth rule'" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="isEditing ? `Edit ${rule.name}` : 'New growth rule'" :back-href="route('growth-rules.index')" back-label="All growth rules" />
        </template>

        <form class="mx-auto max-w-5xl space-y-6 px-4 pt-6 sm:px-6 lg:px-8" @submit.prevent="submit">
            <section class="grid gap-6 rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800 sm:grid-cols-2">
                <FormField label="Rule name" for="name" :error="form.errors.name">
                    <input id="name" v-model="form.name" type="text" required maxlength="100" :class="inputClass" />
                </FormField>
                <FormField label="For" :error="form.errors.habitat_type">
                    <div class="flex gap-2">
                        <button
                            v-for="type in ['aquarium', 'garden']"
                            :key="type"
                            type="button"
                            class="flex-1 rounded-xl px-3 py-2.5 text-sm font-semibold transition disabled:cursor-not-allowed"
                            :class="form.habitat_type === type ? (type === 'aquarium' ? 'bg-cyan-600 text-white' : 'bg-emerald-600 text-white') : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-50'"
                            :disabled="isEditing && form.habitat_type !== type"
                            @click="switchType(type)"
                        >
                            {{ habitatMeta(type).label }}
                        </button>
                    </div>
                </FormField>
            </section>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800">
                    <h2 class="font-display text-xl font-semibold text-slate-800 dark:text-slate-100">Points per score</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">A score earns the points of the highest band it reaches.</p>
                    <p v-if="form.errors.point_bands" class="mt-2 text-sm text-rose-600">{{ form.errors.point_bands }}</p>
                    <div class="mt-4 space-y-2">
                        <div class="grid grid-cols-[1fr_1fr_auto] gap-2 text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                            <span>From %</span><span>Points</span><span class="w-8" />
                        </div>
                        <div v-for="(band, index) in form.point_bands" :key="index">
                            <div class="grid grid-cols-[1fr_1fr_auto] items-center gap-2">
                                <input v-model.number="band.min" type="number" min="0" max="100" step="any" :class="inputClass" :aria-label="`Band ${index + 1} minimum percentage`" />
                                <input v-model.number="band.points" type="number" min="0" max="1000" :class="inputClass" :aria-label="`Band ${index + 1} points`" />
                                <button type="button" class="rounded-lg p-1.5 text-slate-400 dark:text-slate-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-600 disabled:opacity-30" :disabled="form.point_bands.length <= 1" aria-label="Remove band" @click="form.point_bands.splice(index, 1)">
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                            <p v-if="fieldError('point_bands', index, 'min') || fieldError('point_bands', index, 'points')" class="mt-1 text-xs text-rose-600">
                                {{ fieldError('point_bands', index, 'min') || fieldError('point_bands', index, 'points') }}
                            </p>
                        </div>
                    </div>
                    <AppButton size="sm" variant="secondary" class="mt-3" @click="addBand"><Plus class="h-4 w-4" /> Add band</AppButton>
                </section>

                <section class="rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800">
                    <h2 class="font-display text-xl font-semibold text-slate-800 dark:text-slate-100">Growth stages</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Points needed for each stage, and how big the {{ meta.member_noun }} looks.</p>
                    <p v-if="form.errors.stages" class="mt-2 text-sm text-rose-600">{{ form.errors.stages }}</p>
                    <div class="mt-4 space-y-3">
                        <div v-for="(stage, index) in form.stages" :key="index" class="rounded-2xl bg-slate-50 dark:bg-slate-800/60 p-3">
                            <div class="grid grid-cols-[1fr_90px_auto] items-center gap-2">
                                <input v-model="stage.name" type="text" maxlength="40" :class="inputClass" :aria-label="`Stage ${index + 1} name`" />
                                <input v-model.number="stage.min_points" type="number" min="0" :class="inputClass" :aria-label="`Stage ${index + 1} points needed`" />
                                <button type="button" class="rounded-lg p-1.5 text-slate-400 dark:text-slate-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-600 disabled:opacity-30" :disabled="form.stages.length <= 2" aria-label="Remove stage" @click="form.stages.splice(index, 1)">
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                            <div class="mt-2 flex items-center gap-3">
                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Size</span>
                                <input v-model.number="stage.scale" type="range" min="0.2" max="2" step="0.05" class="flex-1 accent-slate-700 dark:accent-slate-300" :aria-label="`Stage ${index + 1} size`" />
                                <span class="flex h-10 w-12 items-end justify-center">
                                    <span class="block h-10 w-12 origin-bottom" :style="{ transform: `scale(${Math.min(stage.scale, 1.4) / 1.4})` }">
                                        <PlaceholderArt :type="assetType" :seed="4" />
                                    </span>
                                </span>
                            </div>
                            <p v-if="fieldError('stages', index, 'name') || fieldError('stages', index, 'min_points') || fieldError('stages', index, 'scale')" class="mt-1 text-xs text-rose-600">
                                {{ fieldError('stages', index, 'name') || fieldError('stages', index, 'min_points') || fieldError('stages', index, 'scale') }}
                            </p>
                        </div>
                    </div>
                    <AppButton size="sm" variant="secondary" class="mt-3" :disabled="form.stages.length >= 10" @click="addStage"><Plus class="h-4 w-4" /> Add stage</AppButton>
                </section>
            </div>

            <div class="flex items-center justify-between gap-4 rounded-3xl bg-white dark:bg-slate-900 p-4 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ isEditing ? 'Saving recalculates growth for every aquarium or garden using this rule.' : 'Choose this rule in an aquarium or garden\'s settings to use it.' }}
                </p>
                <div class="flex shrink-0 gap-2">
                    <AppButton variant="secondary" :href="route('growth-rules.index')">Cancel</AppButton>
                    <AppButton type="submit" :tone="form.habitat_type" :disabled="form.processing">{{ isEditing ? 'Save rule' : 'Create rule' }}</AppButton>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
