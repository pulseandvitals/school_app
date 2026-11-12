<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Camera, Search, Trash2, UserPlus, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormField from '@/Components/UI/FormField.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import GameAssetImage from '@/Components/Game/GameAssetImage.vue';
import GameAssetSelector from '@/Components/Game/GameAssetSelector.vue';
import StudentAvatar from '@/Components/StudentAvatar.vue';
import { habitatMeta, habitatRoute } from '@/lib/habitat';

const props = defineProps({
    habitat: { type: Object, required: true },
    member: { type: Object, default: null },
    assets: { type: Array, required: true },
    rosterStudents: { type: Array, required: true },
});

const meta = computed(() => habitatMeta(props.habitat.type));
const assetType = computed(() => (props.habitat.type === 'aquarium' ? 'fish' : 'plant'));
const noun = computed(() => meta.value.member_noun);
const isEditing = computed(() => !!props.member);

const mode = ref('new');
const search = ref('');
const photoPreview = ref(props.member?.student.photo_url ?? null);

const form = useForm({
    student_id: null,
    name: props.member?.student.name ?? '',
    student_number: props.member?.student.student_number ?? '',
    photo: null,
    remove_photo: false,
    game_asset_id: props.member?.asset.id ?? null,
});

const selectedAsset = computed(() => props.assets.find((asset) => asset.id === form.game_asset_id) ?? null);
const assetChanged = computed(() => isEditing.value && form.game_asset_id !== props.member.asset.id);

const filteredRoster = computed(() => {
    const term = search.value.trim().toLowerCase();
    return props.rosterStudents.filter((student) =>
        !term || student.name.toLowerCase().includes(term) || (student.student_number ?? '').toLowerCase().includes(term),
    );
});

