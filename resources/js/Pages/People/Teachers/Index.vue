<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Search, UserPlus, UsersRound } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PeopleTabs from '@/Components/PeopleTabs.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    teachers: { type: Object, required: true },
    filters: { type: Object, required: true },
    statuses: { type: Array, required: true },
});

const q = ref(props.filters.q);
const status = ref(props.filters.status);
const apply = () =>
    router.get(
        '/people/teachers',
        { q: q.value || undefined, status: status.value },
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
                    <input
                        id="q"
                        v-model="q"
                        type="search"
                        class="s-input"
                        :placeholder="$t('people.teacher_search_hint')"
                    />
                    <button type="submit" class="s-btn px-3" :aria-label="$t('people.search')">
                        <Search :size="20" :stroke-width="1.75" aria-hidden="true" />
                    </button>
                </form>
                <div>
                    <label for="status" class="mb-1 block text-[13px] leading-[22px] font-medium">{{
                        $t('people.status')
                    }}</label>
                    <select id="status" v-model="status" class="s-input" @change="apply">
                        <option v-for="s in statuses" :key="s" :value="s">
                            {{ $t(`people.teacher_statuses.${s}`) }}
                        </option>
                    </select>
                </div>
                <Link href="/people/teachers/create" class="s-btn s-btn-primary">
                    <UserPlus :size="20" :stroke-width="1.75" aria-hidden="true" />
                    {{ $t('people.add_teacher') }}
                </Link>
            </div>

            <EmptyState
                v-if="teachers.data.length === 0"
                :icon="UsersRound"
                :title="filters.q ? $t('people.no_match') : $t('people.no_teachers')"
                :text="filters.q ? '' : $t('people.no_teachers_text')"
            />

            <section v-else class="rounded-lg border border-border bg-surface-raised shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <caption class="sr-only">
                            {{
                                $t('people.tabs.teachers')
                            }}
                        </caption>
                        <thead
                            class="border-b border-border bg-surface-sunken text-left text-xs font-semibold uppercase tracking-wider text-ink-muted"
                        >
                            <tr>
                                <th scope="col" class="px-3.5 py-2.5">{{ $t('people.staff_code') }}</th>
                                <th scope="col" class="px-3.5 py-2.5">{{ $t('people.name') }}</th>
                                <th scope="col" class="px-3.5 py-2.5">{{ $t('people.phone') }}</th>
                                <th scope="col" class="px-3.5 py-2.5">{{ $t('people.subjects') }}</th>
                                <th scope="col" class="px-3.5 py-2.5">{{ $t('people.employment_type') }}</th>
                                <th scope="col" class="px-3.5 py-2.5">{{ $t('people.login') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="t in teachers.data" :key="t.id" class="transition-colors hover:bg-slate-50/70">
                                <td class="px-3.5 py-2.5 font-mono text-xs font-medium tabular-nums text-primary">
                                    {{ t.staff_code ?? '—' }}
                                </td>
                                <td class="px-3.5 py-2.5">
                                    <Link
                                        :href="`/people/teachers/${t.id}/edit`"
                                        class="font-semibold text-ink hover:text-primary hover:underline"
                                    >
                                        {{ t.name_km }}
                                    </Link>
                                    <span v-if="t.name_en" lang="en" class="block text-xs text-ink-muted">{{
                                        t.name_en
                                    }}</span>
                                </td>
                                <td class="px-3.5 py-2.5 font-mono text-xs tabular-nums text-ink-muted">
                                    {{ t.phone ?? '—' }}
                                </td>
                                <td class="px-3.5 py-2.5 text-xs text-ink-muted">{{ t.subjects.join(', ') || '—' }}</td>
                                <td class="px-3.5 py-2.5">{{ $t(`people.employment_types.${t.employment_type}`) }}</td>
                                <td class="px-3.5 py-2.5">
                                    {{ t.has_login ? $t('academic.yes') : $t('academic.no') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <Pagination :paginator="teachers" />
        </div>
    </AdminLayout>
</template>
