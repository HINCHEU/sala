<script setup>
import { Moon, Sun } from 'lucide-vue-next';
import { useTheme } from '@/lib/useTheme';

const { theme, toggleTheme } = useTheme();
</script>

<template>
    <button
        type="button"
        class="inline-flex size-9 items-center justify-center rounded border border-border-strong bg-surface-raised text-ink-muted shadow-xs transition-colors hover:bg-surface-sunken hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary"
        :aria-label="theme === 'dark' ? $t('app.light_mode') : $t('app.dark_mode')"
        :title="theme === 'dark' ? $t('app.light_mode') : $t('app.dark_mode')"
        @click="toggleTheme"
    >
        <Sun
            v-if="theme === 'dark'"
            :size="18"
            :stroke-width="1.75"
            class="text-amber-400 transition-transform duration-300 hover:rotate-45"
            aria-hidden="true"
        />
        <Moon
            v-else
            :size="18"
            :stroke-width="1.75"
            class="transition-transform duration-300 hover:-rotate-12"
            aria-hidden="true"
        />
    </button>
</template>
