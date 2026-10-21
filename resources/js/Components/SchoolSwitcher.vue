<script setup>
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();

const change = (event) => router.post('/schools/switch', { school_id: event.target.value });
</script>

<template>
    <div v-if="page.props.schools.length > 1">
        <label for="school-switcher" class="sr-only">{{ $t('app.switch_school') }}</label>
        <select id="school-switcher" class="s-input max-w-56 text-sm" :value="page.props.school?.id" @change="change">
            <option v-for="school in page.props.schools" :key="school.id" :value="school.id">{{ school.name }}</option>
        </select>
    </div>
</template>
