<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import SButton from '@/Components/SButton.vue';
import { confirmDanger } from '@/lib/alert';

/**
 * A card with a table you edit in place: one row at a time, saved with POST (new) or PUT (existing).
 * columns: [{ key, label, type: text|number|date|select|checkbox, options: [{ value, label }], nullable, readonly, width }]
 */
const props = defineProps({
    title: { type: String, required: true },
    rows: { type: Array, required: true },
    columns: { type: Array, required: true },
    baseUrl: { type: String, required: true },
    // Where existing rows live, when it differs from where new ones are posted (nested creates).
    itemUrl: { type: String, default: null },
    defaults: { type: Object, default: () => ({}) },
    emptyText: { type: String, default: '' },
    nameKey: { type: String, default: null },
});

const editing = ref(null); // row id, 'new', or null

const blank = () => Object.fromEntries(props.columns.map((c) => [c.key, c.type === 'checkbox' ? false : '']));

const form = useForm({ ...blank(), ...props.defaults });

function open(row) {
    editing.value = row ? row.id : 'new';
    form.clearErrors();
    const values = { ...blank(), ...props.defaults };
    if (row) props.columns.forEach((c) => (values[c.key] = row[c.key] ?? (c.type === 'checkbox' ? false : '')));
    Object.assign(form, values);
}

const close = () => (editing.value = null);

function save() {
    const options = {
        preserveScroll: true,
        onSuccess: close,
    };
    // Empty selects and inputs mean "none", not an empty string.
    form.transform((data) => Object.fromEntries(Object.entries(data).map(([k, v]) => [k, v === '' ? null : v])));
    editing.value === 'new'
        ? form.post(props.baseUrl, options)
        : form.put(`${props.itemUrl ?? props.baseUrl}/${editing.value}`, options);
}

async function remove(row) {
    const name = row[props.nameKey ?? props.columns[0].key];
    const ok = await confirmDanger({
        title: trans('academic.delete_confirm', { name }),
        text: trans('academic.delete_text'),
        confirmText: trans('academic.delete'),
    });
    if (ok) router.delete(`${props.itemUrl ?? props.baseUrl}/${row.id}`, { preserveScroll: true });
}

function display(row, column) {
    const value = row[column.key];
    if (column.type === 'checkbox') return value ? trans('academic.yes') : trans('academic.no');
    if (column.type === 'select') return column.options.find((o) => String(o.value) === String(value))?.label ?? '—';
    return value === null || value === '' || value === undefined ? '—' : value;
}
</script>

<template>
    <section class="rounded-md border border-border bg-surface-raised shadow-sm" :aria-label="title">
        <header class="flex flex-wrap items-center justify-between gap-2 border-b border-border px-4 py-3">
            <h2 class="text-lg leading-[30px] font-semibold">{{ title }}</h2>
            <SButton variant="ghost" :disabled="editing !== null" @click="open(null)">
                <Plus :size="20" :stroke-width="1.75" aria-hidden="true" />
                {{ $t('academic.add') }}
            </SButton>
        </header>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-sunken text-left">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            scope="col"
                            class="px-3 py-2 text-[13px] leading-[22px] font-medium whitespace-nowrap"
                        >
                            {{ column.label }}
                        </th>
                        <th scope="col" class="px-3 py-2">
                            <span class="sr-only">{{ $t('academic.actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <template v-for="row in [...rows, ...(editing === 'new' ? [{ id: 'new' }] : [])]" :key="row.id">
                        <tr v-if="editing === row.id" class="bg-primary-soft/40 align-top">
                            <td v-for="column in columns" :key="column.key" class="px-2 py-2" :class="column.width">
                                <span v-if="column.readonly" class="block px-1 py-3">{{ display(row, column) }}</span>
                                <select
                                    v-else-if="column.type === 'select'"
                                    v-model="form[column.key]"
                                    class="s-input min-w-28"
                                    :aria-label="column.label"
                                    :aria-invalid="!!form.errors[column.key]"
                                >
                                    <option v-if="column.nullable" value="">—</option>
                                    <option v-for="o in column.options" :key="o.value" :value="o.value">
                                        {{ o.label }}
                                    </option>
                                </select>
                                <input
                                    v-else-if="column.type === 'checkbox'"
                                    v-model="form[column.key]"
                                    type="checkbox"
                                    class="m-3 size-5 accent-primary"
                                    :aria-label="column.label"
                                />
                                <input
                                    v-else
                                    v-model="form[column.key]"
                                    :type="column.type ?? 'text'"
                                    class="s-input min-w-24"
                                    :class="column.type === 'number' && 'tabular-nums'"
                                    :aria-label="column.label"
                                    :aria-invalid="!!form.errors[column.key]"
                                />
                                <p v-if="form.errors[column.key]" class="mt-1 text-[13px] leading-[22px] text-danger">
                                    {{ form.errors[column.key] }}
                                </p>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                <div class="flex gap-1">
                                    <SButton variant="primary" :loading="form.processing" @click="save">
                                        {{ $t('app.save') }}
                                    </SButton>
                                    <SButton variant="ghost" @click="close">{{ $t('app.cancel') }}</SButton>
                                </div>
                            </td>
                        </tr>
                        <tr v-else>
                            <td v-for="column in columns" :key="column.key" class="px-3 py-2">
                                {{ display(row, column) }}
                            </td>
                            <td class="px-2 py-1 text-right whitespace-nowrap">
                                <slot name="actions" :row="row" />
                                <button
                                    type="button"
                                    class="s-btn s-btn-ghost px-2"
                                    :aria-label="`${$t('academic.edit')} ${row[nameKey ?? columns[0].key]}`"
                                    :disabled="editing !== null"
                                    @click="open(row)"
                                >
                                    <Pencil :size="18" :stroke-width="1.75" />
                                </button>
                                <button
                                    type="button"
                                    class="s-btn s-btn-ghost px-2 text-danger"
                                    :aria-label="`${$t('academic.delete')} ${row[nameKey ?? columns[0].key]}`"
                                    :disabled="editing !== null"
                                    @click="remove(row)"
                                >
                                    <Trash2 :size="18" :stroke-width="1.75" />
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr v-if="rows.length === 0 && editing !== 'new'">
                        <td :colspan="columns.length + 1" class="px-4 py-6 text-center text-ink-muted">
                            {{ emptyText || $t('academic.empty') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
