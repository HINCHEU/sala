<script setup>
import { usePage } from '@inertiajs/vue3';
import { ClipboardList, School } from 'lucide-vue-next';
import { useRoleLayout } from '@/lib/roleLayout';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({ hasSchool: { type: Boolean, required: true } });

const page = usePage();
const layout = useRoleLayout();
</script>

<template>
    <component :is="layout" :title="$t('app.welcome', { name: page.props.auth.user.name })">
        <EmptyState
            v-if="hasSchool"
            :icon="ClipboardList"
            :title="$t('app.dashboard_empty_title')"
            :text="$t('app.dashboard_empty_text')"
        />
        <EmptyState v-else :icon="School" :title="$t('app.no_school_title')" :text="$t('app.no_school_text')" />
    </component>
</template>
