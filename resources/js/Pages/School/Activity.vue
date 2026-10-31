<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { History } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    events: { type: Object, required: true },
    filters: { type: Object, required: true },
    groups: { type: Array, required: true },
    users: { type: Array, required: true },
});

const form = reactive({ ...props.filters });
const apply = () =>
    router.get(
        '/activity',
        {
            group: form.group || undefined,
            user: form.user || undefined,
            from: form.from || undefined,
            to: form.to || undefined,
        },
        { preserveState: true, replace: true },
    );
</script>

<template>
    <AdminLayout :title="$t('activity.title')">
        <div class="space-y-4">
            <form class="flex flex-wrap items-end gap-3" @submit.prevent="apply">
                <label class="text-[13px] font-medium">
                    <span class="mb-1 block">{{ $t('activity.group') }}</span>
                    <select v-model="form.group" class="s-input" @change="apply">
                        <option value="">{{ $t('activity.all') }}</option>
                        <option v-for="g in groups" :key="g" :value="g">{{ $t(`activity.groups.${g}`) }}</option>
                    </select>
                </label>
                <label class="text-[13px] font-medium">
                    <span class="mb-1 block">{{ $t('activity.user') }}</span>
                    <select v-model="form.user" class="s-input" @change="apply">
                        <option value="">{{ $t('activity.all') }}</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </label>
                <label class="text-[13px] font-medium">
                    <span class="mb-1 block">{{ $t('activity.from') }}</span>
                    <input v-model="form.from" type="date" class="s-input" @change="apply" />
                </label>
                <label class="text-[13px] font-medium">
                    <span class="mb-1 block">{{ $t('activity.to') }}</span>
                    <input v-model="form.to" type="date" class="s-input" @change="apply" />
                </label>
            </form>

            <EmptyState
                v-if="!events.data.length"
                :icon="History"
                :title="$t('activity.empty_title')"
                :text="$t('activity.empty_text')"
            />
            <section
                v-else
                class="overflow-x-auto rounded-lg border border-border bg-surface-raised shadow-xs"
                :aria-label="$t('activity.title')"
            >
                <table class="w-full text-sm">
                    <thead class="bg-surface-sunken text-left text-xs font-semibold text-ink-muted">
                        <tr>
                            <th scope="col" class="px-3 py-2 whitespace-nowrap">{{ $t('activity.when') }}</th>
                            <th scope="col" class="px-3 py-2">{{ $t('activity.user') }}</th>
                            <th scope="col" class="px-3 py-2">{{ $t('activity.event') }}</th>
                            <th scope="col" class="px-3 py-2">{{ $t('activity.details') }}</th>
                            <th scope="col" class="px-3 py-2 whitespace-nowrap">{{ $t('activity.change') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="e in events.data" :key="e.id">
                            <td class="px-3 py-1.5 font-mono whitespace-nowrap tabular-nums">{{ e.at }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap">{{ e.who ?? $t('activity.system') }}</td>
                            <td class="px-3 py-1.5">{{ e.event }}</td>
                            <td class="px-3 py-1.5">{{ [e.exam, e.person, e.subject].filter(Boolean).join(' · ') }}</td>
                            <td class="px-3 py-1.5 font-mono whitespace-nowrap tabular-nums">
                                <template v-if="e.old !== null || e.new !== null">
                                    {{ e.old ?? '—' }} → {{ e.new ?? '—' }}
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
            <Pagination :paginator="events" />
        </div>
    </AdminLayout>
</template>
