<script setup>
import { usePage } from '@inertiajs/vue3';

// The school's own logo beside its name; no placeholder mark when there is no logo.
const page = usePage();
</script>

<template>
    <div class="flex min-w-0 items-center gap-2">
        <img
            v-if="page.props.school?.logo_url"
            :src="page.props.school.logo_url"
            alt=""
            class="size-9 shrink-0 rounded-md object-contain"
        />
        <span class="truncate font-bold">{{ page.props.school?.name ?? $t('app.name') }}</span>
    </div>
</template>
