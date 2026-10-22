<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Download, Upload } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PeopleTabs from '@/Components/PeopleTabs.vue';
import SButton from '@/Components/SButton.vue';
import SField from '@/Components/SField.vue';

defineProps({
    year: { type: Object, default: null },
    columns: { type: Array, required: true },
    imports: { type: Array, required: true },
});

const form = useForm({ file: null });
const submit = () => form.post('/people/import', { forceFormData: true });

const required = ['last_name_km', 'first_name_km', 'gender'];
const card = 'space-y-4 rounded-md border border-border bg-surface-raised p-4 shadow-sm sm:p-6';
</script>

<template>
    <AdminLayout :title="$t('people.title')">
        <PeopleTabs />

        <div class="max-w-3xl space-y-6">
            <section :class="card" aria-labelledby="steps-title">
                <h2 id="steps-title" class="text-lg leading-[30px] font-semibold">{{ $t('people.import.title') }}</h2>
                <ol class="list-decimal space-y-1 pl-5 text-sm leading-6">
                    <li>{{ $t('people.import.step_template') }}</li>
                    <li>{{ $t('people.import.step_fill') }}</li>
                    <li>{{ $t('people.import.step_check') }}</li>
                </ol>
                <p v-if="year" class="text-sm leading-6 text-ink-muted">
                    {{ $t('people.import.year_note', { year: year.name }) }}
                </p>
                <p v-else role="alert" class="rounded-md border border-warning bg-warning-soft p-3 text-sm leading-6">
                    {{ $t('people.import.no_year_note') }}
                </p>

                <a href="/people/import/template" class="s-btn">
                    <Download :size="20" :stroke-width="1.75" aria-hidden="true" />
                    {{ $t('people.import.download_template') }}
                </a>

                <form
                    class="flex flex-wrap items-end gap-3 border-t border-border pt-4"
                    novalidate
                    @submit.prevent="submit"
                >
                    <SField
                        id="file"
                        v-slot="f"
                        :label="$t('people.import.file')"
                        :hint="$t('people.import.file_hint')"
                        :error="form.errors.file"
                    >
                        <input
                            id="file"
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            class="text-sm"
                            :aria-invalid="f.invalid"
                            :aria-describedby="f.describedby"
                            @input="form.file = $event.target.files[0]"
                        />
                    </SField>
                    <SButton type="submit" variant="primary" :loading="form.processing" :disabled="!form.file">
                        <Upload :size="20" :stroke-width="1.75" aria-hidden="true" />
                        {{ $t('people.import.check') }}
                    </SButton>
                </form>
            </section>

            <section :class="card" aria-labelledby="columns-title">
                <h2 id="columns-title" class="text-lg leading-[30px] font-semibold">
                    {{ $t('people.import.columns_title') }}
                </h2>
                <table class="w-full text-sm">
                    <thead class="text-left text-ink-muted">
                        <tr>
                            <th scope="col" class="w-12 py-1 font-medium">#</th>
                            <th scope="col" class="py-1 font-medium">{{ $t('people.import.column') }}</th>
                            <th scope="col" class="py-1 font-medium">{{ $t('people.import.accepts') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="(column, i) in columns" :key="column">
                            <td class="py-2 font-mono text-ink-muted">{{ String.fromCharCode(65 + i) }}</td>
                            <td class="py-2 font-medium">
                                {{ $t(`people.import.columns.${column}`) }}
                                <span
                                    v-if="required.includes(column)"
                                    class="text-danger"
                                    :title="$t('people.import.required')"
                                    >*</span
                                >
                            </td>
                            <td class="py-2 text-ink-muted">{{ $t(`people.import.accepts_by_column.${column}`) }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section v-if="imports.length" :class="card" aria-labelledby="recent-title">
                <h2 id="recent-title" class="text-lg leading-[30px] font-semibold">{{ $t('people.import.recent') }}</h2>
                <ul class="divide-y divide-border text-sm">
                    <li v-for="i in imports" :key="i.id" class="flex flex-wrap items-center gap-3 py-2">
                        <Link :href="`/people/import/${i.id}`" class="font-medium text-primary hover:underline">{{
                            i.original_name
                        }}</Link>
                        <span class="text-ink-muted">{{ $t(`people.import.statuses.${i.status}`) }}</span>
                        <span class="text-ink-muted tabular-nums">
                            {{
                                $t('people.import.summary', {
                                    rows: i.row_count,
                                    errors: i.error_count,
                                    imported: i.imported_count,
                                })
                            }}
                        </span>
                        <span class="ml-auto text-ink-muted tabular-nums">{{ i.created_at }}</span>
                    </li>
                </ul>
            </section>
        </div>
    </AdminLayout>
</template>
