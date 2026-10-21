<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AcademicTabs from '@/Components/AcademicTabs.vue';
import CrudTable from '@/Components/CrudTable.vue';

defineProps({ years: { type: Array, required: true } });

const yearColumns = computed(() => [
    { key: 'name', label: trans('academic.year_name') },
    { key: 'start_date', label: trans('academic.start_date'), type: 'date' },
    { key: 'end_date', label: trans('academic.end_date'), type: 'date' },
    { key: 'is_current', label: trans('academic.current'), type: 'checkbox', readonly: true },
]);

const termColumns = computed(() => [
    { key: 'name_km', label: trans('academic.name_km') },
    { key: 'name_en', label: trans('academic.name_en') },
    { key: 'start_date', label: trans('academic.start_date'), type: 'date' },
    { key: 'end_date', label: trans('academic.end_date'), type: 'date' },
    { key: 'sort', label: trans('academic.sort'), type: 'number', width: 'w-24' },
]);

const setCurrent = (year) => router.post(`/academic/years/${year.id}/current`, {}, { preserveScroll: true });
</script>

<template>
    <AdminLayout :title="$t('academic.title')">
        <AcademicTabs />
        <div class="space-y-6">
            <CrudTable
                :title="$t('academic.tabs.years')"
                :rows="years"
                :columns="yearColumns"
                base-url="/academic/years"
                :empty-text="$t('academic.years_empty')"
            >
                <template #actions="{ row }">
                    <button
                        v-if="!row.is_current"
                        type="button"
                        class="s-btn s-btn-ghost text-sm"
                        @click="setCurrent(row)"
                    >
                        {{ $t('academic.set_current') }}
                    </button>
                </template>
            </CrudTable>

            <!-- New terms post under their year; existing ones are edited at /academic/terms/{id}. -->
            <CrudTable
                v-for="year in years"
                :key="year.id"
                :title="`${$t('academic.terms')} · ${year.name}`"
                :rows="year.terms"
                :columns="termColumns"
                :base-url="`/academic/years/${year.id}/terms`"
                item-url="/academic/terms"
                name-key="name_km"
            />
        </div>
    </AdminLayout>
</template>
