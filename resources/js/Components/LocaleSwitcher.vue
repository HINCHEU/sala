<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { loadLanguageAsync } from 'laravel-vue-i18n';

const page = usePage();
const locales = { km: 'ខ្មែរ', en: 'English' };

function change(locale) {
    router.post(
        '/locale',
        { locale },
        {
            preserveScroll: true,
            onSuccess: () => {
                document.documentElement.lang = locale;
                loadLanguageAsync(locale);
            },
        },
    );
}
</script>

<template>
    <div
        role="group"
        :aria-label="$t('app.language')"
        class="inline-flex rounded border border-border-strong bg-surface-raised shadow-xs"
    >
        <button
            v-for="(label, code) in locales"
            :key="code"
            type="button"
            :lang="code"
            :aria-pressed="page.props.locale === code"
            class="min-h-9 px-3 text-xs font-semibold first:rounded-l last:rounded-r transition-colors focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary"
            :class="
                page.props.locale === code
                    ? 'bg-primary text-on-primary'
                    : 'text-ink-muted hover:bg-surface-sunken hover:text-ink'
            "
            @click="change(code)"
        >
            {{ label }}
        </button>
    </div>
</template>
