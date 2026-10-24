<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import MentionChip from '@/Components/MentionChip.vue';
import { BarChart3 } from 'lucide-vue-next';

const props = defineProps({
    classroom: { type: Object, required: true },
    terms: { type: Array, required: true },
    term: { type: Object, default: null },
    rows: { type: Array, required: true },
});

const page = usePage();
const termName = (t) => (page.props.locale === 'en' ? t.name_en : t.name_km);
const fmt = (n) => (n === null || n === undefined ? '—' : Number(n).toFixed(2));
const hasResults = props.rows.some((r) => r.average !== null);
const tab = (active) =>
    active ? 'border-primary font-semibold text-primary' : 'border-transparent text-ink-muted hover:text-ink';
</script>

<template>
    <AdminLayout :title="`${$t('results.periods')} · ${classroom.name}`">
        <nav :aria-label="$t('results.periods')" class="mb-4 overflow-x-auto">
            <ul class="flex min-w-max gap-1 border-b border-border">
                <li v-for="t in terms" :key="t.id">
                    <Link
                        :href="`/results/classes/${classroom.id}?term=${t.id}`"
                        class="-mb-px flex min-h-10 items-center border-b-2 px-3 text-sm"
                        :class="tab(term?.id === t.id)"
                        :aria-current="term?.id === t.id ? 'page' : undefined"
                    >
                        {{ termName(t) }}
                    </Link>
                </li>
                <li>
                    <Link
                        :href="`/results/classes/${classroom.id}`"
                        class="-mb-px flex min-h-10 items-center border-b-2 px-3 text-sm"
                        :class="tab(!term)"
                        :aria-current="!term ? 'page' : undefined"
                    >
                        {{ $t('results.year') }}
                    </Link>
                </li>
            </ul>
        </nav>

        <EmptyState v-if="!hasResults" :icon="BarChart3" :title="$t('results.no_results')" />

        <section v-else class="overflow-x-auto rounded-lg border border-border bg-surface-raised shadow-xs">
            <table class="w-full text-sm">
                <thead class="bg-surface-sunken text-left text-xs font-semibold text-ink-muted">
                    <tr>
                        <th scope="col" class="px-2 py-2 text-right">{{ $t('results.rank') }}</th>
                        <th scope="col" class="px-3 py-2">{{ $t('people.name') }}</th>
                        <template v-if="term">
                            <th scope="col" class="px-2 py-2 text-right">{{ $t('results.monthly_average') }}</th>
                            <th scope="col" class="px-2 py-2 text-right">{{ $t('results.exam_average') }}</th>
                            <th scope="col" class="px-2 py-2 text-right">{{ $t('results.semester_average') }}</th>
                        </template>
                        <template v-else>
                            <th
                                v-for="(t, i) in terms.slice(0, 2)"
                                :key="t.id"
                                scope="col"
                                class="px-2 py-2 text-right"
                            >
                                {{ $t('results.semester_n', { n: i + 1 }) }}
                            </th>
                            <th scope="col" class="px-2 py-2 text-right">{{ $t('results.annual_average') }}</th>
                        </template>
                        <th scope="col" class="px-2 py-2">{{ $t('results.mention') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr
                        v-for="row in [...rows].sort(
                            (a, b) => (a.rank ?? 1e9) - (b.rank ?? 1e9) || a.roll_no - b.roll_no,
                        )"
                        :key="row.enrollment_id"
                    >
                        <td class="px-2 py-1.5 text-right font-mono font-semibold tabular-nums">
                            {{ row.rank ?? '—' }}
                        </td>
                        <th scope="row" class="px-3 py-1.5 text-left font-medium">{{ row.name_km }}</th>
                        <template v-if="term">
                            <td class="px-2 py-1.5 text-right font-mono tabular-nums">
                                {{ fmt(row.monthly_average) }}
                                <span v-if="row.monthly_count" class="text-xs text-ink-muted">{{
                                    $t('results.months_count', { count: row.monthly_count })
                                }}</span>
                            </td>
                            <td class="px-2 py-1.5 text-right font-mono tabular-nums">{{ fmt(row.exam_average) }}</td>
                        </template>
                        <template v-else>
                            <td
                                v-for="(s, i) in row.semesters"
                                :key="i"
                                class="px-2 py-1.5 text-right font-mono tabular-nums"
                            >
                                {{ fmt(s) }}
                            </td>
                        </template>
                        <td class="px-2 py-1.5 text-right font-mono font-semibold tabular-nums">
                            {{ fmt(row.average) }}
                        </td>
                        <td class="px-2 py-1.5"><MentionChip :mention="row.mention" /></td>
                    </tr>
                </tbody>
            </table>
        </section>
    </AdminLayout>
</template>
