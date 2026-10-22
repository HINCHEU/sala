<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PeopleTabs from '@/Components/PeopleTabs.vue';
import SButton from '@/Components/SButton.vue';

const props = defineProps({ import: { type: Object, required: true } });

const imp = computed(() => props.import);
const good = computed(() => imp.value.row_count - imp.value.error_count);
const confirming = ref(false);

const confirm = () =>
    router.post(
        `/people/import/${imp.value.id}/confirm`,
        {},
        {
            onStart: () => (confirming.value = true),
            onFinish: () => (confirming.value = false),
        },
    );

// The job runs in the background: refresh the counts until it finishes.
let timer = null;
onMounted(() => {
    timer = setInterval(() => {
        if (imp.value.status === 'queued') router.reload({ only: ['import'] });
    }, 2000);
});
onBeforeUnmount(() => clearInterval(timer));

const name = (row) => [row.data.last_name_km, row.data.first_name_km].filter(Boolean).join(' ') || '—';
const card = 'rounded-md border border-border bg-surface-raised p-4 shadow-sm sm:p-6';
</script>

<template>
    <AdminLayout :title="$t('people.title')">
        <PeopleTabs />

        <div class="space-y-6">
            <section :class="card" class="space-y-3" aria-labelledby="status-title" aria-live="polite">
                <h2 id="status-title" class="text-lg leading-[30px] font-semibold">{{ imp.original_name }}</h2>
                <p class="text-sm tabular-nums">
                    {{ $t('people.import.counts', { rows: imp.row_count, good, errors: imp.error_count }) }}
                    <span v-if="imp.year">· {{ $t('people.import.into_year', { year: imp.year }) }}</span>
                </p>

                <template v-if="imp.status === 'previewed'">
                    <p v-if="imp.error_count" class="text-sm leading-6">{{ $t('people.import.fix_or_skip') }}</p>
                    <div class="flex flex-wrap gap-2">
                        <SButton variant="primary" :loading="confirming" :disabled="good === 0" @click="confirm">
                            {{ $t('people.import.confirm', { count: good }) }}
                        </SButton>
                        <Link href="/people/import" class="s-btn s-btn-ghost">{{
                            $t('people.import.upload_again')
                        }}</Link>
                    </div>
                </template>
                <p v-else-if="imp.status === 'queued'" class="flex items-center gap-2 text-sm">
                    <span class="s-spin" aria-hidden="true" />
                    {{ $t('people.import.running') }}
                </p>
                <p v-else-if="imp.status === 'done'" class="flex items-center gap-2 font-semibold text-success">
                    <CircleCheck :size="20" :stroke-width="1.75" aria-hidden="true" />
                    {{ $t('people.import.done', { count: imp.imported_count }) }}
                    <Link href="/people/students" class="font-normal text-primary hover:underline">{{
                        $t('people.import.see_students')
                    }}</Link>
                </p>
                <p v-else role="alert" class="flex items-center gap-2 font-semibold text-danger">
                    <CircleAlert :size="20" :stroke-width="1.75" aria-hidden="true" />
                    {{ $t('people.import.failed', { count: imp.imported_count }) }}
                </p>
            </section>

            <section v-if="imp.errors.length" :class="card" aria-labelledby="errors-title">
                <h2 id="errors-title" class="mb-3 text-lg leading-[30px] font-semibold text-danger">
                    {{ $t('people.import.rows_with_errors', { count: imp.errors.length }) }}
                </h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-ink-muted">
                            <tr>
                                <th scope="col" class="py-1 pr-3 font-medium">{{ $t('people.import.line') }}</th>
                                <th scope="col" class="py-1 pr-3 font-medium">{{ $t('people.name') }}</th>
                                <th scope="col" class="py-1 font-medium">{{ $t('people.import.problem') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="row in imp.errors" :key="row.line" class="align-top">
                                <td class="py-2 pr-3 tabular-nums">{{ row.line }}</td>
                                <td class="py-2 pr-3">{{ name(row) }}</td>
                                <td class="py-2">
                                    <ul class="list-disc space-y-0.5 pl-4 text-danger">
                                        <li v-for="(error, i) in row.errors" :key="i">{{ error }}</li>
                                    </ul>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section
                v-if="imp.status === 'previewed' && imp.sample.length"
                :class="card"
                aria-labelledby="sample-title"
            >
                <h2 id="sample-title" class="mb-1 text-lg leading-[30px] font-semibold">
                    {{ $t('people.import.sample') }}
                </h2>
                <p class="mb-3 text-sm text-ink-muted">{{ $t('people.import.sample_hint') }}</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm whitespace-nowrap">
                        <thead class="text-left text-ink-muted">
                            <tr>
                                <th scope="col" class="py-1 pr-3 font-medium">{{ $t('people.import.line') }}</th>
                                <th scope="col" class="py-1 pr-3 font-medium">{{ $t('people.name') }}</th>
                                <th scope="col" class="py-1 pr-3 font-medium">{{ $t('people.gender') }}</th>
                                <th scope="col" class="py-1 pr-3 font-medium">{{ $t('people.dob') }}</th>
                                <th scope="col" class="py-1 pr-3 font-medium">{{ $t('people.classroom') }}</th>
                                <th scope="col" class="py-1 font-medium">{{ $t('people.guardian') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="row in imp.sample" :key="row.line">
                                <td class="py-2 pr-3 tabular-nums">{{ row.line }}</td>
                                <td class="py-2 pr-3">{{ name(row) }}</td>
                                <td class="py-2 pr-3">{{ $t(`people.genders.${row.data.gender}`) }}</td>
                                <td class="py-2 pr-3 tabular-nums">{{ row.data.dob ?? '—' }}</td>
                                <td class="py-2 pr-3">{{ row.data.classroom ?? '—' }}</td>
                                <td class="py-2">
                                    {{ row.data.guardian_name ?? '' }}
                                    <span class="tabular-nums text-ink-muted">{{ row.data.guardian_phone ?? '' }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
