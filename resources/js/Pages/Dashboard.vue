<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Award,
    BookOpen,
    BookUser,
    ChevronRight,
    ClipboardList,
    Clock,
    GraduationCap,
    School,
    UserCheck,
    Users,
} from 'lucide-vue-next';
import { useRoleLayout } from '@/lib/roleLayout';
import EmptyState from '@/Components/EmptyState.vue';
import MetricKpiCard from '@/Components/MetricKpiCard.vue';
import StatusPill from '@/Components/StatusPill.vue';

const props = defineProps({
    hasSchool: { type: Boolean, required: true },
    teacherDashboard: { type: Object, default: null },
    adminDashboard: { type: Object, default: null },
});

const page = usePage();
const layout = useRoleLayout();

const totalWeeklyHours = computed(() => {
    if (!props.teacherDashboard?.teaching_assignments) return 0;
    return props.teacherDashboard.teaching_assignments.reduce((sum, a) => sum + (Number(a.hours_per_week) || 0), 0);
});
</script>

<template>
    <component :is="layout" :title="$t('app.welcome', { name: page.props.auth.user.name })">
        <Head :title="$t('app.dashboard')" />

        <!-- No School State -->
        <EmptyState
            v-if="!hasSchool"
            :icon="School"
            :title="$t('app.no_school_title')"
            :text="$t('app.no_school_text')"
        />

        <!-- TEACHER DASHBOARD -->
        <div v-else-if="teacherDashboard" class="space-y-8">
            <!-- Academic Year Subtitle Banner -->
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-border pb-4">
                <div>
                    <h2 class="text-lg font-bold text-ink">
                        {{ $t('exams.my_classes') }}
                    </h2>
                    <p class="text-xs text-ink-muted">
                        {{ $t('academic.year') }}:
                        <span class="font-semibold text-primary">
                            {{ teacherDashboard.current_year_name }}
                        </span>
                    </p>
                </div>

                <div
                    class="inline-flex items-center gap-2 rounded-full border border-border bg-surface-raised px-3 py-1 text-xs font-semibold text-ink shadow-xs"
                >
                    <Clock class="size-3.5 text-primary" />
                    <span>
                        {{ $t('exams.weekly_hours', { hours: totalWeeklyHours }) }}
                    </span>
                </div>
            </div>

            <!-- Homeroom Classes Section (if any) -->
            <div v-if="teacherDashboard.homeroom_classes?.length > 0" class="space-y-3">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-ink-muted">
                    <UserCheck class="size-4 text-primary" />
                    <h3>{{ $t('exams.homeroom_classes') }}</h3>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                    <div
                        v-for="c in teacherDashboard.homeroom_classes"
                        :key="c.id"
                        class="rounded-lg border border-border bg-surface-raised p-4 shadow-xs transition-shadow hover:shadow-sm"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="text-base font-bold text-ink">
                                    {{ c.name }}
                                </h4>
                                <p class="text-xs text-ink-muted">
                                    {{ c.grade }}
                                </p>
                            </div>
                            <span class="rounded bg-primary-soft px-2 py-0.5 text-xs font-semibold text-primary">
                                {{ $t('academic.homeroom_teacher') }}
                            </span>
                        </div>

                        <div
                            class="mt-4 flex items-center justify-between border-t border-border pt-3 text-xs text-ink-muted"
                        >
                            <span class="font-mono font-medium text-ink">
                                {{ $t('exams.students_enrolled', { count: c.student_count }) }}
                            </span>
                            <Link
                                :href="`/people/enrollment`"
                                class="inline-flex items-center gap-1 font-medium text-primary hover:underline"
                            >
                                <span>{{ $t('people.tabs.enrollment') }}</span>
                                <ChevronRight class="size-3" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Exams Section -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-ink-muted">
                        <Award class="size-4 text-primary" />
                        <h3>{{ $t('exams.open_exams') }}</h3>
                    </div>
                    <Link
                        v-if="page.props.can?.manageExams"
                        href="/exams"
                        class="text-xs font-medium text-primary hover:underline"
                    >
                        {{ $t('exams.title') }} →
                    </Link>
                </div>

                <div
                    v-if="!teacherDashboard.open_exams || teacherDashboard.open_exams.length === 0"
                    class="rounded-lg border border-border bg-surface-raised p-6 text-center text-sm text-ink-muted"
                >
                    {{ $t('exams.no_open_exams') }}
                </div>

                <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div
                        v-for="exam in teacherDashboard.open_exams"
                        :key="exam.id"
                        class="rounded-lg border border-border bg-surface-raised p-5 shadow-xs transition-shadow hover:shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h4 class="text-base font-bold text-ink">
                                    {{ exam.name }}
                                </h4>
                                <p class="font-mono text-xs text-ink-muted">
                                    {{ exam.start_date }} → {{ exam.end_date }}
                                </p>
                            </div>
                            <StatusPill status="open" :label="$t('exams.statuses.open')" />
                        </div>

                        <!-- Subjects list in this exam -->
                        <div
                            class="mt-4 divide-y divide-border/60 rounded border border-border/80 bg-surface-sunken/40"
                        >
                            <div
                                v-for="subj in exam.subjects"
                                :key="subj.id"
                                class="flex items-center justify-between p-2.5 text-xs"
                            >
                                <Link
                                    :href="`/exams/${exam.id}/scores?classroom=${subj.classroom_id}&subject=${subj.subject_id}`"
                                    class="hover:text-primary hover:underline"
                                >
                                    <span class="font-bold text-ink">{{ subj.classroom_name }}</span>
                                    <span class="mx-1 text-ink-muted">•</span>
                                    <span class="text-ink">{{ subj.subject_name }}</span>
                                </Link>
                                <div class="flex items-center gap-2">
                                    <StatusPill
                                        :status="subj.status"
                                        :label="$t(`exams.subject_statuses.${subj.status}`)"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <Link :href="`/exams/${exam.id}`" class="s-btn s-btn-compact s-btn-primary">
                                <Award class="size-3.5" />
                                {{ $t('exams.actions.enter_scores') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Teaching Schedule Section -->
            <div class="space-y-3">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-ink-muted">
                    <BookOpen class="size-4 text-primary" />
                    <h3>{{ $t('exams.assigned_subjects') }}</h3>
                </div>

                <div
                    v-if="!teacherDashboard.teaching_assignments || teacherDashboard.teaching_assignments.length === 0"
                    class="rounded-lg border border-border bg-surface-raised p-6 text-center text-sm text-ink-muted"
                >
                    {{ $t('exams.no_assigned_classes') }}
                </div>

                <div v-else class="overflow-hidden rounded-lg border border-border bg-surface-raised shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead
                                class="border-b border-border bg-surface-sunken/60 text-xs font-semibold uppercase tracking-wider text-ink-muted"
                            >
                                <tr>
                                    <th class="px-4 py-3">{{ $t('academic.tabs.classrooms') }}</th>
                                    <th class="px-4 py-3">{{ $t('academic.grade') }}</th>
                                    <th class="px-4 py-3">{{ $t('academic.subject') }}</th>
                                    <th class="px-4 py-3">{{ $t('academic.hours_per_week') }}</th>
                                    <th class="px-4 py-3">{{ $t('people.tabs.students') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr
                                    v-for="a in teacherDashboard.teaching_assignments"
                                    :key="a.id"
                                    class="transition-colors hover:bg-surface-sunken/30"
                                >
                                    <td class="px-4 py-3 font-bold text-ink">
                                        {{ a.classroom_name }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-ink-muted">
                                        {{ a.grade }}
                                    </td>
                                    <td class="px-4 py-3 font-medium text-ink">
                                        <span class="font-mono text-xs text-ink-muted mr-1.5">{{
                                            a.subject_code
                                        }}</span>
                                        <span>{{ a.subject_name }}</span>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs font-semibold text-ink">
                                        {{ a.hours_per_week }} {{ $t('academic.hours_short') }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-ink">
                                        {{ a.student_count }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ADMIN DASHBOARD -->
        <div v-else-if="adminDashboard" class="space-y-8">
            <!-- Year Indicator -->
            <div class="flex items-center justify-between border-b border-border pb-4">
                <div>
                    <h2 class="text-lg font-bold text-ink">
                        {{ $t('app.dashboard') }}
                    </h2>
                    <p class="text-xs text-ink-muted">
                        {{ $t('academic.year') }}:
                        <span class="font-semibold text-primary">
                            {{ adminDashboard.current_year_name }}
                        </span>
                    </p>
                </div>
            </div>

            <!-- MoEYS Metric KPI Cards -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <MetricKpiCard
                    :title="$t('academic.tabs.classrooms')"
                    :total="adminDashboard.classrooms_count"
                    :icon="GraduationCap"
                />
                <MetricKpiCard
                    :title="$t('people.tabs.teachers')"
                    :total="adminDashboard.teachers_count"
                    :icon="BookUser"
                />
                <MetricKpiCard
                    :title="$t('people.tabs.students')"
                    :total="adminDashboard.students_count"
                    :icon="Users"
                />
                <MetricKpiCard :title="$t('exams.open_exams')" :total="adminDashboard.open_exams_count" :icon="Award" />
            </div>

            <!-- Quick Action Management Cards -->
            <div class="space-y-3">
                <div class="text-xs font-bold uppercase tracking-wider text-ink-muted">
                    {{ $t('academic.actions') }}
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Teaching Assignments -->
                    <Link
                        v-if="page.props.can?.manageAcademic"
                        href="/academic/teaching"
                        class="group rounded-lg border border-border bg-surface-raised p-5 shadow-xs transition-all hover:border-primary hover:shadow-sm"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded-md bg-primary-soft p-2.5 text-primary group-hover:bg-primary group-hover:text-white transition-colors"
                            >
                                <BookOpen class="size-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-ink group-hover:text-primary transition-colors">
                                    {{ $t('academic.tabs.teaching') }}
                                </h3>
                                <p class="text-xs text-ink-muted mt-0.5">
                                    {{ $t('academic.teaching_title') }}
                                </p>
                            </div>
                        </div>
                    </Link>

                    <!-- Student Enrollment -->
                    <Link
                        v-if="page.props.can?.managePeople"
                        href="/people/enrollment"
                        class="group rounded-lg border border-border bg-surface-raised p-5 shadow-xs transition-all hover:border-primary hover:shadow-sm"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded-md bg-primary-soft p-2.5 text-primary group-hover:bg-primary group-hover:text-white transition-colors"
                            >
                                <Users class="size-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-ink group-hover:text-primary transition-colors">
                                    {{ $t('people.tabs.enrollment') }}
                                </h3>
                                <p class="text-xs text-ink-muted mt-0.5">
                                    {{ $t('people.title') }}
                                </p>
                            </div>
                        </div>
                    </Link>

                    <!-- Exams -->
                    <Link
                        v-if="page.props.can?.manageExams"
                        href="/exams"
                        class="group rounded-lg border border-border bg-surface-raised p-5 shadow-xs transition-all hover:border-primary hover:shadow-sm"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded-md bg-primary-soft p-2.5 text-primary group-hover:bg-primary group-hover:text-white transition-colors"
                            >
                                <Award class="size-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-ink group-hover:text-primary transition-colors">
                                    {{ $t('exams.title') }}
                                </h3>
                                <p class="text-xs text-ink-muted mt-0.5">
                                    {{ $t('exams.create') }} & {{ $t('exams.progress') }}
                                </p>
                            </div>
                        </div>
                    </Link>

                    <!-- Academic Structure -->
                    <Link
                        v-if="page.props.can?.manageAcademic"
                        href="/academic/curriculum"
                        class="group rounded-lg border border-border bg-surface-raised p-5 shadow-xs transition-all hover:border-primary hover:shadow-sm"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded-md bg-primary-soft p-2.5 text-primary group-hover:bg-primary group-hover:text-white transition-colors"
                            >
                                <GraduationCap class="size-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-ink group-hover:text-primary transition-colors">
                                    {{ $t('academic.tabs.curriculum') }}
                                </h3>
                                <p class="text-xs text-ink-muted mt-0.5">
                                    {{ $t('academic.title') }}
                                </p>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Default Empty State if neither teacher nor admin dashboard data is populated -->
        <EmptyState
            v-else
            :icon="ClipboardList"
            :title="$t('app.dashboard_empty_title')"
            :text="$t('app.dashboard_empty_text')"
        />
    </component>
</template>
