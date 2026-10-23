<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { House, LogOut, ShieldCheck } from 'lucide-vue-next';
import SchoolBrand from '@/Components/SchoolBrand.vue';
import SchoolSwitcher from '@/Components/SchoolSwitcher.vue';
import LocaleSwitcher from '@/Components/LocaleSwitcher.vue';
import KhmerPattern from '@/Components/KhmerPattern.vue';
import { useAppShell } from '@/lib/useAppShell';

// Phone-first layout for teachers (and, until the portal, guardians and students): one column, bottom tabs.
defineProps({ title: { type: String, required: true } });

const page = useAppShell();

const tabs = [
    { href: '/', label: 'app.dashboard', icon: House },
    { href: '/security', label: 'app.security', icon: ShieldCheck },
];
</script>

<template>
    <Head :title="title" />
    <div class="relative min-h-screen bg-surface pb-20">
        <!-- Subtle sovereign watermark pattern -->
        <KhmerPattern variant="subtle" opacity="0.035" />

        <header class="sticky top-0 z-20 border-b border-border bg-surface-raised">
            <!-- Sovereign gold trim ribbon -->
            <KhmerPattern variant="ribbon" class="opacity-75" />
            <div class="mx-auto flex max-w-xl items-center justify-between gap-2 px-4 py-2">
                <SchoolBrand />
                <LocaleSwitcher />
            </div>
        </header>

        <main class="relative z-10 mx-auto max-w-xl px-4 py-6">
            <h1 class="mb-4 text-[22px] leading-9 font-semibold">{{ title }}</h1>
            <div class="mb-4"><SchoolSwitcher /></div>
            <slot />
        </main>

        <nav class="fixed inset-x-0 bottom-0 z-20 border-t border-border bg-surface-raised">
            <div class="mx-auto flex max-w-xl">
                <Link
                    v-for="tab in tabs"
                    :key="tab.href"
                    :href="tab.href"
                    class="flex min-h-14 flex-1 flex-col items-center justify-center text-[13px] leading-[22px]"
                    :class="page.url === tab.href ? 'font-semibold text-primary' : 'text-ink-muted'"
                    :aria-current="page.url === tab.href ? 'page' : undefined"
                >
                    <component :is="tab.icon" :size="20" :stroke-width="1.75" aria-hidden="true" />
                    {{ $t(tab.label) }}
                </Link>
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="flex min-h-14 flex-1 flex-col items-center justify-center text-[13px] leading-[22px] text-ink-muted"
                >
                    <LogOut :size="20" :stroke-width="1.75" aria-hidden="true" />
                    {{ $t('app.sign_out') }}
                </Link>
            </div>
        </nav>
    </div>
</template>
