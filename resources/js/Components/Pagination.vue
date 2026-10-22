<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

// Previous / next for a Laravel paginator; long lists are searched, not paged through.
defineProps({ paginator: { type: Object, required: true } });
</script>

<template>
    <nav
        v-if="paginator.last_page > 1"
        :aria-label="$t('app.pagination')"
        class="flex items-center justify-between gap-2 text-sm"
    >
        <Link
            v-if="paginator.prev_page_url"
            :href="paginator.prev_page_url"
            preserve-scroll
            class="s-btn s-btn-ghost"
            rel="prev"
        >
            <ChevronLeft :size="18" :stroke-width="1.75" aria-hidden="true" />
            {{ $t('app.previous') }}
        </Link>
        <span v-else />
        <span class="text-ink-muted tabular-nums">
            {{ $t('app.page_of', { page: paginator.current_page, pages: paginator.last_page }) }}
        </span>
        <Link
            v-if="paginator.next_page_url"
            :href="paginator.next_page_url"
            preserve-scroll
            class="s-btn s-btn-ghost"
            rel="next"
        >
            {{ $t('app.next') }}
            <ChevronRight :size="18" :stroke-width="1.75" aria-hidden="true" />
        </Link>
        <span v-else />
    </nav>
</template>
