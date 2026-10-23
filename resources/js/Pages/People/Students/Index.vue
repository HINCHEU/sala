<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { FileSpreadsheet, Search, UserPlus, UsersRound } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PeopleTabs from '@/Components/PeopleTabs.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    students: { type: Object, required: true },
    filters: { type: Object, required: true },
    classrooms: { type: Array, required: true },
    statuses: { type: Array, required: true },
});

const q = ref(props.filters.q);
const classroom = ref(props.filters.classroom ?? '');
const status = ref(props.filters.status);

const apply = () =>
    router.get(
        '/people/students',
        { q: q.value || undefined, classroom: classroom.value || undefined, status: status.value },
        { preserveState: true, replace: true },
    );
</script>

<template>
    <AdminLayout :title="$t('people.title')">
        <PeopleTabs />

        <div class="space-y-4">
            <div class="flex flex-wrap items-end gap-3">
                <form class="flex min-w-60 flex-1 gap-2" role="search" @submit.prevent="apply">
                    <label for="q" class="sr-only">{{ $t('people.search') }}</label>
                    <input id="q" v-model="q" type="search" class="s-input" :placeholder="$t('people.search_hint')" />
                    <button type="submit" class="s-btn px-3" :aria-label="$t('people.search')">
                        <Search :size="20" :stroke-width="1.75" aria-hidden="true" />
                    </button>
                </form>
                <div>
                    <label for="classroom" class="mb-1 block text-[13px] leading-[22px] font-medium">
                        {{ $t('people.classroom') }}
                    </label>
                    <select id="classroom" v-model="classroom" class="s-input" @change="apply">
                        <option value="">{{ $t('people.all_classes') }}</option>
                        <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div>
                    <label for="status" class="mb-1 block text-[13px] leading-[22px] font-medium">
                        {{ $t('people.status') }}
                    </label>
                    <select id="status" v-model="status" class="s-input" @change="apply">
                        <option v-for="s in statuses" :key="s" :value="s">{{ $t(`people.statuses.${s}`) }}</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <Link href="/people/import" class="s-btn">
                        <FileSpreadsheet :size="20" :stroke-width="1.75" aria-hidden="true" />
                        {{ $t('people.tabs.import') }}
                    </Link>
                    <Link href="/people/students/create" class="s-btn s-btn-primary">
                        <UserPlus :size="20" :stroke-width="1.75" aria-hidden="true" />
                        {{ $t('people.add_student') }}
                    </Link>
                </div>
            </div>

            <EmptyState
                v-if="students.data.length === 0"
                :icon="UsersRound"
                :title="filters.q ? $t('people.no_match') : $t('people.no_students')"
                :text="filters.q ? '' : $t('people.no_students_text')"
            />

            <section v-else class="rounded-lg border border-border bg-surface-raised shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <caption class="sr-only">
                            {{
                                $t('people.tabs.students')
                            }}
                        </caption>
                        <thead
                            class="border-b border-border bg-surface-sunken text-left text-xs font-semibold uppercase tracking-wider text-ink-muted"
                        >
                            <tr>
                                <th scope="col" class="px-3.5 py-2.5">{{ $t('people.student_code') }}</th>
                                <th scope="col" class="px-3.5 py-2.5">{{ $t('people.name') }}</th>
                                <th scope="col" class="px-3.5 py-2.5">{{ $t('people.gender') }}</th>
                                <th scope="col" class="px-3.5 py-2.5">{{ $t('people.classroom') }}</th>
                                <th scope="col" class="px-3.5 py-2.5">{{ $t('people.guardian_phone') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="s in students.data" :key="s.id" class="transition-colors hover:bg-slate-50/70">
                                <td class="px-3.5 py-2.5 font-mono text-xs font-medium tabular-nums text-primary">
                                    {{ s.student_code }}
                                </td>
                                <td class="px-3.5 py-2.5">
                                    <Link
                                        :href="`/people/students/${s.id}`"
                                        class="font-semibold text-ink hover:text-primary hover:underline"
                                    >
                                        {{ s.name_km }}
                                    </Link>
                                    <span v-if="s.name_en" lang="en" class="block text-xs text-ink-muted">{{
                                        s.name_en
                                    }}</span>
                                </td>
                                <td class="px-3.5 py-2.5">{{ $t(`people.genders.${s.gender}`) }}</td>
                                <td class="px-3.5 py-2.5">{{ s.classroom ?? '—' }}</td>
                                <td class="px-3.5 py-2.5 font-mono text-xs tabular-nums text-ink-muted">
                                    {{ s.guardian_phone ?? '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <p v-if="students.total" class="text-sm text-ink-muted">
                {{ $t('people.count', { count: students.total }) }}
            </p>
            <Pagination :paginator="students" />
        </div>
    </AdminLayout>
</template>
