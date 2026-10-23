<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { Award, BookOpen, Check, Copy, GraduationCap, Save, Users } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AcademicTabs from '@/Components/AcademicTabs.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    years: { type: Array, required: true },
    yearId: { type: Number, default: null },
    classrooms: { type: Array, required: true },
    selectedClassroomId: { type: Number, default: null },
    selectedClassroom: { type: Object, default: null },
    curriculumSubjects: { type: Array, required: true },
    assignments: { type: Array, required: true },
    teachers: { type: Array, required: true },
    approvalAssignments: { type: Array, required: true },
    grades: { type: Array, required: true },
    staffUsers: { type: Array, required: true },
});

const page = usePage();
const name = (row) => (page.props.locale === 'en' ? (row.name_en || row.name_km) : (row.name_km || row.name_en));

// Year switching
const onYearChange = (event) => {
    router.get('/academic/teaching', { year: event.target.value }, { preserveState: false });
};

// Classroom switching
const selectClassroom = (classroom) => {
    router.get(
        '/academic/teaching',
        { year: props.yearId, classroom_id: classroom.id },
        { preserveState: true, preserveScroll: true },
    );
};

// Assignments Form
const form = useForm({
    assignments: [],
});

const initAssignmentsForm = () => {
    const existing = new Map(props.assignments.map((a) => [a.subject_id, a]));
    form.assignments = props.curriculumSubjects.map((sub) => {
        const found = existing.get(sub.id);
        return {
            subject_id: sub.id,
            teacher_id: found ? found.teacher_id : null,
            hours_per_week: found ? found.hours_per_week : 0,
        };
    });
};

watch(() => [props.selectedClassroomId, props.assignments, props.curriculumSubjects], initAssignmentsForm, {
    immediate: true,
});

const saveAssignments = () => {
    if (!props.selectedClassroom) return;
    form.put(`/academic/teaching/classrooms/${props.selectedClassroom.id}`, {
        preserveScroll: true,
    });
};

// Copy modal
const showCopyModal = ref(false);
const copyForm = useForm({
    target_year_id: props.yearId,
    source_year_id: '',
});

const otherYears = computed(() => props.years.filter((y) => y.id !== props.yearId));

const submitCopy = () => {
    copyForm.target_year_id = props.yearId;
    copyForm.post('/academic/teaching/copy', {
        onSuccess: () => {
            showCopyModal.value = false;
        },
    });
};

// Approvals Form
const approvalsForm = useForm({
    year_id: props.yearId,
    approvals: [],
});

const initApprovalsForm = () => {
    const existing = new Map(props.approvalAssignments.map((a) => [a.grade_level_id, a]));
    approvalsForm.year_id = props.yearId;
    approvalsForm.approvals = props.grades.map((grade) => {
        const found = existing.get(grade.id);
        return {
            grade_level_id: grade.id,
            approver_type: found ? found.approver_type : 'homeroom',
            role: found ? found.role : 'academic_office',
            user_id: found ? found.user_id : (props.staffUsers[0]?.id || null),
        };
    });
};

watch(() => [props.yearId, props.approvalAssignments, props.grades], initApprovalsForm, { immediate: true });

const saveApprovals = () => {
    approvalsForm.year_id = props.yearId;
    approvalsForm.put('/academic/teaching/approvals', {
        preserveScroll: true,
    });
};

// Helpers
const teacherCanTeach = (teacher, subjectId) => {
    return teacher.subject_ids && teacher.subject_ids.includes(subjectId);
};
</script>

