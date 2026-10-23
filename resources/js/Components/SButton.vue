<script setup>
defineProps({
    variant: { type: String, default: 'secondary' }, // primary | secondary | honorary | danger | ghost
    size: { type: String, default: 'default' }, // default | compact | sm
    type: { type: String, default: 'button' },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :aria-busy="loading"
        class="s-btn"
        :class="[
            variant !== 'secondary' && `s-btn-${variant}`,
            (size === 'compact' || size === 'sm') && 's-btn-compact',
        ]"
    >
        <span v-if="loading" class="s-spin" aria-hidden="true" />
        <slot />
    </button>
</template>
