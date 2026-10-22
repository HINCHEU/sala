<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { ArrowRightLeft, CalendarRange, Search, UserMinus, UserPlus } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PeopleTabs from '@/Components/PeopleTabs.vue';
import EmptyState from '@/Components/EmptyState.vue';
import SButton from '@/Components/SButton.vue';
import { confirmDanger } from '@/lib/alert';

const props = defineProps({
    years: { type: Array, required: true },
    yearId: { type: Number, default: null },
    classrooms: { type: Array, required: true },
    classroomId: { type: Number, default: null },
    enrolled: { type: Array, required: true },
    candidates: { type: Array, required: true },
    q: { type: String, default: '' },
    candidateLimit: { type: Number, required: true },
});

const classroom = computed(() => props.classrooms.find((c) => c.id === props.classroomId));
const selectedEnrolled = ref([]);
const selectedCandidates = ref([]);
const moveTo = ref('');
const search = ref(props.q);
const busy = ref(false);

// A new list arrives after every change: forget selections that no longer exist.
watch(
    () => [props.enrolled, props.candidates],
    () => {
        selectedEnrolled.value = [];
        selectedCandidates.value = [];
        moveTo.value = '';
    },
);

const females = (list) => list.filter((s) => s.gender === 'female').length;
const visit = (params) =>
    router.get(
        '/people/enrollment',
        { year: props.yearId, classroom: props.classroomId, q: search.value || undefined, ...params },
        { preserveState: false },
    );

function send(method, data) {
    const options = { preserveScroll: true, onStart: () => (busy.value = true), onFinish: () => (busy.value = false) };
    // router.delete takes its data inside the options; post and put take it as an argument.
    method === 'delete'
        ? router.delete('/people/enrollments', { ...options, data })
        : router[method]('/people/enrollments', data, options);
}

const add = () => send('post', { classroom_id: props.classroomId, student_ids: selectedCandidates.value });
const move = () => send('put', { classroom_id: moveTo.value, enrollment_ids: selectedEnrolled.value });

async function remove() {
    const ok = await confirmDanger({
        title: trans('people.unenroll_confirm', { count: selectedEnrolled.value.length, name: classroom.value.name }),
        text: trans('people.unenroll_text'),
        confirmText: trans('people.unenroll'),
    });
    if (ok) send('delete', { enrollment_ids: selectedEnrolled.value });
}

const toggleEnrolled = () => {
    selectedEnrolled.value =
        selectedEnrolled.value.length === props.enrolled.length ? [] : props.enrolled.map((e) => e.id);
};
const toggleCandidates = () => {
    selectedCandidates.value =
        selectedCandidates.value.length === props.candidates.length ? [] : props.candidates.map((c) => c.id);
};
const panel = 'rounded-md border border-border bg-surface-raised shadow-sm';
</script>

