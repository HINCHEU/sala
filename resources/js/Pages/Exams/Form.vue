<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, CheckSquare, Save, Square } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    exam: { type: Object, default: null },
    years: { type: Array, required: true },
    yearId: { type: Number, required: true },
    terms: { type: Array, required: true },
    grades: { type: Array, required: true },
    types: { type: Array, required: true },
});

const page = usePage();
const isEditing = computed(() => !!props.exam);
const gradeName = (g) => (page.props.locale === 'en' ? g.name_en || g.name_km : g.name_km || g.name_en);

const form = useForm({
    academic_year_id: props.exam?.academic_year_id ?? props.yearId,
    term_id: props.exam?.term_id ?? (props.terms[0]?.id || ''),
    name: props.exam?.name ?? '',
    type: props.exam?.type ?? 'monthly',
    month: props.exam?.month ?? new Date().getMonth() + 1,
    start_date: props.exam?.start_date ?? new Date().toISOString().slice(0, 10),
    end_date: props.exam?.end_date ?? new Date().toISOString().slice(0, 10),
    description: props.exam?.description ?? '',
    classroom_ids: props.exam?.classroom_ids ? [...props.exam.classroom_ids] : [],
});

// Classroom Selection Helpers
const allClassrooms = computed(() => {
    return props.grades.flatMap((g) => g.classrooms || []);
});

const allSelected = computed(() => {
    return allClassrooms.value.length > 0 && form.classroom_ids.length === allClassrooms.value.length;
});

const toggleSelectAll = () => {
    if (allSelected.value) {
        form.classroom_ids = [];
    } else {
        form.classroom_ids = allClassrooms.value.map((c) => c.id);
    }
};

const isGradeAllSelected = (grade) => {
    const classIds = (grade.classrooms || []).map((c) => c.id);
    return classIds.length > 0 && classIds.every((id) => form.classroom_ids.includes(id));
};

const toggleGradeClassrooms = (grade) => {
    const classIds = (grade.classrooms || []).map((c) => c.id);
    if (isGradeAllSelected(grade)) {
        form.classroom_ids = form.classroom_ids.filter((id) => !classIds.includes(id));
    } else {
        const set = new Set([...form.classroom_ids, ...classIds]);
        form.classroom_ids = Array.from(set);
    }
};

const submit = () => {
    if (isEditing.value) {
        form.put(`/exams/${props.exam.id}`);
    } else {
        form.post('/exams');
    }
};
</script>

