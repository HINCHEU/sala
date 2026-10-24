<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Check, X } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import MentionChip from '@/Components/MentionChip.vue';
import Modal from '@/Components/Modal.vue';
import SButton from '@/Components/SButton.vue';
import SField from '@/Components/SField.vue';
import StatusPill from '@/Components/StatusPill.vue';

const props = defineProps({
    exam: { type: Object, required: true },
    classroom: { type: Object, required: true },
    classrooms: { type: Array, required: true },
    subjects: { type: Array, required: true },
    rows: { type: Array, required: true },
    divisor: { type: Number, default: null },
    statistics: { type: Array, required: true },
    changes: { type: Array, required: true },
    canDecide: { type: Boolean, default: false },
});

const page = usePage();
const subjectName = (s) => (page.props.locale === 'en' ? s.name_en || s.name_km : s.name_km || s.name_en);
const calculating = computed(() => props.exam.locked && props.exam.results_version === 0);
const fmt = (n) => (n === null || n === undefined ? '—' : Number(n).toFixed(2));
const stat = (subjectId) => props.statistics.find((s) => s.subject_id === subjectId);

// Right after locking the job is still running: refresh until its version lands.
let timer = null;
onMounted(() => {
    timer = setInterval(() => calculating.value && router.reload({ only: ['exam', 'rows'] }), 3000);
});
onBeforeUnmount(() => clearInterval(timer));

const switchClass = (event) => router.get(`/exams/${props.exam.id}/results`, { classroom: event.target.value });

// A teacher asks for a correction by clicking a cell of their own subject.
const requesting = ref(null);
const changeForm = useForm({
    classroom_id: props.classroom.id,
    enrollment_id: null,
    subject_id: null,
    new_value: '',
    reason: '',
});
function openRequest(row, subject) {
    if (!props.exam.locked || !subject.can_request) return;
    requesting.value = { row, subject };
    changeForm.reset();
    changeForm.clearErrors();
    Object.assign(changeForm, {
        classroom_id: props.classroom.id,
        enrollment_id: row.enrollment_id,
        subject_id: subject.id,
    });
}
const sendRequest = () =>
    changeForm.post(`/exams/${props.exam.id}/changes`, {
        preserveScroll: true,
        onSuccess: () => (requesting.value = null),
    });

const decide = (change, approve) =>
    router.post(`/exams/${props.exam.id}/changes/${change.id}/decide`, { approve }, { preserveScroll: true });

