import { ref } from 'vue';

const STORAGE_KEY = 'sala-theme';

// Module-level reactive singleton
const theme = ref('light');

function getStoredTheme() {
    try {
        const val = localStorage.getItem(STORAGE_KEY);
        if (val === 'light' || val === 'dark') return val;
    } catch {
        // localStorage unavailable (e.g. private mode quota)
    }
    return null;
}

function getSystemTheme() {
    if (typeof window !== 'undefined' && window.matchMedia) {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    return 'light';
}

function applyTheme(nextTheme, save = true) {
    theme.value = nextTheme;
    if (typeof document !== 'undefined') {
        document.documentElement.setAttribute('data-theme', nextTheme);
    }
    if (save) {
        try {
            localStorage.setItem(STORAGE_KEY, nextTheme);
        } catch {
            // Ignore storage errors
        }
    }
}

export function initTheme() {
    if (typeof document === 'undefined') return;
    const currentDom = document.documentElement.getAttribute('data-theme');
    const resolved =
        (currentDom === 'dark' || currentDom === 'light' ? currentDom : null) || getStoredTheme() || getSystemTheme();
    applyTheme(resolved, false);
}

// Initialise immediately in browser environment
if (typeof window !== 'undefined') {
    initTheme();

    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!getStoredTheme()) {
                applyTheme(e.matches ? 'dark' : 'light', false);
            }
        });
    }
}

export function useTheme() {
    function toggleTheme() {
        const next = theme.value === 'dark' ? 'light' : 'dark';
        applyTheme(next, true);
    }

    function setTheme(next) {
        if (next === 'light' || next === 'dark') {
            applyTheme(next, true);
        }
    }

    return {
        theme,
        toggleTheme,
        setTheme,
    };
}