<template>
    <AdminLayout :title="isEditing ? $t('exams.edit') : $t('exams.create')">
        <Head :title="isEditing ? $t('exams.edit') : $t('exams.create')" />

        <div class="mb-6">
            <Link
                :href="isEditing ? `/exams/${exam.id}` : `/exams?year=${yearId}`"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted transition-colors hover:text-ink"
            >
                <ArrowLeft class="size-4" />
                {{ $t('exams.title') }}
            </Link>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <!-- Basic Details Card -->
            <div class="rounded-lg border border-border bg-surface-raised p-6 shadow-xs">
                <h2 class="mb-4 text-base font-bold text-ink">{{ $t('exams.name') }} & {{ $t('exams.dates') }}</h2>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Academic Year -->
                    <div>
                        <label for="exam-year" class="block text-sm font-medium text-ink">
                            {{ $t('academic.year') }} <span class="text-danger">*</span>
                        </label>
                        <select
                            id="exam-year"
                            v-model="form.academic_year_id"
                            class="s-input mt-1"
                            :disabled="isEditing"
                        >
                            <option v-for="y in years" :key="y.id" :value="y.id">
                                {{ y.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.academic_year_id" class="mt-1 text-xs text-danger">
                            {{ form.errors.academic_year_id }}
                        </p>
                    </div>

                    <!-- Term / Semester -->
                    <div>
                        <label for="exam-term" class="block text-sm font-medium text-ink">
                            {{ $t('academic.terms') }}
                        </label>
                        <select id="exam-term" v-model="form.term_id" class="s-input mt-1">
                            <option value="">{{ $t('academic.empty') }}</option>
                            <option v-for="t in terms" :key="t.id" :value="t.id">
                                {{ page.props.locale === 'en' ? t.name_en : t.name_km }}
                            </option>
                        </select>
                        <p v-if="form.errors.term_id" class="mt-1 text-xs text-danger">
                            {{ form.errors.term_id }}
                        </p>
                    </div>

                    <!-- Exam Name -->
                    <div class="sm:col-span-2">
                        <label for="exam-name" class="block text-sm font-medium text-ink">
                            {{ $t('exams.name') }} <span class="text-danger">*</span>
                        </label>
                        <input
                            id="exam-name"
                            v-model="form.name"
                            type="text"
                            class="s-input mt-1"
                            placeholder="e.g. ការប្រឡងប្រចាំខែវិច្ឆិកា / November Monthly Exam"
                            required
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-danger">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Exam Type -->
                    <div>
                        <label for="exam-type" class="block text-sm font-medium text-ink">
                            {{ $t('exams.type') }} <span class="text-danger">*</span>
                        </label>
                        <select id="exam-type" v-model="form.type" class="s-input mt-1">
                            <option v-for="type in types" :key="type" :value="type">
                                {{ $t(`exams.types.${type}`) }}
                            </option>
                        </select>
                        <p v-if="form.errors.type" class="mt-1 text-xs text-danger">
                            {{ form.errors.type }}
                        </p>
                    </div>

                    <!-- Month (if Monthly) -->
                    <div v-if="form.type === 'monthly'">
                        <label for="exam-month" class="block text-sm font-medium text-ink">
                            {{ $t('exams.month') }}
                        </label>
                        <select id="exam-month" v-model="form.month" class="s-input mt-1">
                            <option v-for="m in 12" :key="m" :value="m">
                                {{ $t(`exams.months.${m}`) }}
                            </option>
                        </select>
                        <p v-if="form.errors.month" class="mt-1 text-xs text-danger">
                            {{ form.errors.month }}
                        </p>
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label for="exam-start" class="block text-sm font-medium text-ink">
                            {{ $t('exams.start_date') }} <span class="text-danger">*</span>
                        </label>
                        <input
                            id="exam-start"
                            v-model="form.start_date"
                            type="date"
                            class="s-input mt-1 font-mono"
                            required
                        />
                        <p v-if="form.errors.start_date" class="mt-1 text-xs text-danger">
                            {{ form.errors.start_date }}
                        </p>
                    </div>

                    <!-- End Date -->
                    <div>
                        <label for="exam-end" class="block text-sm font-medium text-ink">
                            {{ $t('exams.end_date') }} <span class="text-danger">*</span>
                        </label>
                        <input
                            id="exam-end"
                            v-model="form.end_date"
                            type="date"
                            class="s-input mt-1 font-mono"
                            required
                        />
                        <p v-if="form.errors.end_date" class="mt-1 text-xs text-danger">
                            {{ form.errors.end_date }}
                        </p>
                    </div>

                    <!-- Description / Instructions -->
                    <div class="sm:col-span-2">
                        <label for="exam-desc" class="block text-sm font-medium text-ink">
                            {{ $t('exams.description') }}
                        </label>
                        <textarea
                            id="exam-desc"
                            v-model="form.description"
                            rows="3"
                            class="s-input mt-1"
                            placeholder="Optional instructions for teachers or exam supervisors..."
                        />
                        <p v-if="form.errors.description" class="mt-1 text-xs text-danger">
                            {{ form.errors.description }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Classroom Selection Card -->
            <div class="rounded-lg border border-border bg-surface-raised p-6 shadow-xs">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-bold text-ink">
                            {{ $t('exams.participating_classrooms') }} <span class="text-danger">*</span>
                        </h2>
                        <p class="text-xs text-ink-muted">
                            {{ form.classroom_ids.length }} / {{ allClassrooms.length }}
                            {{ $t('academic.tabs.classrooms') }}
                        </p>
                    </div>

                    <button type="button" class="s-btn s-btn-compact" @click="toggleSelectAll">
                        <CheckSquare v-if="allSelected" class="size-3.5 text-primary" />
                        <Square v-else class="size-3.5 text-ink-muted" />
                        {{ allSelected ? $t('exams.deselect_all') : $t('exams.select_all') }}
                    </button>
                </div>

                <p v-if="form.errors.classroom_ids" class="mb-4 text-xs font-semibold text-danger">
                    {{ form.errors.classroom_ids }}
                </p>

                <!-- Grades & Classrooms Grid -->
                <div class="space-y-4">
                    <div
                        v-for="grade in grades"
                        :key="grade.id"
                        class="rounded border border-border/80 bg-surface-sunken/30 p-3"
                    >
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-ink">
                                {{ gradeName(grade) }}
                            </span>
                            <button
                                v-if="(grade.classrooms || []).length > 0"
                                type="button"
                                class="text-xs font-medium text-primary hover:underline"
                                @click="toggleGradeClassrooms(grade)"
                            >
                                {{ isGradeAllSelected(grade) ? $t('exams.deselect_all') : $t('exams.select_all') }}
                            </button>
                        </div>

                        <div v-if="(grade.classrooms || []).length === 0" class="text-xs text-ink-muted italic">
                            {{ $t('academic.empty') }}
                        </div>

                        <div v-else class="grid grid-cols-2 gap-2 sm:grid-cols-4 md:grid-cols-6">
                            <label
                                v-for="c in grade.classrooms"
                                :key="c.id"
                                class="flex cursor-pointer items-center gap-2 rounded border p-2 text-xs font-medium transition-colors"
                                :class="
                                    form.classroom_ids.includes(c.id)
                                        ? 'border-primary bg-primary-soft/30 text-primary font-bold'
                                        : 'border-border bg-surface-raised text-ink hover:bg-surface-sunken'
                                "
                            >
                                <input
                                    v-model="form.classroom_ids"
                                    type="checkbox"
                                    :value="c.id"
                                    class="rounded border-border text-primary focus:ring-primary"
                                />
                                <span class="truncate">{{ c.name }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <Link :href="isEditing ? `/exams/${exam.id}` : `/exams?year=${yearId}`" class="s-btn">
                    {{ $t('app.cancel') }}
                </Link>
                <button type="submit" class="s-btn s-btn-primary" :disabled="form.processing">
                    <Save class="size-4" />
                    {{ form.processing ? $t('app.saving') : isEditing ? $t('academic.saved') : $t('exams.create') }}
                </button>
            </div>
        </form>
    </AdminLayout>
</template>
