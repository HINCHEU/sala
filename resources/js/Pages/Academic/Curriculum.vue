<script setup>
import { computed, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { BookOpen, Copy } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AcademicTabs from '@/Components/AcademicTabs.vue';
import EmptyState from '@/Components/EmptyState.vue';
import SButton from '@/Components/SButton.vue';
import { confirmDanger } from '@/lib/alert';

const props = defineProps({
    years: { type: Array, required: true },
    grades: { type: Array, required: true },
    tracks: { type: Array, required: true },
    subjects: { type: Array, required: true },
    selection: { type: Object, required: true },
    rows: { type: Array, required: true },
    divisor: { type: [String, Number], default: null },
});

const page = usePage();
const name = (row) => (page.props.locale === 'en' ? row.name_en : row.name_km);
const grade = computed(() => props.grades.find((g) => g.id === props.selection.grade));

// One line per school subject; "included" lines become this grade's curriculum.
const saved = Object.fromEntries(props.rows.map((r) => [r.subject_id, r]));
const lines = reactive(
    props.subjects.map((subject) => ({
        subject,
        included: subject.id in saved,
        max_score: saved[subject.id]?.max_score ?? '',
        coefficient: saved[subject.id]?.coefficient ?? '1',
        pass_score: saved[subject.id]?.pass_score ?? '',
        sort: saved[subject.id]?.sort ?? '',
    })),
);
const divisor = ref(props.divisor ?? '');
const processing = ref(false);

const included = computed(() => lines.filter((l) => l.included));
const totalMax = computed(() => included.value.reduce((sum, l) => sum + (Number(l.max_score) || 0), 0));

// Validation errors come back as rows.{index}.field, where index is the position among included lines.
const errorFor = (line, field) => {
    const index = included.value.indexOf(line);
    return index === -1 ? null : page.props.errors[`rows.${index}.${field}`];
};

function toggle(line) {
    if (line.included && line.sort === '') line.sort = included.value.length;
}

function select(key, value) {
    router.get('/academic/curriculum', { ...props.selection, [key]: value }, { preserveState: false });
}

function save() {
    router.put(
        '/academic/curriculum',
        {
            academic_year_id: props.selection.year,
            grade_level_id: props.selection.grade,
            track_id: props.selection.track,
            divisor: divisor.value === '' ? null : divisor.value,
            rows: included.value.map((l) => ({
                subject_id: l.subject.id,
                max_score: l.max_score,
                coefficient: l.coefficient,
                pass_score: l.pass_score,
                sort: l.sort === '' ? 0 : l.sort,
            })),
        },
        { preserveScroll: true, onStart: () => (processing.value = true), onFinish: () => (processing.value = false) },
    );
}

const copyFrom = ref(props.years.find((y) => y.id !== props.selection.year)?.id ?? '');
async function copy() {
    const from = props.years.find((y) => y.id === Number(copyFrom.value));
    const to = props.years.find((y) => y.id === props.selection.year);
    const ok = await confirmDanger({
        title: trans('academic.copy_confirm', { from: from.name, to: to.name }),
        text: trans('academic.copy_text', { to: to.name }),
        confirmText: trans('academic.copy'),
    });
    if (ok)
        router.post(
            '/academic/curriculum/copy',
            { from_year_id: from.id, to_year_id: to.id },
            { preserveState: false },
        );
}

const cell = 's-input w-24 tabular-nums';
</script>

<template>
    <AdminLayout :title="$t('academic.title')">
        <AcademicTabs />

        <EmptyState v-if="!selection.year" :icon="BookOpen" :title="$t('academic.no_year')" />
        <EmptyState
            v-else-if="!grades.length || !subjects.length"
            :icon="BookOpen"
            :title="$t('academic.need_structure')"
        />

        <div v-else class="space-y-4">
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label for="year" class="mb-1 block text-[13px] leading-[22px] font-medium">{{
                        $t('academic.year')
                    }}</label>
                    <select
                        id="year"
                        class="s-input"
                        :value="selection.year"
                        @change="select('year', $event.target.value)"
                    >
                        <option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}</option>
                    </select>
                </div>
                <div>
                    <label for="grade" class="mb-1 block text-[13px] leading-[22px] font-medium">{{
                        $t('academic.grade')
                    }}</label>
                    <select
                        id="grade"
                        class="s-input"
                        :value="selection.grade"
                        @change="select('grade', $event.target.value)"
                    >
                        <option v-for="g in grades" :key="g.id" :value="g.id">{{ name(g) }}</option>
                    </select>
                </div>
                <div v-if="grade?.has_tracks">
                    <label for="track" class="mb-1 block text-[13px] leading-[22px] font-medium">{{
                        $t('academic.track')
                    }}</label>
                    <select
                        id="track"
                        class="s-input"
                        :value="selection.track"
                        @change="select('track', $event.target.value)"
                    >
                        <option v-for="t in tracks" :key="t.id" :value="t.id">{{ name(t) }}</option>
                    </select>
                </div>
                <div v-if="years.length > 1" class="ml-auto flex items-end gap-2">
                    <div>
                        <label for="copy-from" class="mb-1 block text-[13px] leading-[22px] font-medium">
                            {{ $t('academic.copy_from') }}
                        </label>
                        <select id="copy-from" v-model="copyFrom" class="s-input">
                            <option v-for="y in years.filter((y) => y.id !== selection.year)" :key="y.id" :value="y.id">
                                {{ y.name }}
                            </option>
                        </select>
                    </div>
                    <SButton @click="copy">
                        <Copy :size="20" :stroke-width="1.75" aria-hidden="true" />
                        {{ $t('academic.copy') }}
                    </SButton>
                </div>
            </div>

            <p class="text-sm leading-6 text-ink-muted">{{ $t('academic.curriculum_hint') }}</p>
            <p v-if="page.props.errors.track_id" class="text-sm text-danger">{{ page.props.errors.track_id }}</p>

            <div class="overflow-x-auto rounded-md border border-border bg-surface-raised shadow-sm">
                <table class="w-full text-sm">
                    <thead class="bg-surface-sunken text-left">
                        <tr class="text-[13px] leading-[22px]">
                            <th scope="col" class="px-3 py-2 font-medium">{{ $t('academic.included') }}</th>
                            <th scope="col" class="px-3 py-2 font-medium">{{ $t('academic.subject') }}</th>
                            <th scope="col" class="px-3 py-2 font-medium">{{ $t('academic.max_score') }}</th>
                            <th scope="col" class="px-3 py-2 font-medium">{{ $t('academic.coefficient') }}</th>
                            <th scope="col" class="px-3 py-2 font-medium">{{ $t('academic.pass_score') }}</th>
                            <th scope="col" class="px-3 py-2 font-medium">{{ $t('academic.sort') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="line in lines" :key="line.subject.id" :class="!line.included && 'text-ink-muted'">
                            <td class="px-3 py-1">
                                <input
                                    :id="`inc-${line.subject.id}`"
                                    v-model="line.included"
                                    type="checkbox"
                                    class="size-5 accent-primary"
                                    @change="toggle(line)"
                                />
                            </td>
                            <td class="px-3 py-1">
                                <label :for="`inc-${line.subject.id}`" class="block">
                                    {{ line.subject.name_km }}
                                    <span class="block text-[13px] leading-5 text-ink-muted">{{
                                        line.subject.name_en
                                    }}</span>
                                </label>
                            </td>
                            <td
                                v-for="field in ['max_score', 'coefficient', 'pass_score', 'sort']"
                                :key="field"
                                class="px-2 py-1 align-top"
                            >
                                <input
                                    v-model="line[field]"
                                    type="number"
                                    :step="field === 'sort' ? 1 : 0.01"
                                    min="0"
                                    :disabled="!line.included"
                                    :class="cell"
                                    :aria-label="`${$t(`academic.${field}`)} ${line.subject.name_km}`"
                                    :aria-invalid="!!errorFor(line, field)"
                                />
                                <p v-if="errorFor(line, field)" class="mt-1 max-w-40 text-[13px] leading-5 text-danger">
                                    {{ errorFor(line, field) }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <label for="divisor" class="mb-1 block text-[13px] leading-[22px] font-medium">{{
                        $t('academic.divisor')
                    }}</label>
                    <input
                        id="divisor"
                        v-model="divisor"
                        type="number"
                        step="0.01"
                        min="0"
                        class="s-input w-32 tabular-nums"
                        aria-describedby="divisor-hint"
                        :aria-invalid="!!page.props.errors.divisor"
                    />
                    <p id="divisor-hint" class="mt-1 text-sm leading-6 text-ink-muted">
                        {{ $t('academic.divisor_hint') }} · {{ $t('academic.total_max', { total: totalMax }) }}
                    </p>
                    <p v-if="page.props.errors.divisor" class="text-sm text-danger">{{ page.props.errors.divisor }}</p>
                </div>
                <SButton variant="primary" :loading="processing" @click="save">
                    {{ processing ? $t('app.saving') : $t('app.save') }}
                </SButton>
            </div>
        </div>
    </AdminLayout>
</template>
