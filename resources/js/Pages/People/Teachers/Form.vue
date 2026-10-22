<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { KeyRound } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PeopleTabs from '@/Components/PeopleTabs.vue';
import InviteLink from '@/Components/InviteLink.vue';
import SButton from '@/Components/SButton.vue';
import SField from '@/Components/SField.vue';

const props = defineProps({
    teacher: { type: Object, default: null },
    subjects: { type: Array, required: true },
    genders: { type: Array, required: true },
    employmentTypes: { type: Array, required: true },
    statuses: { type: Array, required: true },
});

const page = usePage();
const t = props.teacher ?? {};
const form = useForm({
    staff_code: t.staff_code ?? '',
    name_km: t.name_km ?? '',
    name_en: t.name_en ?? '',
    gender: t.gender ?? '',
    dob: t.dob ?? '',
    phone: t.phone ?? '',
    email: t.email ?? '',
    qualification: t.qualification ?? '',
    employment_type: t.employment_type ?? 'civil_servant',
    hired_at: t.hired_at ?? '',
    status: t.status ?? 'active',
    subject_ids: t.subject_ids ?? [],
});

const submit = () =>
    props.teacher ? form.put(`/people/teachers/${t.id}`, { preserveScroll: true }) : form.post('/people/teachers');

const invite = () => router.post(`/people/teachers/${t.id}/invite`, {}, { preserveScroll: true });
const subjectName = (s) => (page.props.locale === 'en' ? s.name_en : s.name_km);

const card = 'space-y-4 rounded-md border border-border bg-surface-raised p-4 shadow-sm sm:p-6';
const textFields = [
    { id: 'name_km', label: 'people.name_km' },
    { id: 'name_en', label: 'people.name_en', lang: 'en' },
    { id: 'staff_code', label: 'people.staff_code' },
    { id: 'qualification', label: 'people.qualification' },
    { id: 'phone', label: 'people.phone', type: 'tel' },
    { id: 'email', label: 'people.email', type: 'email', lang: 'en' },
];
</script>

<template>
    <AdminLayout :title="teacher ? teacher.name_km : $t('people.add_teacher')">
        <PeopleTabs />

        <div class="max-w-3xl space-y-6">
            <form class="space-y-6" novalidate @submit.prevent="submit">
                <section :class="card" aria-labelledby="profile-title">
                    <h2 id="profile-title" class="text-lg leading-[30px] font-semibold">{{ $t('people.profile') }}</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <SField
                            v-for="field in textFields"
                            :id="field.id"
                            :key="field.id"
                            v-slot="f"
                            :label="$t(field.label)"
                            :error="form.errors[field.id]"
                        >
                            <input
                                :id="field.id"
                                v-model="form[field.id]"
                                :type="field.type ?? 'text'"
                                :lang="field.lang"
                                class="s-input"
                                :aria-invalid="f.invalid"
                                :aria-describedby="f.describedby"
                            />
                        </SField>
                        <SField id="gender" v-slot="f" :label="$t('people.gender')" :error="form.errors.gender">
                            <select
                                id="gender"
                                v-model="form.gender"
                                class="s-input"
                                :aria-invalid="f.invalid"
                                :aria-describedby="f.describedby"
                            >
                                <option value="" disabled>—</option>
                                <option v-for="g in genders" :key="g" :value="g">
                                    {{ $t(`people.genders.${g}`) }}
                                </option>
                            </select>
                        </SField>
                        <SField id="dob" v-slot="f" :label="$t('people.dob')" :error="form.errors.dob">
                            <input
                                id="dob"
                                v-model="form.dob"
                                type="date"
                                class="s-input"
                                :aria-invalid="f.invalid"
                                :aria-describedby="f.describedby"
                            />
                        </SField>
                        <SField
                            id="employment_type"
                            v-slot="f"
                            :label="$t('people.employment_type')"
                            :error="form.errors.employment_type"
                        >
                            <select
                                id="employment_type"
                                v-model="form.employment_type"
                                class="s-input"
                                :aria-describedby="f.describedby"
                            >
                                <option v-for="e in employmentTypes" :key="e" :value="e">
                                    {{ $t(`people.employment_types.${e}`) }}
                                </option>
                            </select>
                        </SField>
                        <SField id="hired_at" v-slot="f" :label="$t('people.hired_at')" :error="form.errors.hired_at">
                            <input
                                id="hired_at"
                                v-model="form.hired_at"
                                type="date"
                                class="s-input"
                                :aria-invalid="f.invalid"
                                :aria-describedby="f.describedby"
                            />
                        </SField>
                        <SField id="status" v-slot="f" :label="$t('people.status')" :error="form.errors.status">
                            <select id="status" v-model="form.status" class="s-input" :aria-describedby="f.describedby">
                                <option v-for="s in statuses" :key="s" :value="s">
                                    {{ $t(`people.teacher_statuses.${s}`) }}
                                </option>
                            </select>
                        </SField>
                    </div>
                </section>

                <fieldset :class="card">
                    <legend class="sr-only">{{ $t('people.subjects') }}</legend>
                    <div>
                        <h2 class="text-lg leading-[30px] font-semibold" aria-hidden="true">
                            {{ $t('people.subjects') }}
                        </h2>
                        <p class="text-sm leading-6 text-ink-muted">{{ $t('people.subjects_hint') }}</p>
                    </div>
                    <p v-if="subjects.length === 0" class="text-sm text-ink-muted">{{ $t('people.no_subjects') }}</p>
                    <div class="grid gap-x-4 sm:grid-cols-2 lg:grid-cols-3">
                        <label v-for="s in subjects" :key="s.id" class="flex min-h-11 items-center gap-2">
                            <input
                                v-model="form.subject_ids"
                                type="checkbox"
                                :value="s.id"
                                class="size-5 accent-primary"
                            />
                            {{ subjectName(s) }}
                        </label>
                    </div>
                    <p v-if="form.errors.subject_ids" class="text-sm text-danger">{{ form.errors.subject_ids }}</p>
                </fieldset>

                <div class="flex gap-2">
                    <SButton type="submit" variant="primary" :loading="form.processing">{{ $t('app.save') }}</SButton>
                    <Link href="/people/teachers" class="s-btn s-btn-ghost">{{ $t('app.cancel') }}</Link>
                </div>
            </form>

            <section v-if="teacher" :class="card" aria-labelledby="login-title">
                <h2 id="login-title" class="flex items-center gap-2 text-lg leading-[30px] font-semibold">
                    <KeyRound :size="20" :stroke-width="1.75" aria-hidden="true" />
                    {{ $t('people.login') }}
                </h2>
                <p v-if="teacher.login_name" class="text-sm leading-6">
                    {{ $t('people.login_linked', { name: teacher.login_name }) }}
                </p>
                <template v-else>
                    <p class="text-sm leading-6 text-ink-muted">{{ $t('people.login_hint') }}</p>
                    <SButton @click="invite">{{ $t('people.create_login_link') }}</SButton>
                    <InviteLink v-if="page.props.flash.inviteLink" :link="page.props.flash.inviteLink" />
                </template>
            </section>
        </div>
    </AdminLayout>
</template>
