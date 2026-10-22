<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { CalendarRange } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AcademicTabs from '@/Components/AcademicTabs.vue';
import CrudTable from '@/Components/CrudTable.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    years: { type: Array, required: true },
    yearId: { type: Number, default: null },
    classrooms: { type: Array, required: true },
    grades: { type: Array, required: true },
    tracks: { type: Array, required: true },
    rooms: { type: Array, required: true },
    teachers: { type: Array, required: true },
    shifts: { type: Array, required: true },
});

const page = usePage();
const name = (row) => (page.props.locale === 'en' ? row.name_en : row.name_km);

const columns = computed(() => [
    { key: 'name', label: trans('academic.classroom_name'), width: 'w-28' },
    {
        key: 'grade_level_id',
        label: trans('academic.grade'),
        type: 'select',
        options: props.grades.map((g) => ({ value: g.id, label: name(g) })),
    },
    {
        key: 'track_id',
        label: trans('academic.track'),
        type: 'select',
        nullable: true,
        options: props.tracks.map((t) => ({ value: t.id, label: name(t) })),
    },
    {
        key: 'shift',
        label: trans('academic.shift'),
        type: 'select',
        options: props.shifts.map((s) => ({ value: s, label: trans(`academic.shifts.${s}`) })),
    },
    {
        key: 'room_id',
        label: trans('academic.room'),
        type: 'select',
        nullable: true,
        options: props.rooms.map((r) => ({ value: r.id, label: r.name })),
    },
    {
        key: 'homeroom_teacher_id',
        label: trans('academic.homeroom_teacher'),
        type: 'select',
        nullable: true,
        options: props.teachers.map((t) => ({ value: t.id, label: t.name_km })),
    },
    { key: 'capacity', label: trans('academic.capacity'), type: 'number', width: 'w-24' },
]);

const changeYear = (event) =>
    router.get('/academic/classrooms', { year: event.target.value }, { preserveState: false });
</script>

<template>
    <AdminLayout :title="$t('academic.title')">
        <AcademicTabs />

        <EmptyState v-if="!yearId" :icon="CalendarRange" :title="$t('academic.no_year')" />

        <div v-else class="space-y-4">
            <div class="max-w-xs">
                <label for="year" class="mb-1 block text-[13px] leading-[22px] font-medium">{{
                    $t('academic.year')
                }}</label>
                <select id="year" class="s-input" :value="yearId" @change="changeYear">
                    <option v-for="year in years" :key="year.id" :value="year.id">{{ year.name }}</option>
                </select>
            </div>

            <CrudTable
                :title="$t('academic.classrooms')"
                :rows="classrooms"
                :columns="columns"
                base-url="/academic/classrooms"
                :defaults="{ academic_year_id: yearId, shift: 'morning', grade_level_id: grades[0]?.id ?? '' }"
            />
        </div>
    </AdminLayout>
</template>
