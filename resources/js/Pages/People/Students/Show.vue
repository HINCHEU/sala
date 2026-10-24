<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { FileText, Pencil, Phone, Plus, Trash2, Upload, UserMinus } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PeopleTabs from '@/Components/PeopleTabs.vue';
import GuardianForm from '@/Components/GuardianForm.vue';
import MentionChip from '@/Components/MentionChip.vue';
import SButton from '@/Components/SButton.vue';
import SField from '@/Components/SField.vue';
import StatusPill from '@/Components/StatusPill.vue';
import { confirmDanger } from '@/lib/alert';

const props = defineProps({
    student: { type: Object, required: true },
    guardians: { type: Array, required: true },
    enrollments: { type: Array, required: true },
    documents: { type: Array, required: true },
    relationships: { type: Array, required: true },
    genders: { type: Array, required: true },
    documentTypes: { type: Array, required: true },
    results: { type: Array, default: () => [] },
});

const s = computed(() => props.student);
const editingGuardian = ref(null); // guardian id, 'new' or null

const birthPlace = computed(() =>
    [s.value.birth_village, s.value.birth_commune, s.value.birth_district, s.value.birth_province]
        .filter(Boolean)
        .join(', '),
);
const details = computed(() => [
    ['people.gender', trans(`people.genders.${s.value.gender}`)],
    ['people.dob', s.value.dob],
    ['people.birth_place', birthPlace.value],
    ['people.address', s.value.address],
    ['people.admitted_at', s.value.admitted_at],
]);
const sensitive = computed(() => [
    ['people.birth_cert_no', s.value.birth_cert_no],
    ['people.id_poor_level', trans(`people.id_poor_levels.${s.value.id_poor_level ?? 'none'}`)],
    ['people.disability', s.value.disability],
]);

async function unlink(guardian) {
    const ok = await confirmDanger({
        title: trans('people.unlink_confirm', { name: guardian.name_km }),
        text: trans('people.unlink_text'),
        confirmText: trans('people.unlink'),
    });
    if (ok) router.delete(`/people/students/${s.value.id}/guardians/${guardian.id}`, { preserveScroll: true });
}

const upload = useForm({ type: 'birth_certificate', file: null });
const fileInput = ref(null);
function sendDocument() {
    upload.post(`/people/students/${s.value.id}/documents`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            upload.reset('file');
            fileInput.value.value = '';
        },
    });
}

async function removeDocument(doc) {
    const ok = await confirmDanger({
        title: trans('people.delete_document', { name: doc.name ?? trans(`people.document_types.${doc.type}`) }),
        text: trans('academic.delete_text'),
        confirmText: trans('academic.delete'),
    });
    if (ok) router.delete(`/people/students/${s.value.id}/documents/${doc.id}`, { preserveScroll: true });
}

const kb = (bytes) => `${Math.max(1, Math.round(bytes / 1024))} KB`;
const card = 'rounded-lg border border-border bg-surface-raised p-4 shadow-sm sm:p-6';
</script>

