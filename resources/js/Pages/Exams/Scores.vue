<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Download, Lock, Send, Upload } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ScoreGrid from '@/Components/ScoreGrid.vue';
import StatusPill from '@/Components/StatusPill.vue';
import SButton from '@/Components/SButton.vue';
import { cellKey, useScoreSaver } from '@/lib/useScoreSaver';

const props = defineProps({
    exam: { type: Object, required: true },
    classroom: { type: Object, required: true },
    classrooms: { type: Array, required: true },
    subjects: { type: Array, required: true },
    students: { type: Array, required: true },
    scores: { type: Object, required: true },
    focus: { type: Number, default: null },
});

const page = usePage();
const saver = useScoreSaver({ examId: props.exam.id, classroomId: props.classroom.id, initial: props.scores });
const subjectName = (s) => (page.props.locale === 'en' ? s.name_en || s.name_km : s.name_km || s.name_en);

// One subject or all of them across; phones always show one.
const focusId = ref(props.focus && props.subjects.some((s) => s.id === props.focus) ? props.focus : null);
const columns = computed(() => (focusId.value ? props.subjects.filter((s) => s.id === focusId.value) : props.subjects));
const phoneSubjectId = ref((props.subjects.find((s) => s.can_edit) ?? props.subjects[0])?.id);
const phoneSubject = computed(() => props.subjects.find((s) => s.id === phoneSubjectId.value));

// A refused cell is not filled, whatever it shows.
const filled = (subject) =>
    props.students.filter((st) => {
        const key = cellKey(st.enrollment_id, subject.id);
        return (saver.values[key] ?? '') !== '' && saver.state[key] !== 'error';
    }).length;
const errorCount = computed(() => Object.keys(saver.errors).length);
const firstError = computed(() => Object.values(saver.errors)[0]);
const canWrite = (subject) => subject.can_edit && props.exam.open && !saver.closed.value;

const switchClass = (event) =>
    router.get(`/exams/${props.exam.id}/scores`, {
        classroom: event.target.value,
        subject: focusId.value ?? undefined,
    });

// Submit only once every cell is saved, so the server's completeness check sees what is on screen.
const submitting = ref(null);
async function submit(subject) {
    submitting.value = subject.id;
    await saver.flush();
    router.post(
        `/exams/${props.exam.id}/subjects/${subject.sheet_id}/transition`,
        { status: 'submitted' },
        { preserveScroll: true, onFinish: () => (submitting.value = null) },
    );
}

const importForm = useForm({ file: null, classroom_id: props.classroom.id, subject_id: null });
const fileInput = ref(null);
function sendImport() {
    importForm.subject_id = focusId.value;
    importForm.post(`/exams/${props.exam.id}/scores/import`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            importForm.reset('file');
            fileInput.value.value = '';
        },
    });
}
const focusSubject = computed(() => props.subjects.find((s) => s.id === focusId.value));
const card = 'rounded-lg border border-border bg-surface-raised p-4 shadow-xs';
</script>

