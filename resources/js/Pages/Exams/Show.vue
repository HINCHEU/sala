<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    Award,
    Calendar,
    Check,
    CheckCircle2,
    Clock,
    CornerDownLeft,
    Edit3,
    FileSpreadsheet,
    Lock,
    Send,
    ShieldAlert,
    Undo2,
} from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import MetricKpiCard from '@/Components/MetricKpiCard.vue';
import Modal from '@/Components/Modal.vue';
import StatusPill from '@/Components/StatusPill.vue';

const props = defineProps({
    exam: { type: Object, required: true },
    classrooms: { type: Array, required: true },
    subjectStatuses: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
    teacherId: { type: Number, default: null },
});

const page = usePage();
const subjectName = (s) => (page.props.locale === 'en' ? s.name_en || s.name_km : s.name_km || s.name_en);
const classroomGrade = (c) => (page.props.locale === 'en' ? c.grade_level?.name_en : c.grade_level?.name_km);

// Lifecycle states
const lifecycleStages = ['draft', 'open', 'approved', 'locked', 'published'];
const currentStageIndex = computed(() => lifecycleStages.indexOf(props.exam.status));

const transitionLoading = ref(false);
const transitionExam = (targetStatus) => {
    transitionLoading.value = true;
    router.post(
        `/exams/${props.exam.id}/transition`,
        { status: targetStatus },
        {
            preserveScroll: true,
            onFinish: () => {
                transitionLoading.value = false;
            },
        },
    );
};

// Filter by Classroom
const selectedClassroomId = ref(null);
const filteredStatuses = computed(() => {
    if (!selectedClassroomId.value) {
        return props.subjectStatuses;
    }
    return props.subjectStatuses.filter((s) => s.classroom_id === selectedClassroomId.value);
});

// KPI Counts
const totalSubjects = computed(() => props.subjectStatuses.length);
const approvedSubjects = computed(() => props.subjectStatuses.filter((s) => s.status === 'approved').length);
const submittedSubjects = computed(() => props.subjectStatuses.filter((s) => s.status === 'submitted').length);
const returnedSubjects = computed(() => props.subjectStatuses.filter((s) => s.status === 'returned').length);
const draftSubjects = computed(() => props.subjectStatuses.filter((s) => s.status === 'draft').length);

// Teacher assignment lookup: (classroom_id, subject_id) -> teacher
const assignmentMap = computed(() => {
    const map = new Map();
    for (const c of props.classrooms) {
        for (const ta of c.teaching_assignments || []) {
            map.set(`${c.id}-${ta.subject_id}`, ta);
        }
    }
    return map;
});

const getAssignedTeacher = (classroomId, subjectId) => {
    return assignmentMap.value.get(`${classroomId}-${subjectId}`);
};

const classroomMap = computed(() => {
    return new Map(props.classrooms.map((c) => [c.id, c]));
});

// Permissions check per subject
const canSubmitSubject = (statusRecord) => {
    if (props.exam.status !== 'open') return false;
    if (statusRecord.status !== 'draft' && statusRecord.status !== 'returned') return false;

    if (props.canManage) return true;
    if (!props.teacherId) return false;

    const assigned = getAssignedTeacher(statusRecord.classroom_id, statusRecord.subject_id);
    return assigned && assigned.teacher_id === props.teacherId;
};

const canApproveSubject = (statusRecord) => {
    if (props.exam.status !== 'open') return false;
    if (statusRecord.status !== 'submitted') return false;

    if (props.canManage) return true;
    if (!props.teacherId) return false;

    const classroom = classroomMap.value.get(statusRecord.classroom_id);
    return classroom && classroom.homeroom_teacher?.id === props.teacherId;
};

// Action: Transition subject status
const subjectActionLoading = ref(null);
const transitionSubject = (statusRecord, targetStatus, reason = null) => {
    subjectActionLoading.value = statusRecord.id;
    router.post(
        `/exams/${props.exam.id}/subjects/${statusRecord.id}/transition`,
        { status: targetStatus, reason },
        {
            preserveScroll: true,
            onFinish: () => {
                subjectActionLoading.value = null;
            },
        },
    );
};

// Return Modal
const showReturnModal = ref(false);
const statusToReturn = ref(null);
const returnReason = ref('');
const returnError = ref('');

const openReturnModal = (statusRecord) => {
    statusToReturn.value = statusRecord;
    returnReason.value = '';
    returnError.value = '';
    showReturnModal.value = true;
};

