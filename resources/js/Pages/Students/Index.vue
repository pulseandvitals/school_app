<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Search, Trash2, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import GameAssetImage from '@/Components/Game/GameAssetImage.vue';
import StudentAvatar from '@/Components/StudentAvatar.vue';
import { themeFor } from '@/lib/habitat';

const props = defineProps({
    students: { type: Array, required: true },
});

const search = ref('');
const deleting = ref(null);

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();
    return props.students.filter((student) =>
        !term || student.name.toLowerCase().includes(term) || (student.student_number ?? '').toLowerCase().includes(term),
    );
});

function destroy() {
    router.delete(route('students.destroy', deleting.value.id), {
        preserveScroll: true,
        onFinish: () => (deleting.value = null),
    });
}
</script>

<template>
    <Head title="Students" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                title="My students"
                subtitle="Everyone you teach, across all your aquariums and gardens. Add students from inside an aquarium or garden."
            >
                <template #icon>
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-fuchsia-500 text-white shadow">
                        <Users class="h-6 w-6" />
                    </span>
                </template>
            </PageHeader>
        </template>

        <div class="mx-auto max-w-5xl space-y-4 px-4 pt-6 sm:px-6 lg:px-8">
            <div v-if="students.length" class="relative">
                <Search class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
                <input v-model="search" type="search" placeholder="Search by name or student number" class="w-full rounded-2xl border-slate-200 dark:border-slate-700 py-3 pl-11 shadow-sm focus:border-slate-400 focus:ring-slate-300" />
            </div>

            <ul v-if="filtered.length" class="divide-y divide-slate-100 dark:divide-slate-800 overflow-hidden rounded-3xl bg-white dark:bg-slate-900 shadow-sm ring-1 ring-slate-100 dark:ring-slate-800">
                <li v-for="student in filtered" :key="student.id" class="flex flex-wrap items-center gap-4 px-5 py-4">
                    <StudentAvatar :name="student.name" :photo-url="student.photo_url" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-slate-800 dark:text-slate-100">{{ student.name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ student.student_number ? `#${student.student_number}` : 'No student number' }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="membership in student.memberships"
                            :key="membership.id"
                            :href="route(`${membership.route}.members.show`, { habitat: membership.habitat_id, member: membership.id })"
                            class="inline-flex items-center gap-1.5 rounded-full py-1 pe-3 ps-1 text-xs font-bold transition hover:opacity-80"
                            :class="themeFor(membership.type).chip"
                        >
                            <span class="h-5 w-5 rounded-full bg-white dark:bg-slate-900 p-0.5">
                                <GameAssetImage :asset="{ image_url: membership.image_url, id: membership.id }" :type="membership.type === 'aquarium' ? 'fish' : 'plant'" />
                            </span>
                            {{ membership.habitat_name }} · S{{ membership.stage }}
                        </Link>
                        <span v-if="!student.memberships.length" class="text-xs text-slate-400 dark:text-slate-500">Not in any aquarium or garden</span>
                    </div>
                    <div class="flex gap-1">
                        <AppButton size="sm" variant="ghost" :href="route('students.edit', student.id)" aria-label="Edit">
                            <Pencil class="h-4 w-4" />
                        </AppButton>
                        <AppButton size="sm" variant="ghost" class="text-rose-600 hover:bg-rose-50 dark:!text-rose-400 dark:hover:bg-rose-950/40 hover:text-rose-700 dark:hover:text-rose-300" aria-label="Delete" @click="deleting = student">
                            <Trash2 class="h-4 w-4" />
                        </AppButton>
                    </div>
                </li>
            </ul>

            <p v-else-if="students.length" class="rounded-3xl bg-white dark:bg-slate-900 p-8 text-center text-sm text-slate-500 dark:text-slate-400 ring-1 ring-slate-100 dark:ring-slate-800">No students match "{{ search }}".</p>

            <EmptyState
                v-else
                :icon="Users"
                title="No students yet"
                description="Open an aquarium or garden and use “Add student”. Each student you add appears here."
            >
                <div class="flex flex-wrap justify-center gap-2">
                    <AppButton :href="route('aquariums.index')" tone="aquarium">Go to School of Fishes</AppButton>
                    <AppButton :href="route('gardens.index')" tone="garden">Go to Garden of Hope</AppButton>
                </div>
            </EmptyState>
        </div>

        <ConfirmDialog
            :show="!!deleting"
            :title="`Delete ${deleting?.name}?`"
            message="They will be removed from every aquarium and garden. Their history is kept in the audit log."
            confirm-label="Delete student"
            @close="deleting = null"
            @confirm="destroy"
        />
    </AuthenticatedLayout>
</template>