<template>
    <AdminLayout :title="`${exam.name} · ${classroom.name}`">
        <div class="space-y-4">
            <div class="flex flex-wrap items-end gap-3">
                <Link :href="`/exams/${exam.id}`" class="s-btn s-btn-ghost">
                    <ArrowLeft :size="18" :stroke-width="1.75" aria-hidden="true" />
                    {{ $t('exams.grid.back') }}
                </Link>
                <div>
                    <label for="classroom" class="mb-1 block text-[13px] leading-[22px] font-medium">{{
                        $t('people.classroom')
                    }}</label>
                    <select id="classroom" class="s-input" :value="classroom.id" @change="switchClass">
                        <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="hidden md:block">
                    <label for="focus" class="mb-1 block text-[13px] leading-[22px] font-medium">{{
                        $t('academic.subject')
                    }}</label>
                    <select id="focus" v-model="focusId" class="s-input">
                        <option :value="null">{{ $t('exams.grid.all_subjects') }}</option>
                        <option v-for="s in subjects" :key="s.id" :value="s.id">
                            {{ s.code }} · {{ subjectName(s) }}
                        </option>
                    </select>
                </div>
            </div>

            <p
                v-if="!exam.open || saver.closed.value"
                role="status"
                class="flex items-center gap-2 rounded-md border border-warning bg-warning-soft p-3 text-sm"
            >
                <Lock :size="18" :stroke-width="1.75" aria-hidden="true" />
                {{ $t('exams.grid.read_only', { status: $t(`exams.statuses.${exam.status}`) }) }}
            </p>
            <p v-else class="text-sm leading-6 text-ink-muted">{{ $t('exams.grid.help') }}</p>

            <p
                v-if="errorCount"
                role="alert"
                class="rounded-md border border-danger bg-danger-soft p-3 text-sm text-danger"
            >
                {{ $t('exams.grid.errors', { count: errorCount }) }} {{ firstError }}
            </p>

            <!-- Progress and submit, one card per subject shown -->
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <section v-for="s in columns" :key="s.id" :class="card" class="space-y-2" :aria-label="subjectName(s)">
                    <div class="flex items-center justify-between gap-2">
                        <span class="truncate font-semibold"
                            ><span class="font-mono text-xs text-ink-muted">{{ s.code }}</span>
                            {{ subjectName(s) }}</span
                        >
                        <StatusPill :status="s.status" :label="$t(`exams.subject_statuses.${s.status}`)" />
                    </div>
                    <div class="flex items-center gap-2 text-sm tabular-nums">
                        <progress
                            class="h-2 flex-1 accent-primary"
                            :max="students.length || 1"
                            :value="filled(s)"
                            :aria-label="$t('exams.progress')"
                        />
                        {{ filled(s) }} / {{ students.length }}
                    </div>
                    <p v-if="s.status === 'returned' && s.returned_reason" class="text-xs text-danger">
                        {{ $t('exams.return_reason') }}: {{ s.returned_reason }}
                    </p>
                    <SButton
                        v-if="canWrite(s)"
                        variant="primary"
                        class="s-btn-compact"
                        :loading="submitting === s.id"
                        :disabled="filled(s) < students.length"
                        @click="submit(s)"
                    >
                        <Send :size="14" aria-hidden="true" />
                        {{ $t('exams.actions.submit_subject') }}
                    </SButton>
                </section>
            </div>

            <!-- Excel round trip for one subject -->
            <section
                v-if="focusSubject"
                :class="card"
                class="hidden flex-wrap items-end gap-3 md:flex"
                :aria-label="$t('exams.grid.excel')"
            >
                <a
                    :href="`/exams/${exam.id}/scores/export?classroom=${classroom.id}&subject=${focusSubject.id}`"
                    class="s-btn"
                >
                    <Download :size="18" :stroke-width="1.75" aria-hidden="true" />
                    {{ $t('exams.grid.export') }}
                </a>
                <form v-if="canWrite(focusSubject)" class="flex flex-wrap items-end gap-2" @submit.prevent="sendImport">
                    <div>
                        <label for="score-file" class="mb-1 block text-[13px] leading-[22px] font-medium">{{
                            $t('exams.grid.import_file')
                        }}</label>
                        <input
                            id="score-file"
                            ref="fileInput"
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            class="text-sm"
                            @input="importForm.file = $event.target.files[0]"
                        />
                    </div>
                    <SButton type="submit" :loading="importForm.processing" :disabled="!importForm.file">
                        <Upload :size="18" :stroke-width="1.75" aria-hidden="true" />
                        {{ $t('exams.grid.import') }}
                    </SButton>
                </form>
                <p v-if="importForm.errors.file" class="text-sm text-danger">{{ importForm.errors.file }}</p>
                <ul v-if="page.props.flash.scoreImportErrors?.length" class="w-full list-disc pl-5 text-sm text-danger">
                    <li v-for="e in page.props.flash.scoreImportErrors" :key="e.line">
                        {{ $t('exams.grid.import_line', { line: e.line }) }} {{ e.error }}
                    </li>
                </ul>
            </section>
            <p v-else class="hidden text-sm text-ink-muted md:block">{{ $t('exams.grid.excel_hint') }}</p>

            <!-- Desktop: the spreadsheet -->
            <div class="hidden md:block">
                <ScoreGrid :students="students" :columns="columns" :saver="saver" :disabled="!exam.open" />
            </div>

            <!-- Phone: one student per card, one subject at a time, a large number pad -->
            <div class="space-y-3 md:hidden">
                <label for="phone-subject" class="block text-[13px] leading-[22px] font-medium">{{
                    $t('academic.subject')
                }}</label>
                <select id="phone-subject" v-model="phoneSubjectId" class="s-input">
                    <option v-for="s in subjects" :key="s.id" :value="s.id">
                        {{ s.code }} · {{ subjectName(s) }} (/{{ s.max_score }})
                    </option>
                </select>
                <article v-for="st in students" :key="st.enrollment_id" :class="card" class="flex items-center gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">
                            <span class="font-mono text-xs text-ink-muted">{{ st.roll_no }}.</span> {{ st.name_km }}
                        </p>
                        <p v-if="saver.errors[cellKey(st.enrollment_id, phoneSubjectId)]" class="text-xs text-danger">
                            {{ saver.errors[cellKey(st.enrollment_id, phoneSubjectId)] }}
                        </p>
                        <p
                            v-else-if="saver.state[cellKey(st.enrollment_id, phoneSubjectId)] === 'saved'"
                            class="text-xs text-success"
                        >
                            {{ $t('exams.grid.saved') }}
                        </p>
                    </div>
                    <template v-if="phoneSubject && canWrite(phoneSubject)">
                        <button
                            v-for="letter in ['A', 'E']"
                            :key="letter"
                            type="button"
                            class="s-btn s-btn-ghost size-11 px-0 font-mono"
                            :aria-label="$t(`exams.grid.mark_${letter}`, { name: st.name_km })"
                            @click="saver.set(st.enrollment_id, phoneSubjectId, letter)"
                        >
                            {{ letter }}
                        </button>
                    </template>
                    <input
                        :value="saver.values[cellKey(st.enrollment_id, phoneSubjectId)] ?? ''"
                        :readonly="!phoneSubject || !canWrite(phoneSubject)"
                        inputmode="decimal"
                        autocomplete="off"
                        class="s-input h-12 w-24 text-right font-mono text-xl tabular-nums"
                        :aria-label="`${st.name_km} — ${phoneSubject ? subjectName(phoneSubject) : ''}`"
                        :aria-invalid="saver.state[cellKey(st.enrollment_id, phoneSubjectId)] === 'error'"
                        @input="saver.set(st.enrollment_id, phoneSubjectId, $event.target.value)"
                    />
                </article>
            </div>
        </div>
    </AdminLayout>
</template>