const previewName = computed(() => {
    if (mode.value === 'roster') {
        return props.rosterStudents.find((student) => student.id === form.student_id)?.name ?? 'Your student';
    }
    return form.name || 'Your student';
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

function setMode(value) {
    mode.value = value;
    form.student_id = null;
    form.clearErrors();
}

function submit() {
    const options = { forceFormData: true, preserveScroll: true };

    if (isEditing.value) {
        form.transform((data) => ({ ...data, student_id: undefined })).post(habitatRoute(props.habitat, 'members.update', { member: props.member.id }), options);
        return;
    }

    form.transform((data) => (mode.value === 'roster'
        ? { student_id: data.student_id, game_asset_id: data.game_asset_id }
        : { ...data, student_id: undefined, remove_photo: undefined }))
        .post(habitatRoute(props.habitat, 'members.store'), options);
}
</script>

<template>
    <Head :title="isEditing ? `Edit ${member.student.name}` : 'Add student'" />

    <AuthenticatedLayout :theme="habitat.type">
        <template #header>
            <PageHeader
                :title="isEditing ? `Edit ${member.student.name}` : `Add a student to ${habitat.name}`"
                :back-href="isEditing ? habitatRoute(habitat, 'members.show', { member: member.id }) : habitatRoute(habitat, 'show')"
                :back-label="isEditing ? 'Back to report' : `Back to ${habitat.name}`"
            />
        </template>

        <form class="mx-auto grid max-w-7xl gap-6 px-4 pt-6 sm:px-6 lg:grid-cols-3 lg:px-8" @submit.prevent="submit">
            <div class="space-y-6 lg:col-span-2">
                <!-- Student -->
                <section class="rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="font-display text-xl font-semibold text-slate-800 dark:text-slate-100">1. Student information</h2>
                        <div v-if="!isEditing && rosterStudents.length" class="flex rounded-full bg-slate-100 dark:bg-slate-800 p-1 text-xs font-bold">
                            <button type="button" class="inline-flex items-center gap-1 rounded-full px-3 py-1.5 transition" :class="mode === 'new' ? 'bg-white dark:bg-slate-900 shadow text-slate-800 dark:text-slate-100' : 'text-slate-500 dark:text-slate-400'" @click="setMode('new')">
                                <UserPlus class="h-3.5 w-3.5" /> New student
                            </button>
                            <button type="button" class="inline-flex items-center gap-1 rounded-full px-3 py-1.5 transition" :class="mode === 'roster' ? 'bg-white dark:bg-slate-900 shadow text-slate-800 dark:text-slate-100' : 'text-slate-500 dark:text-slate-400'" @click="setMode('roster')">
                                <Users class="h-3.5 w-3.5" /> From my roster
                            </button>
                        </div>
                    </div>

                    <div v-if="mode === 'new' || isEditing" class="mt-5 grid gap-5 sm:grid-cols-[auto_1fr]">
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
                                <input id="name" v-model="form.name" type="text" maxlength="120" class="w-full rounded-xl border-slate-200 dark:border-slate-700 focus:border-slate-400 focus:ring-slate-300" />
                            </FormField>
                            <FormField label="Student number" for="student_number" :error="form.errors.student_number" optional>
                                <input id="student_number" v-model="form.student_number" type="text" maxlength="64" class="w-full rounded-xl border-slate-200 dark:border-slate-700 focus:border-slate-400 focus:ring-slate-300" />
                            </FormField>
                            <p v-if="form.errors.photo" class="text-sm text-rose-600">{{ form.errors.photo }}</p>
                        </div>
                    </div>

                    <div v-else class="mt-5">
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
                            <input v-model="search" type="search" placeholder="Search your students" class="w-full rounded-xl border-slate-200 dark:border-slate-700 pl-9 focus:border-slate-400 focus:ring-slate-300" />
                        </div>
                        <ul class="mt-3 max-h-72 space-y-1 overflow-y-auto" role="radiogroup" aria-label="Student">
                            <li v-for="student in filteredRoster" :key="student.id">
                                <button
                                    type="button"
                                    role="radio"
                                    :aria-checked="form.student_id === student.id"
                                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left transition"
                                    :class="form.student_id === student.id ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'hover:bg-slate-50 dark:hover:bg-slate-800/60'"
                                    @click="form.student_id = student.id"
                                >
                                    <StudentAvatar :name="student.name" :photo-url="student.photo_url" size="sm" />
                                    <span class="flex-1 font-semibold">{{ student.name }}</span>
                                    <span v-if="student.student_number" class="text-xs opacity-70">#{{ student.student_number }}</span>
                                </button>
                            </li>
                            <li v-if="!filteredRoster.length" class="p-4 text-center text-sm text-slate-500 dark:text-slate-400">No matching students.</li>
                        </ul>
                        <p v-if="form.errors.student_id || form.errors.name" class="mt-2 text-sm text-rose-600">{{ form.errors.student_id || form.errors.name }}</p>
                    </div>
                </section>

                <!-- Asset -->
                <section class="rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800">
                    <h2 class="font-display text-xl font-semibold text-slate-800 dark:text-slate-100">2. Choose a {{ noun }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        This {{ noun }} belongs to the student and grows as they earn good scores.
                    </p>
                    <div class="mt-5">
                        <GameAssetSelector v-model="form.game_asset_id" :assets="assets" :type="assetType" :label="`Choose a ${noun}`" />
                    </div>
                    <p v-if="form.errors.game_asset_id" class="mt-3 text-sm text-rose-600">{{ form.errors.game_asset_id }}</p>
                </section>
            </div>

            <!-- Preview -->
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="overflow-hidden rounded-3xl bg-white dark:bg-slate-900 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800">
                    <div
                        class="relative flex h-56 items-center justify-center"
                        :class="habitat.type === 'aquarium' ? 'bg-gradient-to-b from-sky-300 to-blue-600' : 'bg-gradient-to-b from-sky-200 via-lime-100 to-emerald-300'"
                    >
                        <template v-if="selectedAsset">
                            <div class="h-32 w-40" :class="habitat.type === 'aquarium' ? 'animate-bob' : 'origin-bottom animate-sway'">
                                <GameAssetImage :asset="selectedAsset" :type="assetType" />
                            </div>
                        </template>
                        <p v-else class="px-6 text-center text-sm font-semibold text-white/90 drop-shadow">Pick a {{ noun }} to see it here</p>
                        <template v-if="habitat.type === 'aquarium'">
                            <span v-for="n in 5" :key="n" class="absolute bottom-0 animate-bubble-rise rounded-full border border-white/70" :style="{ left: `${n * 17}%`, width: `${5 + n}px`, height: `${5 + n}px`, animationDelay: `${n * 0.9}s` }" />
                        </template>
                    </div>
                    <div class="space-y-4 p-6">
                        <div>
                            <p class="font-display text-xl font-semibold text-slate-800 dark:text-slate-100">{{ previewName }}</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ selectedAsset?.name ?? `No ${noun} selected` }}</p>
                        </div>
                        <p v-if="assetChanged" class="rounded-xl bg-sky-50 dark:bg-sky-950/40 px-3 py-2 text-xs font-medium text-sky-800 dark:text-sky-200">
                            Changing the {{ noun }} keeps all scores and growth points.
                        </p>
                        <p v-else-if="!isEditing" class="rounded-xl bg-slate-50 dark:bg-slate-800/60 px-3 py-2 text-xs text-slate-600 dark:text-slate-300">
                            New {{ noun === 'fish' ? 'fish start as babies' : 'plants start as seeds' }} and grow with every good score.
                        </p>
                        <AppButton type="submit" :tone="habitat.type" class="w-full" :disabled="form.processing">
                            {{ isEditing ? 'Save changes' : `Add to ${habitat.name}` }}
                        </AppButton>
                    </div>
                </div>
            </aside>
        </form>
    </AuthenticatedLayout>
</template>
