<script setup>
defineProps({
    status: {
        type: String,
        default: 'draft', // draft | submitted | approved | locked | active | warning | info
    },
    label: {
        type: String,
        default: '',
    },
});

const variantClasses = {
    draft: 's-pill-draft',
    open: 's-pill-info',
    submitted: 's-pill-submitted',
    approved: 's-pill-approved',
    locked: 's-pill-locked',
    published: 's-pill-approved',
    returned: 's-pill-danger',
    active: 's-pill-approved',
    info: 's-pill-info',
    warning: 's-pill-locked',
    danger: 's-pill-danger',
};
</script>

<template>
    <span class="s-pill" :class="variantClasses[status] ?? 's-pill-draft'">
        <slot>{{ label }}</slot>
    </span>
</template>