const card = 'rounded-lg border border-border bg-surface-raised shadow-xs';
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
                <Link
                    v-if="exam.locked && exam.results_version"
                    :href="`/exams/${exam.id}/results/grade?grade=${classroom.grade_level_id}`"
                    class="s-btn"
                >
                    {{ $t('results.grade_ranking') }}
                </Link>
                <Link
                    :href="`/results/classes/${classroom.id}${exam.term_id ? `?term=${exam.term_id}` : ''}`"
                    class="s-btn"
                >
                    {{ $t('results.periods') }}
                </Link>
            </div>

            <p v-if="!exam.locked" role="status" class="rounded-md border border-warning bg-warning-soft p-3 text-sm">
                {{ $t('results.live') }}
            </p>
            <p
                v-else-if="calculating"
                role="status"
                class="flex items-center gap-2 rounded-md border border-border bg-surface-sunken p-3 text-sm"
            >
                <span class="s-spin" aria-hidden="true" /> {{ $t('results.calculating') }}
            </p>
            <p v-else class="flex flex-wrap items-center gap-2 text-sm text-ink-muted">
                <StatusPill :status="exam.status" :label="$t(`exams.statuses.${exam.status}`)" />
                {{ $t('results.calculated', { date: exam.computed_at, version: exam.results_version }) }}
                <span v-if="divisor">· {{ $t('results.divisor', { divisor }) }}</span>
            </p>
            <p v-if="exam.locked && subjects.some((s) => s.can_request)" class="text-sm text-ink-muted">
                {{ $t('results.change.hint') }}
            </p>

            <section :class="card" class="overflow-x-auto" :aria-label="$t('results.class_sheet')">
                <table class="w-full text-sm">
                    <thead class="bg-surface-sunken text-left text-xs font-semibold text-ink-muted">
                        <tr>
                            <th scope="col" class="px-2 py-2 text-right">{{ $t('results.rank') }}</th>
                            <th scope="col" class="px-3 py-2">{{ $t('people.name') }}</th>
                            <th
                                v-for="s in subjects"
                                :key="s.id"
                                scope="col"
                                class="px-2 py-2 text-right"
                                :title="subjectName(s)"
                            >
                                <span class="font-mono">{{ s.code }}</span>
                                <span class="block font-normal">/ {{ s.max }}</span>
                            </th>
                            <th scope="col" class="px-2 py-2 text-right">{{ $t('results.total') }}</th>
                            <th scope="col" class="px-2 py-2 text-right">{{ $t('results.average') }}</th>
                            <th scope="col" class="px-2 py-2">{{ $t('results.mention') }}</th>
                            <th scope="col" class="px-2 py-2">{{ $t('results.result') }}</th>
                            <th v-if="exam.locked" scope="col" class="px-2 py-2 text-right">
                                {{ $t('results.rank_grade') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="row in rows" :key="row.enrollment_id">
                            <td class="px-2 py-1.5 text-right font-mono font-semibold tabular-nums">
                                <span v-if="row.rank_class">{{ row.rank_class }}</span>
                                <span v-else class="text-xs font-normal text-ink-muted">{{
                                    $t('results.not_ranked')
                                }}</span>
                            </td>
                            <th scope="row" class="px-3 py-1.5 text-left font-medium whitespace-nowrap">
                                {{ row.name_km }}
                            </th>
                            <td
                                v-for="s in subjects"
                                :key="s.id"
                                class="px-2 py-1.5 text-right font-mono tabular-nums"
                                :class="[
                                    row.cells[s.id] === undefined && !exam.locked && 'bg-warning-soft text-warning',
                                    exam.locked && s.can_request && 'cursor-pointer hover:bg-primary-soft',
                                ]"
                                :title="row.cells[s.id] === undefined ? $t('results.missing') : ''"
                                @click="openRequest(row, s)"
                            >
                                {{ row.cells[s.id] ?? '—' }}
                            </td>
                            <td class="px-2 py-1.5 text-right font-mono tabular-nums">{{ fmt(row.total) }}</td>
                            <td class="px-2 py-1.5 text-right font-mono font-semibold tabular-nums">
                                {{ fmt(row.average) }}
                            </td>
                            <td class="px-2 py-1.5"><MentionChip :mention="row.mention" /></td>
                            <td class="px-2 py-1.5">
                                <span :class="row.passed ? 'text-success' : 'text-danger'">
                                    {{ row.passed ? $t('results.passed') : $t('results.failed') }}
                                </span>
                            </td>
                            <td v-if="exam.locked" class="px-2 py-1.5 text-right font-mono tabular-nums">
                                {{ row.rank_grade ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section :class="card" class="overflow-x-auto p-4" aria-labelledby="stats-title">
                <h2 id="stats-title" class="mb-2 text-lg font-semibold">{{ $t('results.statistics') }}</h2>
                <table class="w-full text-sm">
                    <thead class="text-left text-xs text-ink-muted">
                        <tr>
                            <th scope="col" class="py-1">{{ $t('academic.subject') }}</th>
                            <th scope="col" class="py-1 text-right">{{ $t('results.subject_average') }}</th>
                            <th scope="col" class="py-1 text-right">{{ $t('results.pass_rate') }}</th>
                            <th scope="col" class="py-1 text-right">{{ $t('results.sat') }}</th>
                            <th scope="col" class="py-1 text-right">{{ $t('results.passed_count') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="s in subjects" :key="s.id">
                            <th scope="row" class="py-1.5 text-left font-medium">
                                {{ s.code }} · {{ subjectName(s) }}
                            </th>
                            <td class="py-1.5 text-right font-mono tabular-nums">
                                {{ fmt(stat(s.id)?.average) }} / {{ s.max }}
                            </td>
                            <td class="py-1.5 text-right font-mono tabular-nums">
                                {{ stat(s.id)?.pass_rate ?? '—' }}%
                            </td>
                            <td class="py-1.5 text-right font-mono tabular-nums">
                                {{ stat(s.id)?.sat }} / {{ stat(s.id)?.sat_female }}
                            </td>
                            <td class="py-1.5 text-right font-mono tabular-nums">
                                {{ stat(s.id)?.passed }} / {{ stat(s.id)?.passed_female }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section v-if="exam.locked" :class="card" class="p-4" aria-labelledby="changes-title">
                <h2 id="changes-title" class="mb-2 text-lg font-semibold">{{ $t('results.change.title') }}</h2>
                <p v-if="changes.length === 0" class="text-sm text-ink-muted">{{ $t('results.change.none') }}</p>
                <ul class="divide-y divide-border text-sm">
                    <li v-for="c in changes" :key="c.id" class="flex flex-wrap items-center gap-3 py-2">
                        <div class="min-w-0 flex-1">
                            <p>
                                <span class="font-semibold">{{ c.student }}</span> ·
                                <span class="font-mono">{{ c.subject }}</span
                                >:
                                <span class="font-mono">{{ c.old_value ?? '—' }} → {{ c.new_value }}</span>
                            </p>
                            <p class="text-ink-muted">
                                {{ c.reason }} · {{ $t('results.change.by', { name: c.requested_by }) }}
                            </p>
                            <p v-if="c.decided_by" class="text-xs text-ink-muted">
                                {{ $t('results.change.decided_by', { name: c.decided_by })
                                }}<template v-if="c.decision_note">: {{ c.decision_note }}</template>
                            </p>
                        </div>
                        <StatusPill
                            :status="{ pending: 'submitted', approved: 'approved', rejected: 'danger' }[c.status]"
                            :label="$t(`results.change.statuses.${c.status}`)"
                        />
                        <template v-if="canDecide && c.pending">
                            <SButton variant="primary" class="s-btn-compact" @click="decide(c, true)">
                                <Check :size="14" aria-hidden="true" /> {{ $t('results.change.approve') }}
                            </SButton>
                            <SButton variant="ghost" class="s-btn-compact text-danger" @click="decide(c, false)">
                                <X :size="14" aria-hidden="true" /> {{ $t('results.change.reject') }}
                            </SButton>
                        </template>
                    </li>
                </ul>
            </section>
        </div>

        <Modal :show="requesting !== null" max-width="md" @close="requesting = null">
            <form v-if="requesting" class="space-y-4 p-6" novalidate @submit.prevent="sendRequest">
                <h2 class="text-lg font-bold">
                    {{
                        $t('results.change.request_for', {
                            subject: subjectName(requesting.subject),
                            name: requesting.row.name_km,
                        })
                    }}
                </h2>
                <p class="text-sm">
                    {{ $t('results.change.current') }}:
                    <span class="font-mono">{{ requesting.row.cells[requesting.subject.id] ?? '—' }}</span> /
                    {{ requesting.subject.max }}
                </p>
                <SField
                    id="new_value"
                    v-slot="f"
                    :label="$t('results.change.new_value')"
                    :error="changeForm.errors.new_value"
                >
                    <input
                        id="new_value"
                        v-model="changeForm.new_value"
                        inputmode="decimal"
                        class="s-input font-mono"
                        :aria-invalid="f.invalid"
                        :aria-describedby="f.describedby"
                    />
                </SField>
                <SField id="reason" v-slot="f" :label="$t('results.change.reason')" :error="changeForm.errors.reason">
                    <textarea
                        id="reason"
                        v-model="changeForm.reason"
                        rows="3"
                        class="s-input"
                        :aria-invalid="f.invalid"
                        :aria-describedby="f.describedby"
                    />
                </SField>
                <div class="flex justify-end gap-2">
                    <SButton variant="ghost" @click="requesting = null">{{ $t('app.cancel') }}</SButton>
                    <SButton type="submit" variant="primary" :loading="changeForm.processing">{{
                        $t('results.change.send')
                    }}</SButton>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
