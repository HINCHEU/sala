<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <Head :title="$t('app.dashboard')" />
    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-8">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-[22px] leading-9 font-semibold">
                {{ $t('app.welcome', { name: page.props.auth.user.name }) }}
            </h1>
            <Link
                href="/logout"
                method="post"
                as="button"
                class="min-h-11 rounded-md border border-border-strong px-4 py-2 hover:bg-surface-sunken"
            >
                {{ $t('app.sign_out') }}
            </Link>
        </div>
    </main>
</template>
