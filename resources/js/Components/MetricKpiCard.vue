<script setup>
defineProps({
    title: { type: String, required: true },
    total: { type: [Number, String], required: true },
    female: { type: [Number, String], default: null },
    icon: { type: [Object, Function], default: null },
    trend: { type: String, default: null }, // e.g. "+5.2%"
    trendUp: { type: Boolean, default: true },
});
</script>

<template>
    <div
        class="relative rounded-lg border border-border bg-surface-raised p-4 shadow-sm transition-shadow hover:shadow-md"
    >
        <div class="flex items-start justify-between gap-2">
            <span class="text-xs font-medium text-ink-muted">{{ title }}</span>
            <component :is="icon" v-if="icon" class="size-4 text-ink-muted" :stroke-width="1.75" aria-hidden="true" />
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="font-mono text-2xl font-bold tracking-tight text-ink tabular-nums">{{ total }}</span>
            <span v-if="female !== null" class="s-kpi-female">
                {{ $t('people.female_split', { count: female }) }}
            </span>
        </div>
        <div
            v-if="trend"
            class="mt-2 flex items-center justify-end text-xs font-mono tabular-nums"
            :class="trendUp ? 'text-success' : 'text-danger'"
        >
            <span>{{ trend }}</span>
        </div>
    </div>
</template>
