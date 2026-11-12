<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Fish, Flower2, LayoutDashboard, Menu, Settings2, Users, X } from 'lucide-vue-next';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import CelebrationOverlay from '@/Components/CelebrationOverlay.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import FlashToast from '@/Components/FlashToast.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import StudentAvatar from '@/Components/StudentAvatar.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

/**
 * `theme` tints the page background: 'default' | 'aquarium' | 'garden'.
 */
defineProps({
    theme: { type: String, default: 'default' },
});

const showingNavigationDropdown = ref(false);

const links = [
    { name: 'Dashboard', route: 'dashboard', match: 'dashboard', icon: LayoutDashboard, active: 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' },
    { name: 'School of Fishes', route: 'aquariums.index', match: 'aquariums.*', icon: Fish, active: 'bg-cyan-600 text-white' },
    { name: 'Garden of Hope', route: 'gardens.index', match: 'gardens.*', icon: Flower2, active: 'bg-emerald-600 text-white' },
    { name: 'Students', route: 'students.index', match: 'students.*', icon: Users, active: 'bg-violet-600 text-white' },
    { name: 'Growth Rules', route: 'growth-rules.index', match: 'growth-rules.*', icon: Settings2, active: 'bg-amber-500 text-white' },
];

const backgrounds = {
    default: 'bg-gradient-to-br from-slate-50 via-sky-50 to-emerald-50 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950',
    aquarium: 'bg-gradient-to-br from-sky-50 via-cyan-50 to-blue-100 dark:from-slate-950 dark:via-cyan-950/60 dark:to-slate-950',
    garden: 'bg-gradient-to-br from-lime-50 via-emerald-50 to-yellow-50 dark:from-slate-950 dark:via-emerald-950/60 dark:to-slate-950',
};
</script>

<template>
    <div class="min-h-screen" :class="backgrounds[theme] ?? backgrounds.default">
        <nav class="sticky top-0 z-40 border-b border-white/60 bg-white/80 backdrop-blur-lg dark:border-slate-800 dark:bg-slate-900/80">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between gap-4">
                    <Link :href="route('dashboard')" class="flex shrink-0 items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-400 to-emerald-500 text-white shadow">
                            <ApplicationLogo class="h-5 w-5 fill-current" />
                        </span>
                        <span class="hidden font-display text-lg font-semibold leading-tight text-slate-800 dark:text-slate-100 md:block">
                            Fishes <span class="text-slate-400 dark:text-slate-500">&amp;</span> Hope
                        </span>
                    </Link>

                    <div class="hidden flex-1 items-center justify-center gap-1 lg:flex">
                        <Link
                            v-for="link in links"
                            :key="link.route"
                            :href="route(link.route)"
                            class="inline-flex items-center gap-2 rounded-full px-3.5 py-2 text-sm font-semibold transition"
                            :class="route().current(link.match) ? link.active : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'"
                        >
                            <component :is="link.icon" class="h-4 w-4" />
                            {{ link.name }}
                        </Link>
                    </div>

                    <div class="hidden items-center gap-2 lg:flex">
                        <ThemeToggle />
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button type="button" class="flex items-center gap-2 rounded-full py-1 pe-3 ps-1 text-sm font-semibold text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-800">
                                    <StudentAvatar :name="$page.props.auth.user.name" size="sm" />
                                    {{ $page.props.auth.user.name }}
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl p-2 text-slate-500 dark:text-slate-400 transition hover:bg-slate-100 dark:hover:bg-slate-800 lg:hidden"
                        :aria-expanded="showingNavigationDropdown"
                        aria-label="Toggle navigation"
                        @click="showingNavigationDropdown = !showingNavigationDropdown"
                    >
                        <X v-if="showingNavigationDropdown" class="h-6 w-6" />
                        <Menu v-else class="h-6 w-6" />
                    </button>
                </div>
            </div>

            <div v-show="showingNavigationDropdown" class="border-t border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 lg:hidden">
                <div class="space-y-1 px-2 pb-3 pt-2">
                    <Link
                        v-for="link in links"
                        :key="link.route"
                        :href="route(link.route)"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-base font-semibold"
                        :class="route().current(link.match) ? link.active : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/60'"
                    >
                        <component :is="link.icon" class="h-5 w-5" />
                        {{ link.name }}
                    </Link>
                </div>
                <div class="border-t border-slate-100 dark:border-slate-800 pb-2 pt-4">
                    <div class="flex items-center justify-between gap-3 px-4">
                        <div class="min-w-0">
                            <div class="truncate text-base font-medium text-slate-800 dark:text-slate-100">{{ $page.props.auth.user.name }}</div>
                            <div class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">{{ $page.props.auth.user.email }}</div>
                        </div>
                        <ThemeToggle />
                    </div>
                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">Profile</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">Log Out</ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header">
            <div class="mx-auto max-w-7xl px-4 pb-2 pt-8 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main class="pb-16">
            <slot />
        </main>

        <FlashToast />
        <CelebrationOverlay />
    </div>
</template>