<template>
    <AdminLayout :title="$t('academic.teaching_title')">
        <AcademicTabs />

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <label for="year-select" class="text-sm font-medium text-ink-muted">
                    {{ $t('academic.year') }}:
                </label>
                <select
                    id="year-select"
                    :value="yearId"
                    class="rounded-sm border border-border bg-surface-raised px-3 py-1.5 text-sm font-medium shadow-xs focus:border-primary focus:ring-1 focus:ring-primary"
                    @change="onYearChange"
                >
                    <option v-for="y in years" :key="y.id" :value="y.id">
                        {{ y.name }} {{ y.is_current ? `(${ $t('academic.current') })` : '' }}
                    </option>
                </select>
            </div>

            <button
                v-if="otherYears.length > 0"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-sm border border-border bg-surface-raised px-3 py-1.5 text-sm font-medium text-ink shadow-xs transition-colors hover:border-border-strong hover:bg-surface-sunken"
                @click="showCopyModal = true"
            >
                <Copy class="size-4 text-ink-muted" />
                {{ $t('academic.copy_teaching_from') }}
            </button>
        </div>

        <div v-if="classrooms.length === 0" class="rounded-lg border border-border bg-surface-raised p-8">
            <EmptyState
                :icon="GraduationCap"
                :title="$t('academic.empty')"
                :text="$t('academic.no_year')"
            />
        </div>

        <div v-else class="space-y-8">
            <!-- SECTION 1: Classroom Matrix -->
            <div class="rounded-lg border border-border bg-surface-raised shadow-xs">
                <!-- Classroom Pill Selector -->
                <div class="border-b border-border bg-surface-sunken/40 px-4 py-3">
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-ink-muted">
                        {{ $t('academic.select_classroom') }}
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="c in classrooms"
                            :key="c.id"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-sm border px-3 py-1.5 text-sm font-medium transition-all"
                            :class="
                                selectedClassroomId === c.id
                                    ? 'border-primary bg-primary text-white shadow-xs'
                                    : 'border-border bg-surface-raised text-ink hover:border-border-strong'
                            "
                            @click="selectClassroom(c)"
                        >
                            <span class="font-bold">{{ c.name }}</span>
                            <span
                                class="text-xs opacity-80"
                                :class="selectedClassroomId === c.id ? 'text-white' : 'text-ink-muted'"
                            >
                                ({{ name(c.gradeLevel) }})
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Classroom Details Header -->
                <div v-if="selectedClassroom" class="border-b border-border p-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-ink">
                                {{ $t('academic.classroom_name') }}: {{ selectedClassroom.name }}
                            </h2>
                            <p class="text-xs text-ink-muted">
                                {{ $t('academic.grade') }}: {{ name(selectedClassroom.gradeLevel) }}
                                <span v-if="selectedClassroom.track">
                                    • {{ $t('academic.track') }}: {{ name(selectedClassroom.track) }}
                                </span>
                                • {{ $t('academic.homeroom_teacher') }}:
                                <span class="font-medium text-ink">
                                    {{ selectedClassroom.homeroomTeacher ? selectedClassroom.homeroomTeacher.name_km : $t('academic.unassigned') }}
                                </span>
                            </p>
                        </div>

                        <button
                            type="button"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-1.5 rounded-sm bg-primary px-4 py-2 text-sm font-medium text-white shadow-xs transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50"
                            @click="saveAssignments"
                        >
                            <Save class="size-4" />
                            {{ $t('academic.saved') ? $t('academic.edit') : $t('academic.saved') }}
                            {{ $t('settings.save') }}
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-ink-muted">
                        {{ $t('academic.teaching_matrix_hint') }}
                    </p>
                </div>

                <!-- Assignment Matrix Table -->
                <div v-if="selectedClassroom" class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-border bg-surface-sunken/60 text-xs font-semibold uppercase tracking-wider text-ink-muted">
                            <tr>
                                <th class="w-16 px-4 py-3">{{ $t('academic.code') }}</th>
                                <th class="px-4 py-3">{{ $t('academic.subject') }}</th>
                                <th class="w-24 px-4 py-3">{{ $t('academic.coefficient') }}</th>
                                <th class="w-72 px-4 py-3">{{ $t('academic.assign_teacher') }}</th>
                                <th class="w-32 px-4 py-3">{{ $t('academic.hours_per_week') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="(sub, idx) in curriculumSubjects"
                                :key="sub.id"
                                class="transition-colors hover:bg-surface-sunken/30"
                            >
                                <td class="px-4 py-3 font-mono text-xs font-semibold text-ink-muted">
                                    {{ sub.code }}
                                </td>
                                <td class="px-4 py-3 font-medium text-ink">
                                    {{ name(sub) }}
                                </td>
                                <td class="px-4 py-3 text-xs text-ink-muted">
                                    <span v-if="sub.coefficient" class="font-mono">
                                        ×{{ sub.coefficient }}
                                    </span>
                                    <span v-else>—</span>
                                </td>
                                <td class="px-4 py-3">
                                    <select
                                        v-if="form.assignments[idx]"
                                        v-model="form.assignments[idx].teacher_id"
                                        class="w-full rounded-sm border border-border bg-surface-raised px-2.5 py-1.5 text-xs text-ink focus:border-primary focus:ring-1 focus:ring-primary"
                                    >
                                        <option :value="null">-- {{ $t('academic.unassigned') }} --</option>
                                        <option
                                            v-for="t in teachers"
                                            :key="t.id"
                                            :value="t.id"
                                        >
                                            {{ t.name_km }} {{ t.staff_code ? `(${t.staff_code})` : '' }}
                                            {{ teacherCanTeach(t, sub.id) ? '★' : '' }}
                                        </option>
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <input
                                            v-if="form.assignments[idx]"
                                            v-model.number="form.assignments[idx].hours_per_week"
                                            type="number"
                                            min="0"
                                            max="40"
                                            class="w-20 rounded-sm border border-border bg-surface-raised px-2.5 py-1.5 text-xs font-mono tabular-nums text-ink focus:border-primary focus:ring-1 focus:ring-primary"
                                        />
                                        <span class="text-xs text-ink-muted">{{ $t('academic.hours_short') }}</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-end border-t border-border p-4">
                    <button
                        type="button"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 rounded-sm bg-primary px-4 py-2 text-sm font-medium text-white shadow-xs transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50"
                        @click="saveAssignments"
                    >
                        <Save class="size-4" />
                        {{ $t('settings.save') }}
                    </button>
                </div>
            </div>

            <!-- SECTION 2: Grade Approver Configuration -->
            <div class="rounded-lg border border-border bg-surface-raised shadow-xs">
                <div class="border-b border-border p-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-ink">
                                {{ $t('academic.approvals_title') }}
                            </h2>
                            <p class="mt-1 text-xs text-ink-muted">
                                {{ $t('academic.approvals_hint') }}
                            </p>
                        </div>

                        <button
                            type="button"
                            :disabled="approvalsForm.processing"
                            class="inline-flex items-center gap-1.5 rounded-sm bg-primary px-4 py-2 text-sm font-medium text-white shadow-xs transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50"
                            @click="saveApprovals"
                        >
                            <Save class="size-4" />
                            {{ $t('settings.save') }}
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-border bg-surface-sunken/60 text-xs font-semibold uppercase tracking-wider text-ink-muted">
                            <tr>
                                <th class="w-48 px-4 py-3">{{ $t('academic.grade') }}</th>
                                <th class="w-64 px-4 py-3">{{ $t('academic.approver') }}</th>
                                <th class="px-4 py-3">{{ $t('academic.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="(grade, idx) in grades"
                                :key="grade.id"
                                class="transition-colors hover:bg-surface-sunken/30"
                            >
                                <td class="px-4 py-3 font-medium text-ink">
                                    {{ name(grade) }}
                                </td>
                                <td class="px-4 py-3">
                                    <select
                                        v-if="approvalsForm.approvals[idx]"
                                        v-model="approvalsForm.approvals[idx].approver_type"
                                        class="w-full rounded-sm border border-border bg-surface-raised px-2.5 py-1.5 text-xs text-ink focus:border-primary focus:ring-1 focus:ring-primary"
                                    >
                                        <option value="homeroom">{{ $t('academic.approver_types.homeroom') }}</option>
                                        <option value="role">{{ $t('academic.approver_types.role') }}</option>
                                        <option value="user">{{ $t('academic.approver_types.user') }}</option>
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <div v-if="approvalsForm.approvals[idx]">
                                        <!-- If role -->
                                        <div v-if="approvalsForm.approvals[idx].approver_type === 'role'" class="flex items-center gap-2">
                                            <span class="text-xs text-ink-muted">{{ $t('academic.designated_role') }}:</span>
                                            <select
                                                v-model="approvalsForm.approvals[idx].role"
                                                class="rounded-sm border border-border bg-surface-raised px-2.5 py-1.5 text-xs text-ink focus:border-primary focus:ring-1 focus:ring-primary"
                                            >
                                                <option value="academic_office">{{ $t('roles.academic_office') }}</option>
                                                <option value="director">{{ $t('roles.director') }}</option>
                                            </select>
                                        </div>

                                        <!-- If specific user -->
                                        <div v-else-if="approvalsForm.approvals[idx].approver_type === 'user'" class="flex items-center gap-2">
                                            <span class="text-xs text-ink-muted">{{ $t('academic.designated_user') }}:</span>
                                            <select
                                                v-model="approvalsForm.approvals[idx].user_id"
                                                class="rounded-sm border border-border bg-surface-raised px-2.5 py-1.5 text-xs text-ink focus:border-primary focus:ring-1 focus:ring-primary"
                                            >
                                                <option
                                                    v-for="u in staffUsers"
                                                    :key="u.id"
                                                    :value="u.id"
                                                >
                                                    {{ u.name }} ({{ u.email }})
                                                </option>
                                            </select>
                                        </div>

                                        <div v-else class="text-xs text-ink-muted">
                                            ✓ {{ $t('academic.approver_types.homeroom') }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-end border-t border-border p-4">
                    <button
                        type="button"
                        :disabled="approvalsForm.processing"
                        class="inline-flex items-center gap-1.5 rounded-sm bg-primary px-4 py-2 text-sm font-medium text-white shadow-xs transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50"
                        @click="saveApprovals"
                    >
                        <Save class="size-4" />
                        {{ $t('settings.save') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Copy Teaching Modal -->
        <Modal :show="showCopyModal" :title="$t('academic.copy_teaching_from')" @close="showCopyModal = false">
            <div class="space-y-4 p-6">
                <p class="text-sm text-ink-muted">
                    {{ $t('academic.copy_teaching_text') }}
                </p>

                <div>
                    <label for="source-year" class="block text-sm font-medium text-ink">
                        {{ $t('academic.copy_from') }}
                    </label>
                    <select
                        id="source-year"
                        v-model="copyForm.source_year_id"
                        class="mt-1 block w-full rounded-sm border border-border bg-surface-raised px-3 py-2 text-sm text-ink focus:border-primary focus:ring-1 focus:ring-primary"
                    >
                        <option value="" disabled>-- {{ $t('academic.year') }} --</option>
                        <option v-for="y in otherYears" :key="y.id" :value="y.id">
                            {{ y.name }}
                        </option>
                    </select>
                    <p v-if="copyForm.errors.source_year_id" class="mt-1 text-xs text-danger">
                        {{ copyForm.errors.source_year_id }}
                    </p>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button
                        type="button"
                        class="rounded-sm border border-border bg-surface-raised px-4 py-2 text-sm font-medium text-ink shadow-xs hover:bg-surface-sunken"
                        @click="showCopyModal = false"
                    >
                        {{ $t('academic.no') }}
                    </button>
                    <button
                        type="button"
                        :disabled="!copyForm.source_year_id || copyForm.processing"
                        class="inline-flex items-center gap-1.5 rounded-sm bg-primary px-4 py-2 text-sm font-medium text-white shadow-xs hover:bg-primary-hover disabled:opacity-50"
                        @click="submitCopy"
                    >
                        <Copy class="size-4" />
                        {{ $t('academic.copy') }}
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