const submitReturn = () => {
    if (!returnReason.value.trim()) {
        returnError.value = page.props.locale === 'en' ? 'A reason is required.' : 'សូមបញ្ជាក់ពីមូលហេតុ។';
        return;
    }

    if (!statusToReturn.value) return;

    transitionSubject(statusToReturn.value, 'returned', returnReason.value);
    showReturnModal.value = false;
    statusToReturn.value = null;
};
</script>

<template>
    <AdminLayout :title="exam.name">
        <Head :title="exam.name" />

        <!-- Navigation & Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <Link
                :href="`/exams?year=${exam.academic_year?.id}`"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted transition-colors hover:text-ink"
            >
                <ArrowLeft class="size-4" />
                {{ $t('exams.title') }}
            </Link>

            <div class="flex items-center gap-2">
                <Link
                    v-if="canManage && exam.status !== 'locked' && exam.status !== 'published'"
                    :href="`/exams/${exam.id}/edit`"
                    class="s-btn s-btn-compact"
                >
                    <Edit3 class="size-3.5" />
                    {{ $t('academic.edit') }}
                </Link>
            </div>
        </div>

        <!-- Exam Overview Card -->
        <div class="mb-6 rounded-lg border border-border bg-surface-raised p-6 shadow-xs">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-xl font-bold text-ink sm:text-2xl">
                            {{ exam.name }}
                        </h1>
                        <StatusPill :status="exam.status" :label="$t(`exams.statuses.${exam.status}`)" />
                    </div>

                    <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-ink-muted sm:text-sm">
                        <span class="rounded bg-surface-sunken px-2 py-0.5 font-medium text-ink">
                            {{ $t(`exams.types.${exam.type}`) }}
                            <template v-if="exam.month">
                                ({{ $t(`exams.months.${exam.month}`) }})
                            </template>
                        </span>
                        <span>•</span>
                        <span>{{ exam.academic_year?.name }}</span>
                        <template v-if="exam.term">
                            <span>•</span>
                            <span>{{ page.props.locale === 'en' ? exam.term.name_en : exam.term.name_km }}</span>
                        </template>
                        <span>•</span>
                        <span class="font-mono">{{ exam.start_date }} → {{ exam.end_date }}</span>
                    </div>

                    <p v-if="exam.description" class="mt-3 text-sm text-ink-muted">
                        {{ exam.description }}
                    </p>
                </div>
            </div>

            <!-- Lifecycle State Machine Stepper -->
            <div class="mt-6 border-t border-border pt-6">
                <div class="mb-3 text-xs font-semibold uppercase tracking-wider text-ink-muted">
                    {{ $t('people.status') }} / {{ $t('exams.progress') }}
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-1 sm:gap-2">
                        <template v-for="(stage, idx) in lifecycleStages" :key="stage">
                            <div
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition-colors"
                                :class="
                                    idx < currentStageIndex
                                        ? 'bg-success-soft text-success'
                                        : idx === currentStageIndex
                                          ? 'bg-primary text-white shadow-xs'
                                          : 'bg-surface-sunken text-ink-muted'
                                "
                            >
                                <Check v-if="idx < currentStageIndex" class="size-3" />
                                <span>{{ $t(`exams.statuses.${stage}`) }}</span>
                            </div>
                            <span
                                v-if="idx < lifecycleStages.length - 1"
                                class="text-xs text-border-strong"
                            >
                                →
                            </span>
                        </template>
                    </div>

                    <!-- Lifecycle Action Buttons -->
                    <div v-if="canManage" class="flex flex-wrap items-center gap-2">
                        <!-- If Draft: Open -->
                        <button
                            v-if="exam.status === 'draft'"
                            type="button"
                            class="s-btn s-btn-compact s-btn-primary"
                            :disabled="transitionLoading"
                            @click="transitionExam('open')"
                        >
                            <Clock class="size-3.5" />
                            {{ $t('exams.actions.open') }}
                        </button>

                        <!-- If Open: Revert to Draft, Approve, Lock -->
                        <template v-if="exam.status === 'open'">
                            <button
                                type="button"
                                class="s-btn s-btn-compact"
                                :disabled="transitionLoading"
                                @click="transitionExam('draft')"
                            >
                                <Undo2 class="size-3.5" />
                                {{ $t('exams.actions.revert_draft') }}
                            </button>
                            <button
                                type="button"
                                class="s-btn s-btn-compact s-btn-primary"
                                :disabled="transitionLoading"
                                @click="transitionExam('approved')"
                            >
                                <CheckCircle2 class="size-3.5" />
                                {{ $t('exams.actions.approve') }}
                            </button>
                            <button
                                type="button"
                                class="s-btn s-btn-compact s-btn-gold"
                                :disabled="transitionLoading"
                                @click="transitionExam('locked')"
                            >
                                <Lock class="size-3.5" />
                                {{ $t('exams.actions.lock') }}
                            </button>
                        </template>

                        <!-- If Approved: Reopen, Lock -->
                        <template v-if="exam.status === 'approved'">
                            <button
                                type="button"
                                class="s-btn s-btn-compact"
                                :disabled="transitionLoading"
                                @click="transitionExam('open')"
                            >
                                <Undo2 class="size-3.5" />
                                {{ $t('exams.actions.open') }}
                            </button>
                            <button
                                type="button"
                                class="s-btn s-btn-compact s-btn-gold"
                                :disabled="transitionLoading"
                                @click="transitionExam('locked')"
                            >
                                <Lock class="size-3.5" />
                                {{ $t('exams.actions.lock') }}
                            </button>
                        </template>

                        <!-- If Locked: Publish -->
                        <template v-if="exam.status === 'locked'">
                            <button
                                type="button"
                                class="s-btn s-btn-compact s-btn-primary"
                                :disabled="transitionLoading"
                                @click="transitionExam('published')"
                            >
                                <Award class="size-3.5" />
                                {{ $t('exams.actions.publish') }}
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <MetricKpiCard
                :title="$t('academic.subject')"
                :total="totalSubjects"
                :icon="Award"
            />
            <MetricKpiCard
                :title="$t('exams.subject_statuses.approved')"
                :total="approvedSubjects"
                :icon="CheckCircle2"
            />
            <MetricKpiCard
                :title="$t('exams.subject_statuses.submitted')"
                :total="submittedSubjects"
                :icon="Send"
            />
            <MetricKpiCard
                :title="$t('exams.subject_statuses.returned')"
                :total="returnedSubjects"
                :icon="ShieldAlert"
            />
        </div>

        <!-- Subject Scores Tracking Table Card -->
        <div class="rounded-lg border border-border bg-surface-raised shadow-xs">
            <!-- Classroom Pill Filter Header -->
            <div class="border-b border-border bg-surface-sunken/40 px-4 py-3">
                <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-ink-muted">
                    {{ $t('academic.select_classroom') }}
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-sm border px-3 py-1 text-xs font-semibold transition-all"
                        :class="
                            selectedClassroomId === null
                                ? 'border-primary bg-primary text-white shadow-xs'
                                : 'border-border bg-surface-raised text-ink hover:border-border-strong'
                        "
                        @click="selectedClassroomId = null"
                    >
                        <span>{{ $t('exams.select_all') }}</span>
                        <span class="opacity-80">({{ classrooms.length }})</span>
                    </button>

                    <button
                        v-for="c in classrooms"
                        :key="c.id"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-sm border px-3 py-1 text-xs font-medium transition-all"
                        :class="
                            selectedClassroomId === c.id
                                ? 'border-primary bg-primary text-white shadow-xs'
                                : 'border-border bg-surface-raised text-ink hover:border-border-strong'
                        "
                        @click="selectedClassroomId = c.id"
                    >
                        <span class="font-bold">{{ c.name }}</span>
                        <span
                            class="text-[11px] opacity-80"
                            :class="selectedClassroomId === c.id ? 'text-white' : 'text-ink-muted'"
                        >
                            ({{ classroomGrade(c) }})
                        </span>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-surface-sunken/60 text-xs font-semibold uppercase tracking-wider text-ink-muted">
                        <tr>
                            <th class="px-4 py-3">{{ $t('academic.classroom_name') }}</th>
                            <th class="px-4 py-3">{{ $t('academic.subject') }}</th>
                            <th class="px-4 py-3">{{ $t('academic.assign_teacher') }}</th>
                            <th class="px-4 py-3">{{ $t('people.status') }}</th>
                            <th class="px-4 py-3">{{ $t('exams.progress') }}</th>
                            <th class="px-4 py-3 text-right">{{ $t('academic.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-if="filteredStatuses.length === 0"
                            class="text-center text-ink-muted"
                        >
                            <td colspan="6" class="p-8 text-sm">
                                {{ $t('academic.empty') }}
                            </td>
                        </tr>

                        <tr
                            v-for="status in filteredStatuses"
                            :key="status.id"
                            class="transition-colors hover:bg-surface-sunken/40"
                        >
                            <!-- Classroom -->
                            <td class="px-4 py-3 whitespace-nowrap font-medium text-ink">
                                <div>{{ classroomMap.get(status.classroom_id)?.name }}</div>
                                <div class="text-xs text-ink-muted">
                                    {{ classroomGrade(classroomMap.get(status.classroom_id)) }}
                                    • {{ classroomMap.get(status.classroom_id)?.homeroom_teacher?.name_km || $t('academic.unassigned') }}
                                </div>
                            </td>

                            <!-- Subject -->
                            <td class="px-4 py-3 font-medium text-ink">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-semibold text-ink-muted">
                                        {{ status.subject.code }}
                                    </span>
                                    <span>{{ subjectName(status.subject) }}</span>
                                </div>
                            </td>

                            <!-- Teacher -->
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-ink">
                                {{ getAssignedTeacher(status.classroom_id, status.subject_id)?.teacher_name || $t('academic.unassigned') }}
                            </td>

                            <!-- Status Pill -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <StatusPill
                                    :status="status.status"
                                    :label="$t(`exams.subject_statuses.${status.status}`)"
                                />
                            </td>

                            <!-- Audit info / Returned reason note -->
                            <td class="px-4 py-3 text-xs text-ink-muted">
                                <div v-if="status.status === 'submitted' && status.submitted_by">
                                    {{ $t('exams.submitted_by', { name: status.submitted_by, date: status.submitted_at }) }}
                                </div>
                                <div v-else-if="status.status === 'approved' && status.approved_by">
                                    {{ $t('exams.approved_by', { name: status.approved_by, date: status.approved_at }) }}
                                </div>
                                <div v-else-if="status.status === 'returned' && status.returned_reason" class="text-danger">
                                    <span class="font-semibold">{{ $t('exams.return_reason') }}:</span>
                                    "{{ status.returned_reason }}"
                                </div>
                                <div v-else class="text-ink-muted italic">
                                    —
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Submit Button (Teacher / Manage) -->
                                    <button
                                        v-if="canSubmitSubject(status)"
                                        type="button"
                                        class="s-btn s-btn-compact s-btn-primary"
                                        :disabled="subjectActionLoading === status.id"
                                        @click="transitionSubject(status, 'submitted')"
                                    >
                                        <Send class="size-3" />
                                        {{ $t('exams.actions.submit_subject') }}
                                    </button>

                                    <!-- Approve Button (Approver / Manage) -->
                                    <button
                                        v-if="canApproveSubject(status)"
                                        type="button"
                                        class="s-btn s-btn-compact s-btn-primary"
                                        :disabled="subjectActionLoading === status.id"
                                        @click="transitionSubject(status, 'approved')"
                                    >
                                        <Check class="size-3" />
                                        {{ $t('exams.actions.approve_subject') }}
                                    </button>

                                    <!-- Return Button (Approver / Manage) -->
                                    <button
                                        v-if="canApproveSubject(status)"
                                        type="button"
                                        class="s-btn s-btn-compact s-btn-danger text-white"
                                        :disabled="subjectActionLoading === status.id"
                                        @click="openReturnModal(status)"
                                    >
                                        <CornerDownLeft class="size-3" />
                                        {{ $t('exams.actions.return_subject') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Return Reason Modal -->
        <Modal :show="showReturnModal" max-width="md" @close="showReturnModal = false">
            <div class="p-6">
                <div class="flex items-center gap-2 text-danger">
                    <ShieldAlert class="size-5 shrink-0" />
                    <h3 class="text-lg font-bold text-ink">
                        {{ $t('exams.actions.return_subject') }}
                    </h3>
                </div>

                <p class="mt-2 text-xs text-ink-muted">
                    {{ $t('exams.return_reason_placeholder') }}
                </p>

                <div class="mt-4">
                    <label for="return-reason-text" class="block text-xs font-medium text-ink">
                        {{ $t('exams.return_reason') }} <span class="text-danger">*</span>
                    </label>
                    <textarea
                        id="return-reason-text"
                        v-model="returnReason"
                        rows="3"
                        class="s-input mt-1"
                        :placeholder="$t('exams.return_reason_placeholder')"
                        required
                    />
                    <p v-if="returnError" class="mt-1 text-xs text-danger font-medium">
                        {{ returnError }}
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="s-btn"
                        @click="showReturnModal = false"
                    >
                        {{ $t('app.cancel') }}
                    </button>
                    <button
                        type="button"
                        class="s-btn s-btn-danger"
                        @click="submitReturn"
                    >
                        {{ $t('exams.actions.return_subject') }}
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