<template>
    <AdminLayout :title="$t('people.title')">
        <PeopleTabs />

        <EmptyState v-if="!yearId" :icon="CalendarRange" :title="$t('academic.no_year')" />
        <EmptyState
            v-else-if="classrooms.length === 0"
            :icon="CalendarRange"
            :title="$t('people.no_classes')"
            :text="$t('people.no_classes_text')"
        />

        <div v-else class="space-y-4">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="year" class="mb-1 block text-[13px] leading-[22px] font-medium">{{
                        $t('academic.year')
                    }}</label>
                    <select
                        id="year"
                        class="s-input"
                        :value="yearId"
                        @change="visit({ year: $event.target.value, classroom: undefined })"
                    >
                        <option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}</option>
                    </select>
                </div>
                <div>
                    <label for="classroom" class="mb-1 block text-[13px] leading-[22px] font-medium">{{
                        $t('people.classroom')
                    }}</label>
                    <select
                        id="classroom"
                        class="s-input"
                        :value="classroomId"
                        @change="visit({ classroom: $event.target.value })"
                    >
                        <option v-for="c in classrooms" :key="c.id" :value="c.id">
                            {{ c.name }} ({{ c.count }}{{ c.capacity ? ` / ${c.capacity}` : '' }})
                        </option>
                    </select>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <section :class="panel" aria-labelledby="enrolled-title">
                    <header class="flex flex-wrap items-center justify-between gap-2 border-b border-border px-4 py-3">
                        <h2 id="enrolled-title" class="text-lg leading-[30px] font-semibold">
                            {{ $t('people.in_class', { name: classroom.name }) }}
                        </h2>
                        <span class="text-sm text-ink-muted tabular-nums">
                            {{ $t('people.total_female', { total: enrolled.length, female: females(enrolled) }) }}
                        </span>
                    </header>

                    <p v-if="enrolled.length === 0" class="px-4 py-6 text-center text-sm text-ink-muted">
                        {{ $t('people.class_empty') }}
                    </p>
                    <template v-else>
                        <div class="flex flex-wrap items-center gap-2 border-b border-border px-4 py-2">
                            <label for="move-to" class="sr-only">{{ $t('people.move_to') }}</label>
                            <select
                                id="move-to"
                                v-model="moveTo"
                                class="s-input w-auto"
                                :disabled="selectedEnrolled.length === 0"
                            >
                                <option value="" disabled>{{ $t('people.move_to') }}</option>
                                <option
                                    v-for="c in classrooms.filter((c) => c.id !== classroomId)"
                                    :key="c.id"
                                    :value="c.id"
                                >
                                    {{ c.name }}
                                </option>
                            </select>
                            <SButton :disabled="!moveTo || selectedEnrolled.length === 0" :loading="busy" @click="move">
                                <ArrowRightLeft :size="18" :stroke-width="1.75" aria-hidden="true" />
                                {{ $t('people.move') }}
                            </SButton>
                            <SButton
                                variant="ghost"
                                class="text-danger"
                                :disabled="selectedEnrolled.length === 0 || busy"
                                @click="remove"
                            >
                                <UserMinus :size="18" :stroke-width="1.75" aria-hidden="true" />
                                {{ $t('people.unenroll') }}
                            </SButton>
                        </div>
                        <div class="max-h-[60vh] overflow-y-auto">
                            <table class="w-full text-sm">
                                <thead class="sticky top-0 bg-surface-sunken text-left">
                                    <tr>
                                        <th scope="col" class="w-10 px-3 py-2">
                                            <input
                                                type="checkbox"
                                                class="size-5 accent-primary"
                                                :checked="selectedEnrolled.length === enrolled.length"
                                                :aria-label="$t('people.select_all')"
                                                @change="toggleEnrolled"
                                            />
                                        </th>
                                        <th scope="col" class="px-2 py-2 font-medium">{{ $t('people.roll_no') }}</th>
                                        <th scope="col" class="px-2 py-2 font-medium">{{ $t('people.name') }}</th>
                                        <th scope="col" class="px-2 py-2 font-medium">{{ $t('people.gender') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    <tr v-for="e in enrolled" :key="e.id">
                                        <td class="px-3 py-1">
                                            <input
                                                v-model="selectedEnrolled"
                                                type="checkbox"
                                                :value="e.id"
                                                class="size-5 accent-primary"
                                                :aria-label="e.name_km"
                                            />
                                        </td>
                                        <td class="px-2 py-1 tabular-nums">{{ e.roll_no }}</td>
                                        <td class="px-2 py-1">
                                            {{ e.name_km }}
                                            <span class="font-mono text-[12px] text-ink-muted">{{
                                                e.student_code
                                            }}</span>
                                        </td>
                                        <td class="px-2 py-1">{{ $t(`people.genders.${e.gender}`) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </section>

                <section :class="panel" aria-labelledby="candidates-title">
                    <header class="space-y-2 border-b border-border px-4 py-3">
                        <h2 id="candidates-title" class="text-lg leading-[30px] font-semibold">
                            {{ $t('people.without_class') }}
                        </h2>
                        <form class="flex gap-2" role="search" @submit.prevent="visit({})">
                            <label for="q" class="sr-only">{{ $t('people.search') }}</label>
                            <input
                                id="q"
                                v-model="search"
                                type="search"
                                class="s-input"
                                :placeholder="$t('people.search_hint')"
                            />
                            <button type="submit" class="s-btn px-3" :aria-label="$t('people.search')">
                                <Search :size="20" :stroke-width="1.75" aria-hidden="true" />
                            </button>
                        </form>
                    </header>

                    <p v-if="candidates.length === 0" class="px-4 py-6 text-center text-sm text-ink-muted">
                        {{ q ? $t('people.no_match') : $t('people.all_enrolled') }}
                    </p>
                    <template v-else>
                        <div class="flex flex-wrap items-center gap-2 border-b border-border px-4 py-2">
                            <SButton
                                variant="primary"
                                :disabled="selectedCandidates.length === 0"
                                :loading="busy"
                                @click="add"
                            >
                                <UserPlus :size="18" :stroke-width="1.75" aria-hidden="true" />
                                {{
                                    $t('people.add_to_class', {
                                        count: selectedCandidates.length,
                                        name: classroom.name,
                                    })
                                }}
                            </SButton>
                            <span v-if="candidates.length >= candidateLimit" class="text-sm text-ink-muted">
                                {{ $t('people.first_n', { count: candidateLimit }) }}
                            </span>
                        </div>
                        <div class="max-h-[60vh] overflow-y-auto">
                            <table class="w-full text-sm">
                                <thead class="sticky top-0 bg-surface-sunken text-left">
                                    <tr>
                                        <th scope="col" class="w-10 px-3 py-2">
                                            <input
                                                type="checkbox"
                                                class="size-5 accent-primary"
                                                :checked="selectedCandidates.length === candidates.length"
                                                :aria-label="$t('people.select_all')"
                                                @change="toggleCandidates"
                                            />
                                        </th>
                                        <th scope="col" class="px-2 py-2 font-medium">{{ $t('people.name') }}</th>
                                        <th scope="col" class="px-2 py-2 font-medium">{{ $t('people.gender') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    <tr v-for="c in candidates" :key="c.id">
                                        <td class="px-3 py-1">
                                            <input
                                                v-model="selectedCandidates"
                                                type="checkbox"
                                                :value="c.id"
                                                class="size-5 accent-primary"
                                                :aria-label="c.name_km"
                                            />
                                        </td>
                                        <td class="px-2 py-1">
                                            {{ c.name_km }}
                                            <span class="font-mono text-[12px] text-ink-muted">{{
                                                c.student_code
                                            }}</span>
                                        </td>
                                        <td class="px-2 py-1">{{ $t(`people.genders.${c.gender}`) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </section>
            </div>
            <p class="text-sm leading-6 text-ink-muted">{{ $t('people.roll_hint') }}</p>
        </div>
    </AdminLayout>
</template>
