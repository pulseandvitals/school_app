import { usePage } from '@inertiajs/vue3';

/**
 * Visual identity per habitat type. Keep in sync with config/habitats.php.
 */
export const habitatThemes = {
    aquarium: {
        icon: 'Fish',
        gradient: 'from-sky-500 via-cyan-500 to-blue-600',
        soft: 'bg-cyan-50 dark:bg-cyan-950/40',
        softBorder: 'border-cyan-100 ring-cyan-100 dark:ring-cyan-900/60',
        text: 'text-cyan-700 dark:text-cyan-300',
        accent: 'bg-cyan-600 hover:bg-cyan-700 focus-visible:outline-cyan-600',
        ring: 'ring-cyan-500',
        chip: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/50 dark:text-cyan-200',
        bar: 'bg-gradient-to-r from-cyan-400 to-blue-500',
    },
    garden: {
        icon: 'Flower2',
        gradient: 'from-lime-400 via-emerald-500 to-green-600',
        soft: 'bg-emerald-50 dark:bg-emerald-950/40',
        softBorder: 'border-emerald-100 ring-emerald-100 dark:ring-emerald-900/60',
        text: 'text-emerald-700 dark:text-emerald-300',
        accent: 'bg-emerald-600 hover:bg-emerald-700 focus-visible:outline-emerald-600',
        ring: 'ring-emerald-500',
        chip: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200',
        bar: 'bg-gradient-to-r from-lime-400 to-emerald-500',
    },
};

/**
 * Background gradient per scene theme (keys from config/habitats.php).
 */
export const scenePreviews = {
    'coral-reef': 'from-sky-300 via-cyan-500 to-blue-700',
    'deep-ocean': 'from-blue-600 via-indigo-800 to-slate-950',
    'sunny-lagoon': 'from-cyan-100 via-teal-300 to-teal-600',
    'kelp-forest': 'from-teal-300 via-emerald-700 to-slate-900',
    'sunny-meadow': 'from-sky-300 via-sky-100 to-lime-300',
    'spring-blossom': 'from-pink-300 via-rose-100 to-lime-200',
    'golden-sunset': 'from-orange-400 via-amber-200 to-lime-300',
    'zen-garden': 'from-teal-300 via-emerald-100 to-emerald-300',
};

export function themeFor(type) {
    return habitatThemes[type] ?? habitatThemes.aquarium;
}

/**
 * Labels from the server-side habitat registry (shared Inertia prop).
 */
export function habitatMeta(type) {
    return usePage().props.habitatTypes?.[type] ?? {};
}

/**
 * Named route inside a habitat module, e.g. habitatRoute(habitat, 'show').
 */
export function habitatRoute(habitat, name, params = {}) {
    return route(`${habitat.route}.${name}`, { habitat: habitat.id, ...params });
}

/**
 * Visual effects that intensify as a fish or plant grows.
 */
export function stageEffects(stage, maxStage) {
    const ratio = maxStage > 1 ? (stage - 1) / (maxStage - 1) : 1;

    return {
        ratio,
        filter: `saturate(${(0.55 + 0.65 * ratio).toFixed(2)}) brightness(${(0.94 + 0.1 * ratio).toFixed(2)})`,
        glow: ratio >= 0.7,
        sparkle: stage >= maxStage,
    };
}

export function formatPercent(value) {
    return value === null || value === undefined ? '—' : `${Number(value).toFixed(Number(value) % 1 === 0 ? 0 : 1)}%`;
}

export function formatDate(value) {
    if (!value) return '';
    return new Date(value.length === 10 ? `${value}T00:00:00` : value).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

export const performanceStyles = {
    excellent: { label: 'Excellent', chip: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200' },
    good: { label: 'Good', chip: 'bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-200' },
    fair: { label: 'Fair', chip: 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200' },
    needs_improvement: { label: 'Needs improvement', chip: 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-200' },
};

export function performanceFor(average) {
    if (average === null || average === undefined) return null;
    if (average >= 90) return 'excellent';
    if (average >= 75) return 'good';
    if (average >= 60) return 'fair';
    return 'needs_improvement';
}

/**
 * Growth points a percentage earns under the given point bands.
 */
export function pointsForPercentage(bands, percentage) {
    const sorted = [...bands].sort((a, b) => b.min - a.min);
    return sorted.find((band) => percentage >= band.min)?.points ?? 0;
}
