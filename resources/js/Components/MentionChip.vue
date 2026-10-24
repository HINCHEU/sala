<script setup>
// និទ្ទេស as a coloured chip; same tones as the grading scale editor.
defineProps({ mention: { type: String, default: null } });

const tone = {
    excellent: 'bg-gold text-on-gold',
    very_good: 'bg-success-soft text-success',
    good: 'bg-primary-soft text-primary',
    fairly_good: 'bg-surface-sunken text-ink',
    average: 'bg-warning-soft text-warning',
    weak: 'bg-danger-soft text-danger',
};
</script>

<template>
    <span
        v-if="mention"
        class="inline-block rounded-full px-2.5 text-[12px] leading-[22px] font-medium whitespace-nowrap"
        :class="tone[mention]"
    >
        {{ $t(`results.mentions.${mention}`) }}
    </span>
    <span v-else class="text-ink-muted">—</span>
</template>
