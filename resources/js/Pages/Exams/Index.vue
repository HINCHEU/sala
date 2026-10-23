<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { AlertTriangle, Award, CheckCircle2, Clock, Plus, Trash2 } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import MetricKpiCard from '@/Components/MetricKpiCard.vue';
import Modal from '@/Components/Modal.vue';
import StatusPill from '@/Components/StatusPill.vue';

const props = defineProps({
    years: { type: Array, required: true },
    yearId: { type: Number, default: null },
    exams: { type: Array, required: true },
});

const page = usePage();
const canManage = computed(() => page.props.can?.manageExams);

const onYearChange = (event) => {
    router.get('/exams', { year: event.target.value }, { preserveState: false });
};

// KPI Metrics
const totalExams = computed(() => props.exams.length);
const openExamsCount = computed(() => props.exams.filter((e) => e.status === 'open').length);
const completedExamsCount = computed(
    () =>
        props.exams.filter((e) => e.status === 'approved' || e.status === 'locked' || e.status === 'published').length,
);

// Delete Exam
const examToDelete = ref(null);
const showDeleteModal = ref(false);
const deleting = ref(false);

const confirmDelete = (exam) => {
    examToDelete.value = exam;
    showDeleteModal.value = true;
};

const deleteExam = () => {
    if (!examToDelete.value) return;
    deleting.value = true;
    router.delete(`/exams/${examToDelete.value.id}`, {
        onFinish: () => {
            deleting.value = false;
            showDeleteModal.value = false;
            examToDelete.value = null;
        },
    });
};
</script>

