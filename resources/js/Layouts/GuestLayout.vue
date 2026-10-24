<script setup>
import { Head } from '@inertiajs/vue3';
import KhmerPattern from '@/Components/KhmerPattern.vue';
import LocaleSwitcher from '@/Components/LocaleSwitcher.vue';
import ThemeSwitcher from '@/Components/ThemeSwitcher.vue';
import { useAppShell } from '@/lib/useAppShell';

defineProps({ title: { type: String, required: true } });

const page = useAppShell();
</script>

<template>
    <Head :title="title" />
    <main
        class="relative flex min-h-screen w-full flex-col items-center justify-center overflow-hidden bg-[#001733] p-4 sm:p-6"
    >
        <!-- Vector Code Khmer Kbach Pattern Background -->
        <KhmerPattern variant="hero" />

        <div class="relative z-10 my-auto w-full max-w-sm sm:max-w-md">
            <!-- Header: Institutional Crest, App Name, Tagline & Locale Switcher -->
            <div class="mb-6 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 via-amber-500 to-amber-600 shadow-lg shadow-amber-950/40 ring-2 ring-amber-300/40"
                    >
                        <svg
                            class="size-6 text-white drop-shadow-xs"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z" />
                            <path d="M6 6h10" />
                            <path d="M6 10h10" />
                            <path d="M6 14h6" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-headline text-2xl font-bold tracking-tight text-white drop-shadow-sm">
                            {{ $t('app.name') }}
                        </p>
                        <p class="text-xs font-medium text-sky-100/80">
                            {{ $t('app.tagline') }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <ThemeSwitcher />
                    <LocaleSwitcher />
                </div>
            </div>

            <!-- Elevated Premium Card with Glassmorphism & Gold Accent Bar -->
            <div
                class="relative overflow-hidden rounded-2xl border border-white/30 bg-surface-raised/95 p-6 shadow-2xl backdrop-blur-xl transition-all sm:p-8 dark:border-white/10 dark:bg-surface-raised/90"
            >
                <!-- Royal Gold Accent Top Bar -->
                <div
                    class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-600 via-amber-400 to-amber-600 shadow-xs"
                    aria-hidden="true"
                />

                <h1 class="mb-4 font-headline text-lg font-bold text-ink sm:text-xl">
                    {{ title }}
                </h1>

                <p
                    v-if="page.props.flash.status"
                    role="status"
                    class="mb-4 rounded-lg bg-success-soft px-3.5 py-2.5 text-xs font-medium leading-5 text-success"
                >
                    {{ page.props.flash.status }}
                </p>

                <slot />
            </div>

            <!-- MoEYS Sovereign Standard Institutional Seal / Footer -->
            <div class="mt-6 text-center text-xs font-medium text-sky-200/70 drop-shadow-xs">សាលា • Sala</div>
        </div>
    </main>
</template>
