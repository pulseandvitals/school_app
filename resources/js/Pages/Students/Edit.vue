<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Camera, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormField from '@/Components/UI/FormField.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import StudentAvatar from '@/Components/StudentAvatar.vue';

const props = defineProps({
    student: { type: Object, required: true },
});

const photoPreview = ref(props.student.photo_url);

const form = useForm({
    name: props.student.name,
    student_number: props.student.student_number ?? '',
    photo: null,
    remove_photo: false,
});

function onPhoto(event) {
    const file = event.target.files[0];
    form.photo = file ?? null;
    form.remove_photo = false;
    photoPreview.value = file ? URL.createObjectURL(file) : null;
}

function removePhoto() {
    form.photo = null;
    form.remove_photo = true;
    photoPreview.value = null;
}

function submit() {
    form.post(route('students.update', props.student.id), { forceFormData: true });
}
</script>

<template>
    <Head :title="`Edit ${student.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="`Edit ${student.name}`" :back-href="route('students.index')" back-label="All students" />
        </template>

        <div class="mx-auto max-w-2xl px-4 pt-6 sm:px-6 lg:px-8">
            <form class="grid gap-6 rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800 sm:grid-cols-[auto_1fr] sm:p-8" @submit.prevent="submit">
                <div class="flex flex-col items-center gap-2">
                    <StudentAvatar :name="form.name || '?'" :photo-url="photoPreview" size="lg" />
                    <label class="inline-flex cursor-pointer items-center gap-1 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                        <Camera class="h-3.5 w-3.5" /> {{ photoPreview ? 'Change' : 'Add photo' }}
                        <input type="file" accept="image/*" class="sr-only" @change="onPhoto" />
                    </label>
                    <button v-if="photoPreview" type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600" @click="removePhoto">
                        <Trash2 class="h-3.5 w-3.5" /> Remove
                    </button>
                </div>
                <div class="space-y-4">
                    <FormField label="Full name" for="name" :error="form.errors.name">
                        <input id="name" v-model="form.name" type="text" required maxlength="120" class="w-full rounded-xl border-slate-200 dark:border-slate-700 focus:border-slate-400 focus:ring-slate-300" />
                    </FormField>
                    <FormField label="Student number" for="student_number" :error="form.errors.student_number" optional>
                        <input id="student_number" v-model="form.student_number" type="text" maxlength="64" class="w-full rounded-xl border-slate-200 dark:border-slate-700 focus:border-slate-400 focus:ring-slate-300" />
                    </FormField>
                    <p v-if="form.errors.photo" class="text-sm text-rose-600">{{ form.errors.photo }}</p>
                    <div class="flex justify-end gap-2 pt-2">
                        <AppButton variant="secondary" :href="route('students.index')">Cancel</AppButton>
                        <AppButton type="submit" :disabled="form.processing">Save</AppButton>
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