<template>
    <AdminLayout :title="$t('exams.title')">
        <Head :title="$t('exams.title')" />

        <!-- Header Controls -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <label for="exam-year-select" class="text-sm font-medium text-ink-muted">
                    {{ $t('academic.year') }}:
                </label>
                <select
                    id="exam-year-select"
                    :value="yearId"
                    class="rounded-sm border border-border bg-surface-raised px-3 py-1.5 text-sm font-medium shadow-xs focus:border-primary focus:ring-1 focus:ring-primary"
                    @change="onYearChange"
                >
                    <option v-for="y in years" :key="y.id" :value="y.id">
                        {{ y.name }} {{ y.is_current ? `(${$t('academic.current')})` : '' }}
                    </option>
                </select>
            </div>

            <Link v-if="canManage" :href="`/exams/create?year=${yearId}`" class="s-btn s-btn-primary">
                <Plus class="size-4" :stroke-width="2" />
                {{ $t('exams.create') }}
            </Link>
        </div>

        <!-- Metric KPI Cards -->
        <div v-if="exams.length > 0" class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <MetricKpiCard :title="$t('exams.title')" :total="totalExams" :icon="Award" />
            <MetricKpiCard :title="$t('exams.statuses.open')" :total="openExamsCount" :icon="Clock" />
            <MetricKpiCard :title="$t('exams.statuses.approved')" :total="completedExamsCount" :icon="CheckCircle2" />
        </div>

        <!-- Empty State -->
        <div v-if="exams.length === 0" class="rounded-lg border border-border bg-surface-raised p-8">
            <EmptyState :icon="Award" :title="$t('exams.empty_title')" :text="$t('exams.empty_text')">
                <div v-if="canManage" class="mt-4">
                    <Link :href="`/exams/create?year=${yearId}`" class="s-btn s-btn-primary">
                        <Plus class="size-4" :stroke-width="2" />
                        {{ $t('exams.create') }}
                    </Link>
                </div>
            </EmptyState>
        </div>

        <!-- Exams Table -->
        <div v-else class="overflow-hidden rounded-lg border border-border bg-surface-raised shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead
                        class="border-b border-border bg-surface-sunken/60 text-xs font-semibold uppercase tracking-wider text-ink-muted"
                    >
                        <tr>
                            <th class="px-4 py-3">{{ $t('exams.name') }}</th>
                            <th class="px-4 py-3">{{ $t('exams.type') }}</th>
                            <th class="px-4 py-3">{{ $t('exams.dates') }}</th>
                            <th class="px-4 py-3">{{ $t('academic.tabs.classrooms') }}</th>
                            <th class="px-4 py-3">{{ $t('exams.progress') }}</th>
                            <th class="px-4 py-3">{{ $t('people.status') }}</th>
                            <th class="px-4 py-3 text-right">{{ $t('academic.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="exam in exams" :key="exam.id" class="transition-colors hover:bg-surface-sunken/40">
                            <td class="px-4 py-3 font-medium text-ink">
                                <Link :href="`/exams/${exam.id}`" class="font-semibold text-primary hover:underline">
                                    {{ exam.name }}
                                </Link>
                                <div v-if="exam.term" class="text-xs text-ink-muted">
                                    {{ page.props.locale === 'en' ? exam.term.name_en : exam.term.name_km }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-ink-muted">
                                <span class="rounded bg-surface-sunken px-2 py-0.5 text-xs font-medium text-ink">
                                    {{ $t(`exams.types.${exam.type}`) }}
                                    <template v-if="exam.month"> ({{ $t(`exams.months.${exam.month}`) }}) </template>
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap font-mono text-xs text-ink">
                                <span>{{ exam.start_date }}</span>
                                <span class="mx-1 text-ink-muted">→</span>
                                <span>{{ exam.end_date }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-ink">
                                {{ exam.classrooms_count }} {{ $t('academic.tabs.classrooms') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="w-36">
                                    <div class="flex items-center justify-between text-xs font-mono text-ink-muted">
                                        <span>{{ exam.subjects_approved }}/{{ exam.subjects_total }}</span>
                                        <span>
                                            {{
                                                exam.subjects_total > 0
                                                    ? Math.round((exam.subjects_approved / exam.subjects_total) * 100)
                                                    : 0
                                            }}%
                                        </span>
                                    </div>
                                    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-surface-sunken">
                                        <div
                                            class="h-full bg-success transition-all duration-300"
                                            :style="{
                                                width: `${
                                                    exam.subjects_total > 0
                                                        ? (exam.subjects_approved / exam.subjects_total) * 100
                                                        : 0
                                                }%`,
                                            }"
                                        />
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <StatusPill :status="exam.status" :label="$t(`exams.statuses.${exam.status}`)" />
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-2">
                                    <Link :href="`/exams/${exam.id}`" class="s-btn s-btn-compact">
                                        {{ $t('academic.actions') }}
                                    </Link>
                                    <Link
                                        v-if="canManage && exam.status !== 'locked' && exam.status !== 'published'"
                                        :href="`/exams/${exam.id}/edit`"
                                        class="s-btn s-btn-compact text-ink-muted hover:text-ink"
                                    >
                                        {{ $t('academic.edit') }}
                                    </Link>
                                    <button
                                        v-if="canManage && exam.status === 'draft'"
                                        type="button"
                                        class="s-btn s-btn-compact s-btn-danger text-white"
                                        @click="confirmDelete(exam)"
                                    >
                                        <Trash2 class="size-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Delete Modal -->
        <Modal :show="showDeleteModal" max-width="md" @close="showDeleteModal = false">
            <div class="p-6">
                <div class="flex items-center gap-3 text-danger">
                    <AlertTriangle class="size-6 shrink-0" />
                    <h3 class="text-lg font-bold text-ink">
                        {{ $t('exams.confirm_delete', { name: examToDelete?.name }) }}
                    </h3>
                </div>
                <p class="mt-3 text-sm text-ink-muted">
                    {{ $t('exams.confirm_delete_text') }}
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="s-btn" :disabled="deleting" @click="showDeleteModal = false">
                        {{ $t('app.cancel') }}
                    </button>
                    <button type="button" class="s-btn s-btn-danger" :disabled="deleting" @click="deleteExam">
                        {{ deleting ? $t('app.saving') : $t('academic.delete') }}
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
