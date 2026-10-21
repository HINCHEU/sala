<script setup>
import { Head } from '@inertiajs/vue3';
import LocaleSwitcher from '@/Components/LocaleSwitcher.vue';
import { useAppShell } from '@/lib/useAppShell';

defineProps({ title: { type: String, required: true } });

const page = useAppShell();
</script>

<template>
    <Head :title="title" />
    <main class="flex min-h-screen flex-col items-center justify-center px-4 py-8">
        <div class="w-full max-w-sm">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <p class="text-[22px] leading-9 font-semibold">{{ $t('app.name') }}</p>
                    <p class="text-sm leading-6 text-ink-muted">{{ $t('app.tagline') }}</p>
                </div>
                <LocaleSwitcher />
            </div>
            <div class="rounded-md border border-border bg-surface-raised p-4 shadow-sm sm:p-6">
                <h1 class="mb-4 text-lg leading-[30px] font-semibold">{{ title }}</h1>
                <p
                    v-if="page.props.flash.status"
                    role="status"
                    class="mb-4 rounded-sm bg-success-soft px-3 py-2 text-sm leading-6 text-success"
                >
                    {{ page.props.flash.status }}
                </p>
                <slot />
            </div>
        </div>
    </main>
</template>
