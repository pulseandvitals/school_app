import { computed, ref } from 'vue';

/**
 * Light / dark / system colour theme, shared across the app and saved in
 * localStorage. The initial class is set by an inline script in app.blade.php.
 */
const STORAGE_KEY = 'theme';
const media = typeof window !== 'undefined' ? window.matchMedia('(prefers-color-scheme: dark)') : null;

const mode = ref(typeof window !== 'undefined' ? localStorage.getItem(STORAGE_KEY) || 'system' : 'system');
const systemDark = ref(media?.matches ?? false);

media?.addEventListener('change', (event) => {
    systemDark.value = event.matches;
    apply();
});

const isDark = computed(() => mode.value === 'dark' || (mode.value === 'system' && systemDark.value));

function apply() {
    document.documentElement.classList.toggle('dark', isDark.value);
}

function setMode(value) {
    mode.value = value;
    if (value === 'system') {
        localStorage.removeItem(STORAGE_KEY);
    } else {
        localStorage.setItem(STORAGE_KEY, value);
    }
    apply();
}

export function useTheme() {
    return { mode, isDark, setMode };
}
