<script setup>
import { Head } from '@inertiajs/vue3';
import LocaleSwitcher from '@/Components/LocaleSwitcher.vue';
import { useAppShell } from '@/lib/useAppShell';

defineProps({ title: { type: String, required: true } });

const page = useAppShell();
</script>

<template>
    <Head :title="title" />
    <main class="flex min-h-screen flex-col items-center justify-center bg-surface px-4 py-8">
        <div class="w-full max-w-sm">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <p class="font-headline text-[22px] leading-8 font-bold text-primary">{{ $t('app.name') }}</p>
                    <p class="text-xs leading-5 text-ink-muted">{{ $t('app.tagline') }}</p>
                </div>
                <LocaleSwitcher />
            </div>
            <div class="rounded-lg border border-border bg-surface-raised p-6 shadow-sm">
                <h1 class="mb-4 font-headline text-lg leading-7 font-bold text-ink">{{ title }}</h1>
                <p
                    v-if="page.props.flash.status"
                    role="status"
                    class="mb-4 rounded bg-success-soft px-3 py-2 text-xs leading-5 font-medium text-success"
                >
                    {{ page.props.flash.status }}
                </p>
                <slot />
            </div>
        </div>
    </main>
</template>
