<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormField from '@/Components/UI/FormField.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { habitatRoute } from '@/lib/habitat';

const props = defineProps({
    habitat: { type: Object, required: true },
    assessment: { type: Object, default: null },
    types: { type: Array, required: true },
});

const isEditing = computed(() => !!props.assessment);

const form = useForm({
    title: props.assessment?.title ?? '',
    type: props.assessment?.type ?? 'quiz',
    custom_type: props.assessment?.custom_type ?? '',
    assessed_on: props.assessment?.assessed_on ?? new Date().toLocaleDateString('en-CA'),
    total_score: props.assessment?.total_score ?? 20,
    description: props.assessment?.description ?? '',
});

const totalChanged = computed(() => isEditing.value && Number(form.total_score) !== props.assessment.total_score);

function submit() {
    if (isEditing.value) {
        form.put(habitatRoute(props.habitat, 'assessments.update', { assessment: props.assessment.id }));
    } else {
        form.post(habitatRoute(props.habitat, 'assessments.store'));
    }
}

const inputClass = 'w-full rounded-xl border-slate-200 dark:border-slate-700 focus:border-slate-400 focus:ring-slate-300';
</script>

<template>
    <Head :title="isEditing ? `Edit ${assessment.title}` : 'New assessment'" />

    <AuthenticatedLayout :theme="habitat.type">
        <template #header>
            <PageHeader
                :title="isEditing ? `Edit ${assessment.title}` : 'New assessment'"
                :eyebrow="habitat.name"
                :back-href="isEditing ? habitatRoute(habitat, 'assessments.show', { assessment: assessment.id }) : habitatRoute(habitat, 'show')"
                back-label="Back"
            />
        </template>

        <div class="mx-auto max-w-3xl px-4 pt-6 sm:px-6 lg:px-8">
            <form class="space-y-6 rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800 sm:p-8" @submit.prevent="submit">
                <FormField label="Title" for="title" :error="form.errors.title" hint="For example: Mathematics Quiz 1">
                    <input id="title" v-model="form.title" type="text" required maxlength="150" :class="inputClass" />
                </FormField>

                <FormField label="Type" :error="form.errors.type">
                    <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Assessment type">
                        <button
                            v-for="type in types"
                            :key="type.value"
                            type="button"
                            role="radio"
                            :aria-checked="form.type === type.value"
                            class="rounded-full px-4 py-2 text-sm font-semibold transition"
                            :class="form.type === type.value ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'"
                            @click="form.type = type.value"
                        >
                            {{ type.label }}
                        </button>
                    </div>
                </FormField>

                <FormField v-if="form.type === 'custom'" label="Custom type name" for="custom_type" :error="form.errors.custom_type">
                    <input id="custom_type" v-model="form.custom_type" type="text" maxlength="60" placeholder="e.g. Recitation" :class="inputClass" />
                </FormField>

                <div class="grid gap-6 sm:grid-cols-2">
                    <FormField label="Date" for="assessed_on" :error="form.errors.assessed_on">
                        <input id="assessed_on" v-model="form.assessed_on" type="date" required :class="inputClass" />
                    </FormField>
                    <FormField label="Total possible score" for="total_score" :error="form.errors.total_score">
                        <input id="total_score" v-model="form.total_score" type="number" min="1" max="10000" step="0.01" required :class="inputClass" />
                    </FormField>
                </div>
                <p v-if="totalChanged" class="rounded-xl bg-amber-50 dark:bg-amber-950/40 px-3 py-2 text-xs font-medium text-amber-800 dark:text-amber-200">
                    Changing the total recalculates every percentage and the growth it gives. Scores above the new total are capped at the total.
                </p>

                <FormField label="Description" for="description" :error="form.errors.description" optional>
                    <textarea id="description" v-model="form.description" rows="3" maxlength="1000" :class="inputClass" />
                </FormField>

                <div class="flex justify-end gap-2 border-t border-slate-100 dark:border-slate-800 pt-6">
                    <AppButton variant="secondary" :href="habitatRoute(habitat, 'show')">Cancel</AppButton>
                    <AppButton type="submit" :tone="habitat.type" :disabled="form.processing">
                        {{ isEditing ? 'Save changes' : 'Create & enter scores' }}
                    </AppButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
