<script setup>
import { usePage } from '@inertiajs/vue3';
import { cellKey } from '@/lib/useScoreSaver';

/**
 * Students down, subjects across, like Excel: arrows and Enter move between cells, Tab moves right,
 * and a block pasted from Excel fills down and across from the cell it lands in.
 */
const props = defineProps({
    students: { type: Array, required: true },
    columns: { type: Array, required: true },
    saver: { type: Object, required: true },
    disabled: { type: Boolean, default: false },
});

const page = usePage();
const subjectName = (s) => (page.props.locale === 'en' ? s.name_en || s.name_km : s.name_km || s.name_en);
const editable = (column) => column.can_edit && !props.disabled && !props.saver.closed.value;
const id = (row, col) => `cell-${row}-${col}`;

function move(row, col) {
    const input = document.getElementById(id(row, col));
    if (input) {
        input.focus();
        input.select();
    }
}

function onKey(event, row, col) {
    const input = event.target;
    const atStart = input.selectionStart === 0 && input.selectionEnd === 0;
    const atEnd = input.selectionStart === input.value.length;
    const allSelected = input.selectionStart === 0 && input.selectionEnd === input.value.length;

    if (event.key === 'ArrowDown' || (event.key === 'Enter' && !event.shiftKey)) move(row + 1, col);
    else if (event.key === 'ArrowUp' || (event.key === 'Enter' && event.shiftKey)) move(row - 1, col);
    else if (event.key === 'ArrowRight' && (atEnd || allSelected)) move(row, col + 1);
    else if (event.key === 'ArrowLeft' && (atStart || allSelected)) move(row, col - 1);
    else return;
    event.preventDefault();
}

// One value is typed as usual; a block (tabs or new lines, as Excel copies) fills from this cell.
function onPaste(event, row, col) {
    const text = event.clipboardData.getData('text/plain').replace(/\r/g, '');
    if (!/[\t\n]/.test(text.trim())) return;
    event.preventDefault();

    const lines = text.replace(/\n$/, '').split('\n');
    lines.forEach((line, i) => {
        const student = props.students[row + i];
        if (!student) return;
        line.split('\t').forEach((value, j) => {
            const column = props.columns[col + j];
            if (column && editable(column)) props.saver.set(student.enrollment_id, column.id, value.trim());
        });
    });
}

const dot = {
    pending: 'bg-ink-muted/40',
    saving: 'bg-warning animate-pulse',
    saved: 'bg-success',
    error: 'bg-danger',
};
</script>

<template>
    <div class="overflow-auto rounded-lg border border-border bg-surface-raised shadow-xs">
        <table class="w-full border-collapse text-sm">
            <caption class="sr-only">
                {{
                    $t('exams.grid.caption')
                }}
            </caption>
            <thead class="sticky top-0 z-10 bg-surface-sunken text-left">
                <tr>
                    <th
                        scope="col"
                        class="sticky left-0 z-20 w-12 bg-surface-sunken px-2 py-2 text-xs font-semibold text-ink-muted"
                    >
                        {{ $t('people.roll_no') }}
                    </th>
                    <th
                        scope="col"
                        class="sticky left-12 z-20 min-w-44 bg-surface-sunken px-3 py-2 text-xs font-semibold text-ink-muted"
                    >
                        {{ $t('people.name') }}
                    </th>
                    <th
                        v-for="column in columns"
                        :key="column.id"
                        scope="col"
                        class="min-w-24 px-2 py-2 text-right align-bottom"
                    >
                        <span class="block font-mono text-xs font-semibold">{{ column.code }}</span>
                        <span
                            class="block truncate text-[11px] font-normal text-ink-muted"
                            :title="subjectName(column)"
                        >
                            {{ subjectName(column) }}
                        </span>
                        <span class="block font-mono text-[11px] text-ink-muted">/ {{ column.max_score ?? '—' }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(student, row) in students" :key="student.enrollment_id" class="hover:bg-surface-sunken/40">
                    <td
                        class="sticky left-0 bg-surface-raised px-2 py-1 text-right font-mono text-xs tabular-nums text-ink-muted"
                    >
                        {{ student.roll_no }}
                    </td>
                    <th
                        scope="row"
                        class="sticky left-12 bg-surface-raised px-3 py-1 text-left font-medium whitespace-nowrap"
                    >
                        {{ student.name_km }}
                    </th>
                    <td v-for="(column, col) in columns" :key="column.id" class="s-grid-cell relative p-0">
                        <input
                            :id="id(row, col)"
                            :value="saver.values[cellKey(student.enrollment_id, column.id)] ?? ''"
                            :readonly="!editable(column)"
                            inputmode="decimal"
                            autocomplete="off"
                            class="h-9 w-full bg-transparent px-2 text-right font-mono tabular-nums outline-none read-only:text-ink-muted"
                            :class="
                                saver.state[cellKey(student.enrollment_id, column.id)] === 'error' &&
                                'bg-danger-soft text-danger'
                            "
                            :aria-label="`${student.name_km} — ${subjectName(column)}`"
                            :aria-invalid="saver.state[cellKey(student.enrollment_id, column.id)] === 'error'"
                            :title="saver.errors[cellKey(student.enrollment_id, column.id)] ?? ''"
                            @focus="$event.target.select()"
                            @input="saver.set(student.enrollment_id, column.id, $event.target.value)"
                            @keydown="onKey($event, row, col)"
                            @paste="onPaste($event, row, col)"
                        />
                        <span
                            v-if="saver.state[cellKey(student.enrollment_id, column.id)]"
                            class="pointer-events-none absolute top-1 left-1 size-1.5 rounded-full"
                            :class="dot[saver.state[cellKey(student.enrollment_id, column.id)]]"
                            aria-hidden="true"
                        />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
