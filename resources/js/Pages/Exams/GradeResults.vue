<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import MentionChip from '@/Components/MentionChip.vue';

defineProps({
    exam: { type: Object, required: true },
    grade: { type: Object, required: true },
    rows: { type: Array, required: true },
});

const page = usePage();
const gradeName = (g) => (page.props.locale === 'en' ? g.name_en : g.name_km);
</script>

<template>
    <AdminLayout :title="`${exam.name} · ${gradeName(grade)}`">
        <div class="space-y-4">
            <Link :href="`/exams/${exam.id}`" class="s-btn s-btn-ghost">
                <ArrowLeft :size="18" :stroke-width="1.75" aria-hidden="true" />
                {{ $t('exams.grid.back') }}
            </Link>
            <section
                class="overflow-x-auto rounded-lg border border-border bg-surface-raised shadow-xs"
                :aria-label="$t('results.grade_ranking')"
            >
                <table class="w-full text-sm">
                    <thead class="bg-surface-sunken text-left text-xs font-semibold text-ink-muted">
                        <tr>
                            <th scope="col" class="px-2 py-2 text-right">{{ $t('results.rank_grade') }}</th>
                            <th scope="col" class="px-3 py-2">{{ $t('people.name') }}</th>
                            <th scope="col" class="px-2 py-2">{{ $t('people.classroom') }}</th>
                            <th scope="col" class="px-2 py-2 text-right">{{ $t('results.average') }}</th>
                            <th scope="col" class="px-2 py-2">{{ $t('results.mention') }}</th>
                            <th scope="col" class="px-2 py-2">{{ $t('results.result') }}</th>
                            <th scope="col" class="px-2 py-2 text-right">{{ $t('results.rank_class') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="row in rows" :key="row.enrollment_id">
                            <td class="px-2 py-1.5 text-right font-mono font-semibold tabular-nums">
                                {{ row.rank_grade ?? '—' }}
                            </td>
                            <th scope="row" class="px-3 py-1.5 text-left font-medium">{{ row.name_km }}</th>
                            <td class="px-2 py-1.5">{{ row.classroom }}</td>
                            <td class="px-2 py-1.5 text-right font-mono tabular-nums">{{ row.average.toFixed(2) }}</td>
                            <td class="px-2 py-1.5"><MentionChip :mention="row.mention" /></td>
                            <td class="px-2 py-1.5" :class="row.passed ? 'text-success' : 'text-danger'">
                                {{ row.passed ? $t('results.passed') : $t('results.failed') }}
                            </td>
                            <td class="px-2 py-1.5 text-right font-mono tabular-nums">{{ row.rank_class ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </AdminLayout>
</template>