<template>
    <AdminLayout :title="s.name_km">
        <PeopleTabs />

        <div class="space-y-6">
            <section :class="card" class="flex flex-wrap items-start gap-4">
                <img
                    v-if="s.photo_url"
                    :src="s.photo_url"
                    :alt="$t('people.photo_of', { name: s.name_km })"
                    class="size-24 rounded border border-border object-cover"
                />
                <div
                    v-else
                    class="flex size-24 items-center justify-center rounded bg-surface-sunken font-headline text-3xl font-bold text-ink-muted"
                    aria-hidden="true"
                >
                    {{ s.first_name_km?.charAt(0) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p v-if="s.name_en" lang="en" class="text-sm font-normal text-ink-muted">{{ s.name_en }}</p>
                    <p class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                        <span class="font-mono text-xs font-semibold tabular-nums text-primary">{{
                            s.student_code
                        }}</span>
                        <StatusPill :status="s.status === 'active' ? 'approved' : 'warning'">
                            {{ $t(`people.statuses.${s.status}`) }}
                        </StatusPill>
                        <StatusPill v-if="enrollments[0]" status="submitted">
                            {{ enrollments[0].classroom }} · {{ enrollments[0].year }}
                        </StatusPill>
                    </p>
                </div>
                <Link :href="`/people/students/${s.id}/edit`" class="s-btn">
                    <Pencil :size="16" :stroke-width="1.75" aria-hidden="true" />
                    {{ $t('academic.edit') }}
                </Link>
            </section>

            <div class="grid gap-6 lg:grid-cols-2">
                <section :class="card" aria-labelledby="details-title">
                    <h2 id="details-title" class="mb-3 font-headline text-base leading-6 font-bold text-ink">
                        {{ $t('people.identity') }}
                    </h2>
                    <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
                        <template v-for="[label, value] in details" :key="label">
                            <dt class="text-ink-muted">{{ $t(label) }}</dt>
                            <dd>{{ value || '—' }}</dd>
                        </template>
                    </dl>
                    <h3 class="mt-5 mb-2 text-sm font-semibold">{{ $t('people.sensitive') }}</h3>
                    <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
                        <template v-for="[label, value] in sensitive" :key="label">
                            <dt class="text-ink-muted">{{ $t(label) }}</dt>
                            <dd>{{ value || '—' }}</dd>
                        </template>
                    </dl>
                </section>

                <section :class="card" aria-labelledby="history-title">
                    <h2 id="history-title" class="mb-3 text-lg leading-[30px] font-semibold">
                        {{ $t('people.history') }}
                    </h2>
                    <p v-if="enrollments.length === 0" class="text-sm text-ink-muted">{{ $t('people.no_history') }}</p>
                    <table v-else class="w-full text-sm">
                        <thead class="text-left text-ink-muted">
                            <tr>
                                <th scope="col" class="py-1 font-medium">{{ $t('academic.year') }}</th>
                                <th scope="col" class="py-1 font-medium">{{ $t('people.classroom') }}</th>
                                <th scope="col" class="py-1 font-medium">{{ $t('people.roll_no') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="e in enrollments" :key="e.id">
                                <td class="py-2">{{ e.year }}</td>
                                <td class="py-2">{{ e.classroom }}</td>
                                <td class="py-2 tabular-nums">{{ e.roll_no ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </div>

            <section :class="card" aria-labelledby="guardians-title">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h2 id="guardians-title" class="text-lg leading-[30px] font-semibold">
                        {{ $t('people.tabs.guardians') }}
                    </h2>
                    <SButton variant="ghost" :disabled="editingGuardian !== null" @click="editingGuardian = 'new'">
                        <Plus :size="20" :stroke-width="1.75" aria-hidden="true" />
                        {{ $t('people.add_guardian') }}
                    </SButton>
                </div>

                <GuardianForm
                    v-if="editingGuardian === 'new'"
                    class="mb-4 rounded-md border border-border p-4"
                    :student-id="s.id"
                    :relationships="relationships"
                    :genders="genders"
                    @done="editingGuardian = null"
                />

                <p v-if="guardians.length === 0 && editingGuardian !== 'new'" class="text-sm text-ink-muted">
                    {{ $t('people.no_guardians') }}
                </p>

                <ul class="divide-y divide-border">
                    <li v-for="g in guardians" :key="g.id" class="py-3">
                        <GuardianForm
                            v-if="editingGuardian === g.id"
                            :student-id="s.id"
                            :guardian="g"
                            :relationships="relationships"
                            :genders="genders"
                            @done="editingGuardian = null"
                        />
                        <div v-else class="flex flex-wrap items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold">
                                    {{ g.name_km }}
                                    <span v-if="g.name_en" lang="en" class="font-normal text-ink-muted"
                                        >· {{ g.name_en }}</span
                                    >
                                </p>
                                <p class="mt-1 flex flex-wrap items-center gap-2 text-sm">
                                    <span>{{ $t(`people.relationships.${g.relationship}`) }}</span>
                                    <StatusPill v-if="g.is_primary_contact" status="submitted">
                                        {{ $t('people.primary_contact') }}
                                    </StatusPill>
                                    <StatusPill v-if="!g.can_pickup" status="warning">
                                        {{ $t('people.no_pickup') }}
                                    </StatusPill>
                                </p>
                                <p v-if="g.phone" class="mt-1 text-sm">
                                    <a
                                        :href="`tel:${g.phone}`"
                                        class="inline-flex min-h-11 items-center gap-1 text-primary tabular-nums hover:underline"
                                    >
                                        <Phone :size="16" :stroke-width="1.75" aria-hidden="true" />
                                        {{ g.phone }}
                                    </a>
                                </p>
                                <p v-if="g.occupation" class="text-sm text-ink-muted">{{ g.occupation }}</p>
                            </div>
                            <div class="flex">
                                <button
                                    type="button"
                                    class="s-btn s-btn-ghost px-2"
                                    :aria-label="`${$t('academic.edit')} ${g.name_km}`"
                                    :disabled="editingGuardian !== null"
                                    @click="editingGuardian = g.id"
                                >
                                    <Pencil :size="18" :stroke-width="1.75" />
                                </button>
                                <button
                                    type="button"
                                    class="s-btn s-btn-ghost px-2 text-danger"
                                    :aria-label="`${$t('people.unlink')} ${g.name_km}`"
                                    :disabled="editingGuardian !== null"
                                    @click="unlink(g)"
                                >
                                    <UserMinus :size="18" :stroke-width="1.75" />
                                </button>
                            </div>
                        </div>
                    </li>
                </ul>
            </section>

            <section :class="card" aria-labelledby="results-title">
                <h2 id="results-title" class="mb-3 text-lg leading-[30px] font-semibold">
                    {{ $t('results.history') }}
                </h2>
                <p v-if="results.length === 0" class="text-sm text-ink-muted">{{ $t('results.no_history') }}</p>
                <table v-else class="w-full text-sm">
                    <thead class="text-left text-ink-muted">
                        <tr>
                            <th scope="col" class="py-1 font-medium">{{ $t('exams.title') }}</th>
                            <th scope="col" class="py-1 text-right font-medium">{{ $t('results.average') }}</th>
                            <th scope="col" class="py-1 text-right font-medium">{{ $t('results.rank') }}</th>
                            <th scope="col" class="py-1 font-medium">{{ $t('results.mention') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="r in results" :key="r.exam_id">
                            <td class="py-2">{{ r.exam }}</td>
                            <td class="py-2 text-right font-mono tabular-nums">{{ r.average.toFixed(2) }}</td>
                            <td class="py-2 text-right font-mono tabular-nums">{{ r.rank_class ?? '—' }}</td>
                            <td class="py-2"><MentionChip :mention="r.mention" /></td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section :class="card" aria-labelledby="documents-title">
                <h2 id="documents-title" class="mb-3 text-lg leading-[30px] font-semibold">
                    {{ $t('people.documents') }}
                </h2>

                <ul v-if="documents.length" class="mb-4 divide-y divide-border">
                    <li v-for="doc in documents" :key="doc.id" class="flex flex-wrap items-center gap-3 py-2 text-sm">
                        <FileText :size="20" :stroke-width="1.75" class="text-ink-muted" aria-hidden="true" />
                        <div class="min-w-0 flex-1">
                            <a
                                :href="doc.url"
                                target="_blank"
                                rel="noopener"
                                class="font-medium text-primary hover:underline"
                            >
                                {{ $t(`people.document_types.${doc.type}`) }}
                            </a>
                            <span class="block truncate text-ink-muted"
                                >{{ doc.name }} · {{ kb(doc.size) }} · {{ doc.created_at }}</span
                            >
                        </div>
                        <button
                            type="button"
                            class="s-btn s-btn-ghost px-2 text-danger"
                            :aria-label="`${$t('academic.delete')} ${doc.name}`"
                            @click="removeDocument(doc)"
                        >
                            <Trash2 :size="18" :stroke-width="1.75" />
                        </button>
                    </li>
                </ul>
                <p v-else class="mb-4 text-sm text-ink-muted">{{ $t('people.no_documents') }}</p>

                <form class="flex flex-wrap items-end gap-3" novalidate @submit.prevent="sendDocument">
                    <SField id="doc-type" v-slot="f" :label="$t('people.document_type')" :error="upload.errors.type">
                        <select id="doc-type" v-model="upload.type" class="s-input" :aria-describedby="f.describedby">
                            <option v-for="t in documentTypes" :key="t" :value="t">
                                {{ $t(`people.document_types.${t}`) }}
                            </option>
                        </select>
                    </SField>
                    <SField
                        id="doc-file"
                        v-slot="f"
                        :label="$t('people.file')"
                        :hint="$t('people.document_hint')"
                        :error="upload.errors.file"
                    >
                        <input
                            id="doc-file"
                            ref="fileInput"
                            type="file"
                            accept="application/pdf,image/png,image/jpeg,image/webp"
                            class="text-sm"
                            :aria-invalid="f.invalid"
                            :aria-describedby="f.describedby"
                            @input="upload.file = $event.target.files[0]"
                        />
                    </SField>
                    <SButton type="submit" :loading="upload.processing" :disabled="!upload.file">
                        <Upload :size="18" :stroke-width="1.75" aria-hidden="true" />
                        {{ $t('people.upload') }}
                    </SButton>
                </form>
            </section>
        </div>
    </AdminLayout>
</template>
