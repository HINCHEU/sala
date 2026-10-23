<script setup>
defineProps({
    icon: { type: [Object, Function], required: true },
    title: { type: String, required: true },
    text: { type: String, default: '' },
});
</script>

<template>
    <div class="rounded-lg border border-dashed border-border-strong bg-surface-raised px-4 py-10 text-center">
        <component :is="icon" class="mx-auto mb-3 size-10 text-ink-muted" :stroke-width="1.5" aria-hidden="true" />
        <p class="font-headline text-base leading-6 font-bold text-ink">{{ title }}</p>
        <p v-if="text" class="mx-auto mt-1 max-w-md text-xs leading-5 text-ink-muted">{{ text }}</p>
        <div v-if="$slots.default" class="mt-4"><slot /></div>
    </div>
</template>
