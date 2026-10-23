<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Search, UsersRound } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PeopleTabs from '@/Components/PeopleTabs.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    guardians: { type: Object, required: true },
    q: { type: String, default: '' },
});

const search = ref(props.q);
const apply = () =>
    router.get('/people/guardians', { q: search.value || undefined }, { preserveState: true, replace: true });
</script>

<template>
    <AdminLayout :title="$t('people.title')">
        <PeopleTabs />

        <div class="space-y-4">
            <form class="flex max-w-lg gap-2" role="search" @submit.prevent="apply">
                <label for="q" class="sr-only">{{ $t('people.search') }}</label>
                <input
                    id="q"
                    v-model="search"
                    type="search"
                    class="s-input"
                    :placeholder="$t('people.guardian_search_hint')"
                />
                <button type="submit" class="s-btn px-3" :aria-label="$t('people.search')">
                    <Search :size="20" :stroke-width="1.75" aria-hidden="true" />
                </button>
            </form>
            <p class="text-sm leading-6 text-ink-muted">{{ $t('people.guardians_hint') }}</p>

            <EmptyState
                v-if="guardians.data.length === 0"
                :icon="UsersRound"
                :title="q ? $t('people.no_match') : $t('people.no_guardians')"
            />

            <section v-else class="rounded-lg border border-border bg-surface-raised shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <caption class="sr-only">
                            {{
                                $t('people.tabs.guardians')
                            }}
                        </caption>
                        <thead
                            class="border-b border-border bg-surface-sunken text-left text-xs font-semibold uppercase tracking-wider text-ink-muted"
                        >
                            <tr>
                                <th scope="col" class="px-3.5 py-2.5">{{ $t('people.name') }}</th>
                                <th scope="col" class="px-3.5 py-2.5">{{ $t('people.phone') }}</th>
                                <th scope="col" class="px-3.5 py-2.5">{{ $t('people.occupation') }}</th>
                                <th scope="col" class="px-3.5 py-2.5">{{ $t('people.children') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="g in guardians.data" :key="g.id" class="transition-colors hover:bg-slate-50/70">
                                <td class="px-3.5 py-2.5">
                                    <span class="font-semibold text-ink">{{ g.name_km }}</span>
                                    <span v-if="g.name_en" lang="en" class="block text-xs text-ink-muted">{{
                                        g.name_en
                                    }}</span>
                                </td>
                                <td class="px-3.5 py-2.5 font-mono text-xs tabular-nums">
                                    <a v-if="g.phone" :href="`tel:${g.phone}`" class="text-primary hover:underline">{{
                                        g.phone
                                    }}</a>
                                    <span v-else class="text-ink-muted">—</span>
                                </td>
                                <td class="px-3.5 py-2.5">{{ g.occupation ?? '—' }}</td>
                                <td class="px-3.5 py-2.5">
                                    <template v-for="(child, i) in g.children" :key="child.id">
                                        <span v-if="i">, </span>
                                        <Link
                                            :href="`/people/students/${child.id}`"
                                            class="font-medium text-ink hover:text-primary hover:underline"
                                            >{{ child.name }}</Link
                                        >
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <Pagination :paginator="guardians" />
        </div>
    </AdminLayout>
</template>
