<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    Award,
    BookUser,
    Building2,
    GraduationCap,
    House,
    LogOut,
    Menu,
    Settings,
    ShieldCheck,
    Users,
    X,
} from 'lucide-vue-next';
import SchoolBrand from '@/Components/SchoolBrand.vue';
import SchoolSwitcher from '@/Components/SchoolSwitcher.vue';
import LocaleSwitcher from '@/Components/LocaleSwitcher.vue';
import ThemeSwitcher from '@/Components/ThemeSwitcher.vue';
import KhmerPattern from '@/Components/KhmerPattern.vue';
import { useAppShell } from '@/lib/useAppShell';

// Staff layout: sidebar on desktop, drawer on phones.
defineProps({ title: { type: String, required: true } });

const page = useAppShell();
const open = ref(false);

const nav = computed(() =>
    [
        { href: '/', label: 'app.dashboard', icon: House, show: true },
        {
            href: '/academic/years',
            match: '/academic',
            label: 'academic.title',
            icon: GraduationCap,
            show: page.props.can.manageAcademic,
        },
        {
            href: '/people/students',
            match: '/people',
            label: 'people.title',
            icon: BookUser,
            show: page.props.can.managePeople,
        },
        {
            href: '/exams',
            match: '/exams',
            label: 'exams.title',
            icon: Award,
            show: page.props.can.manageExams,
        },
        { href: '/members', label: 'app.members', icon: Users, show: page.props.can.manageMembers },
        { href: '/settings', label: 'app.settings', icon: Settings, show: page.props.can.manageSettings },
        { href: '/platform/schools', label: 'app.platform_schools', icon: Building2, show: page.props.can.platform },
        { href: '/security', label: 'app.security', icon: ShieldCheck, show: true },
    ].filter((item) => item.show),
);

const isActive = (item) => (item.href === '/' ? page.url === '/' : page.url.startsWith(item.match ?? item.href));
</script>

<template>
    <Head :title="title" />
    <div class="relative min-h-screen bg-surface lg:flex">
        <!-- Sovereign Khmer Kbach Tapestry Watermark across full layout background -->
        <KhmerPattern variant="subtle" class="text-primary" opacity="0.12" />

        <!-- Phone top bar with Khmer gold ribbon -->
        <header class="border-b border-border bg-surface-raised lg:hidden">
            <KhmerPattern variant="ribbon" class="opacity-75" />
            <div class="flex items-center justify-between gap-2 px-4 py-2">
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="s-btn s-btn-ghost px-2"
                        :aria-label="$t('app.menu')"
                        :aria-expanded="open"
                        aria-controls="sidebar"
                        @click="open = true"
                    >
                        <Menu :size="20" :stroke-width="1.75" />
                    </button>
                    <SchoolBrand />
                </div>
                <ThemeSwitcher />
            </div>
        </header>

        <div v-if="open" class="fixed inset-0 z-30 bg-black/40 lg:hidden" @click="open = false" />

        <aside
            id="sidebar"
            class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-border bg-surface-sunken p-4 transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
            :class="open ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="mb-4 flex items-center justify-between gap-2">
                <SchoolBrand />
                <button
                    type="button"
                    class="s-btn s-btn-ghost px-2 lg:hidden"
                    :aria-label="$t('app.close')"
                    @click="open = false"
                >
                    <X :size="20" :stroke-width="1.75" />
                </button>
            </div>

            <!-- Subtle Khmer sovereign gold trim in sidebar -->
            <KhmerPattern variant="ribbon" class="mb-4 opacity-50" />

            <nav class="flex-1 space-y-1">
                <Link
                    v-for="item in nav"
                    :key="item.href"
                    :href="item.href"
                    class="flex min-h-10 items-center gap-3 rounded px-3 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary"
                    :class="
                        isActive(item)
                            ? 'bg-primary text-on-primary font-semibold shadow-xs'
                            : 'text-ink-muted hover:bg-surface-raised hover:text-ink'
                    "
                    :aria-current="isActive(item) ? 'page' : undefined"
                    @click="open = false"
                >
                    <component :is="item.icon" :size="18" :stroke-width="1.75" aria-hidden="true" />
                    {{ $t(item.label) }}
                </Link>
            </nav>

            <div class="space-y-3 border-t border-border pt-4">
                <SchoolSwitcher />
                <div class="flex items-center justify-between gap-2">
                    <LocaleSwitcher />
                    <ThemeSwitcher />
                </div>
                <p class="truncate text-xs font-medium text-ink-muted">{{ page.props.auth.user.name }}</p>
                <Link href="/logout" method="post" as="button" class="s-btn w-full">
                    <LogOut :size="18" :stroke-width="1.75" aria-hidden="true" />
                    {{ $t('app.sign_out') }}
                </Link>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="relative z-10 mx-auto w-full max-w-6xl flex-1 px-4 py-6 lg:px-8 lg:py-8">
            <div class="relative z-10">
                <h1
                    class="mb-6 font-headline text-[22px] leading-8 font-bold tracking-tight text-ink lg:text-[28px] lg:leading-9"
                >
                    {{ title }}
                </h1>
                <slot />
            </div>
        </main>
    </div>
</template>
