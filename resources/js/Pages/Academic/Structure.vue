<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { Sparkles } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AcademicTabs from '@/Components/AcademicTabs.vue';
import CrudTable from '@/Components/CrudTable.vue';
import SButton from '@/Components/SButton.vue';

const props = defineProps({
    grades: { type: Array, required: true },
    tracks: { type: Array, required: true },
    subjects: { type: Array, required: true },
    rooms: { type: Array, required: true },
    stages: { type: Array, required: true },
    categories: { type: Array, required: true },
});

const names = () => [
    { key: 'name_km', label: trans('academic.name_km') },
    { key: 'name_en', label: trans('academic.name_en') },
];

const gradeColumns = computed(() => [
    { key: 'number', label: trans('academic.number'), type: 'number', width: 'w-24' },
    ...names(),
    {
        key: 'stage',
        label: trans('academic.stage'),
        type: 'select',
        options: props.stages.map((s) => ({ value: s, label: trans(`academic.stages.${s}`) })),
    },
    { key: 'has_tracks', label: trans('academic.has_tracks'), type: 'checkbox' },
]);

const trackColumns = computed(() => [{ key: 'code', label: trans('academic.code'), width: 'w-28' }, ...names()]);

const subjectColumns = computed(() => [
    { key: 'code', label: trans('academic.code'), width: 'w-28' },
    ...names(),
    {
        key: 'category',
        label: trans('academic.category'),
        type: 'select',
        options: props.categories.map((c) => ({ value: c, label: trans(`academic.categories.${c}`) })),
    },
]);

const roomColumns = computed(() => [
    { key: 'name', label: trans('academic.room') },
    { key: 'building', label: trans('academic.building') },
    { key: 'capacity', label: trans('academic.capacity'), type: 'number', width: 'w-28' },
]);

const applyTemplate = () => router.post('/academic/structure/moeys-template', {}, { preserveScroll: true });
</script>

<template>
    <AdminLayout :title="$t('academic.title')">
        <AcademicTabs />
        <div class="space-y-6">
            <div
                class="flex flex-wrap items-center gap-4 rounded-md border border-border bg-surface-raised p-4 shadow-sm"
            >
                <p class="min-w-0 flex-1 text-sm leading-6 text-ink-muted">{{ $t('academic.template_text') }}</p>
                <SButton @click="applyTemplate">
                    <Sparkles :size="20" :stroke-width="1.75" aria-hidden="true" />
                    {{ $t('academic.template') }}
                </SButton>
            </div>

            <CrudTable
                :title="$t('academic.grades')"
                :rows="grades"
                :columns="gradeColumns"
                base-url="/academic/grades"
                name-key="name_km"
                :defaults="{ stage: 'primary', has_tracks: false }"
            />
            <CrudTable
                :title="$t('academic.subjects')"
                :rows="subjects"
                :columns="subjectColumns"
                base-url="/academic/subjects"
                name-key="name_km"
                :defaults="{ category: 'other' }"
            />
            <CrudTable
                :title="$t('academic.tracks')"
                :rows="tracks"
                :columns="trackColumns"
                base-url="/academic/tracks"
                name-key="name_km"
            />
            <CrudTable :title="$t('academic.rooms')" :rows="rooms" :columns="roomColumns" base-url="/academic/rooms" />
        </div>
    </AdminLayout>
</template>
