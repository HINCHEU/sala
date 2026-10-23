<script setup>
// Label + control + hint + error. The default slot receives the ids to wire aria attributes.
const props = defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
});

const describedBy = [props.hint && `${props.id}-hint`, `${props.id}-error`].filter(Boolean).join(' ');
</script>

<template>
    <div>
        <label :for="id" class="mb-1 block text-xs font-semibold text-ink">{{ label }}</label>
        <slot :describedby="describedBy" :invalid="!!error" />
        <p v-if="hint" :id="`${id}-hint`" class="mt-1 text-xs leading-5 text-ink-muted">{{ hint }}</p>
        <p v-if="error" :id="`${id}-error`" class="mt-1 text-xs leading-5 font-medium text-danger">{{ error }}</p>
    </div>
</template>
