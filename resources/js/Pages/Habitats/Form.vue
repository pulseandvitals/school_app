<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormField from '@/Components/UI/FormField.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { habitatMeta, habitatRoute, scenePreviews } from '@/lib/habitat';

const props = defineProps({
    habitatType: { type: String, required: true },
    habitat: { type: Object, default: null },
    growthRules: { type: Array, required: true },
});

const meta = computed(() => habitatMeta(props.habitatType));
const isEditing = computed(() => !!props.habitat);
const singular = computed(() => meta.value.singular);

const form = useForm({
    name: props.habitat?.name ?? '',
    description: props.habitat?.description ?? '',
    theme: props.habitat?.theme ?? Object.keys(meta.value.themes)[0],
    growth_rule_id: props.habitat?.growth_rule_id ?? null,
});

const ruleChanged = computed(() => isEditing.value && form.growth_rule_id !== props.habitat.growth_rule_id);

function submit() {
    if (isEditing.value) {
        form.put(habitatRoute(props.habitat, 'update'));
    } else {
        form.post(route(`${meta.value.route}.store`));
    }
}
</script>

<template>
    <Head :title="isEditing ? `Edit ${habitat.name}` : `New ${singular}`" />

    <AuthenticatedLayout :theme="habitatType">
        <template #header>
            <PageHeader
                :title="isEditing ? `Edit ${habitat.name}` : `Create a new ${singular.toLowerCase()}`"
                :back-href="isEditing ? habitatRoute(habitat, 'show') : route(`${meta.route}.index`)"
                :back-label="isEditing ? `Back to ${habitat.name}` : `All ${meta.plural.toLowerCase()}`"
            />
        </template>

        <div class="mx-auto max-w-3xl px-4 pt-6 sm:px-6 lg:px-8">
            <form class="space-y-6 rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800 sm:p-8" @submit.prevent="submit">
                <FormField label="Name" for="name" :error="form.errors.name" hint="For example: Grade 3 - Section A">
                    <input id="name" v-model="form.name" type="text" required maxlength="120" class="w-full rounded-xl border-slate-200 dark:border-slate-700 focus:border-slate-400 focus:ring-slate-300" />
                </FormField>

                <FormField label="Description" for="description" :error="form.errors.description" optional>
                    <textarea id="description" v-model="form.description" rows="3" maxlength="1000" class="w-full rounded-xl border-slate-200 dark:border-slate-700 focus:border-slate-400 focus:ring-slate-300" />
                </FormField>

                <FormField label="Theme" :error="form.errors.theme">
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4" role="radiogroup" aria-label="Theme">
                        <button
                            v-for="(label, key) in meta.themes"
                            :key="key"
                            type="button"
                            role="radio"
                            :aria-checked="form.theme === key"
                            class="relative overflow-hidden rounded-2xl text-left ring-2 transition focus:outline-none focus-visible:ring-4"
                            :class="form.theme === key ? 'ring-slate-900 dark:ring-slate-100' : 'ring-transparent hover:ring-slate-300'"
                            @click="form.theme = key"
                        >
                            <div class="h-20 bg-gradient-to-b" :class="scenePreviews[key]" />
                            <span class="block bg-white dark:bg-slate-900 px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200">{{ label }}</span>
                            <span v-if="form.theme === key" class="absolute right-2 top-2 flex h-5 w-5 items-center justify-center rounded-full bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900">
                                <Check class="h-3 w-3" stroke-width="3" />
                            </span>
                        </button>
                    </div>
                </FormField>

                <FormField
                    label="Growth rules"
                    for="growth_rule_id"
                    :error="form.errors.growth_rule_id"
                    hint="Decides how many points each score earns and when a stage is reached. Manage rules in Growth Rules."
                >
                    <select id="growth_rule_id" v-model="form.growth_rule_id" class="w-full rounded-xl border-slate-200 dark:border-slate-700 focus:border-slate-400 focus:ring-slate-300">
                        <option v-for="rule in growthRules" :key="rule.id ?? 'default'" :value="rule.id">{{ rule.name }}</option>
                    </select>
                    <p v-if="ruleChanged" class="mt-2 rounded-xl bg-amber-50 dark:bg-amber-950/40 px-3 py-2 text-xs font-medium text-amber-800 dark:text-amber-200">
                        Saving will recalculate every student's growth with the new rules. Scores stay the same.
                    </p>
                </FormField>

                <div class="flex justify-end gap-2 border-t border-slate-100 dark:border-slate-800 pt-6">
                    <AppButton variant="secondary" :href="isEditing ? habitatRoute(habitat, 'show') : route(`${meta.route}.index`)">Cancel</AppButton>
                    <AppButton type="submit" :tone="habitatType" :disabled="form.processing">
                        {{ isEditing ? 'Save changes' : `Create ${singular.toLowerCase()}` }}
                    </AppButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
