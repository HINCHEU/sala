<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ShieldCheck } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PeopleTabs from '@/Components/PeopleTabs.vue';
import SButton from '@/Components/SButton.vue';
import SField from '@/Components/SField.vue';

const props = defineProps({
    student: { type: Object, default: null },
    genders: { type: Array, required: true },
    statuses: { type: Array, required: true },
    idPoorLevels: { type: Array, required: true },
    provinces: { type: Array, required: true },
});

const page = usePage();
const s = props.student ?? {};
const form = useForm({
    student_code: s.student_code ?? '',
    last_name_km: s.last_name_km ?? '',
    first_name_km: s.first_name_km ?? '',
    last_name_en: s.last_name_en ?? '',
    first_name_en: s.first_name_en ?? '',
    gender: s.gender ?? '',
    dob: s.dob ?? '',
    status: s.status ?? 'active',
    admitted_at: s.admitted_at ?? new Date().toLocaleDateString('en-CA'), // local YYYY-MM-DD, not UTC
    birth_province: s.birth_province ?? '',
    birth_district: s.birth_district ?? '',
    birth_commune: s.birth_commune ?? '',
    birth_village: s.birth_village ?? '',
    address: s.address ?? '',
    birth_cert_no: s.birth_cert_no ?? '',
    id_poor_level: s.id_poor_level ?? '',
    disability: s.disability ?? '',
    photo: null,
});

const photoPreview = computed(() => (form.photo ? URL.createObjectURL(form.photo) : s.photo_url));
const provinceName = (p) => (page.props.locale === 'en' ? p.en : p.km);

// A file upload cannot travel in a real PUT, so edits POST with _method=put.
function submit() {
    const options = { forceFormData: true, preserveScroll: true };
    props.student
        ? form.transform((data) => ({ ...data, _method: 'put' })).post(`/people/students/${s.id}`, options)
        : form.post('/people/students', options);
}

const card = 'space-y-4 rounded-md border border-border bg-surface-raised p-4 shadow-sm sm:p-6';
const text = (key) => ({ id: key, label: `people.${key}` });
const names = [text('last_name_km'), text('first_name_km'), text('last_name_en'), text('first_name_en')];
const birthPlace = [text('birth_district'), text('birth_commune'), text('birth_village')];
</script>

<template>
    <AdminLayout :title="student ? $t('people.edit_student') : $t('people.add_student')">
        <PeopleTabs />

        <form class="max-w-3xl space-y-6" novalidate @submit.prevent="submit">
            <section :class="card" aria-labelledby="identity-title">
                <h2 id="identity-title" class="text-lg leading-[30px] font-semibold">{{ $t('people.identity') }}</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <SField
                        v-for="field in names"
                        :id="field.id"
                        :key="field.id"
                        v-slot="f"
                        :label="$t(field.label)"
                        :error="form.errors[field.id]"
                    >
                        <input
                            :id="field.id"
                            v-model="form[field.id]"
                            class="s-input"
                            :lang="field.id.endsWith('_en') ? 'en' : undefined"
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
                            <option v-for="g in genders" :key="g" :value="g">{{ $t(`people.genders.${g}`) }}</option>
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
                        id="student_code"
                        v-slot="f"
                        :label="$t('people.student_code')"
                        :hint="$t('people.student_code_hint')"
                        :error="form.errors.student_code"
                    >
                        <input
                            id="student_code"
                            v-model="form.student_code"
                            class="s-input font-mono"
                            :aria-invalid="f.invalid"
                            :aria-describedby="f.describedby"
                        />
                    </SField>
                    <SField id="status" v-slot="f" :label="$t('people.status')" :error="form.errors.status">
                        <select id="status" v-model="form.status" class="s-input" :aria-describedby="f.describedby">
                            <option v-for="st in statuses" :key="st" :value="st">
                                {{ $t(`people.statuses.${st}`) }}
                            </option>
                        </select>
                    </SField>
                    <SField
                        id="admitted_at"
                        v-slot="f"
                        :label="$t('people.admitted_at')"
                        :error="form.errors.admitted_at"
                    >
                        <input
                            id="admitted_at"
                            v-model="form.admitted_at"
                            type="date"
                            class="s-input"
                            :aria-invalid="f.invalid"
                            :aria-describedby="f.describedby"
                        />
                    </SField>
                </div>

                <SField
                    id="photo"
                    v-slot="f"
                    :label="$t('people.photo')"
                    :hint="$t('people.photo_hint')"
                    :error="form.errors.photo"
                >
                    <div class="flex items-center gap-4">
                        <img
                            v-if="photoPreview"
                            :src="photoPreview"
                            alt=""
                            class="size-20 rounded-md border border-border object-cover"
                        />
                        <input
                            id="photo"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            class="text-sm"
                            :aria-invalid="f.invalid"
                            :aria-describedby="f.describedby"
                            @input="form.photo = $event.target.files[0]"
                        />
                    </div>
                </SField>
            </section>

            <section :class="card" aria-labelledby="birth-title">
                <h2 id="birth-title" class="text-lg leading-[30px] font-semibold">{{ $t('people.birth_place') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <SField
                        id="birth_province"
                        v-slot="f"
                        :label="$t('people.birth_province')"
                        :error="form.errors.birth_province"
                    >
                        <select
                            id="birth_province"
                            v-model="form.birth_province"
                            class="s-input"
                            :aria-describedby="f.describedby"
                        >
                            <option value="">—</option>
                            <option v-for="p in provinces" :key="p.km" :value="p.km">{{ provinceName(p) }}</option>
                        </select>
                    </SField>
                    <SField
                        v-for="field in birthPlace"
                        :id="field.id"
                        :key="field.id"
                        v-slot="f"
                        :label="$t(field.label)"
                        :error="form.errors[field.id]"
                    >
                        <input
                            :id="field.id"
                            v-model="form[field.id]"
                            class="s-input"
                            :aria-invalid="f.invalid"
                            :aria-describedby="f.describedby"
                        />
                    </SField>
                </div>
                <SField id="address" v-slot="f" :label="$t('people.address')" :error="form.errors.address">
                    <textarea
                        id="address"
                        v-model="form.address"
                        rows="2"
                        class="s-input"
                        :aria-invalid="f.invalid"
                        :aria-describedby="f.describedby"
                    />
                </SField>
            </section>

            <section :class="card" aria-labelledby="sensitive-title">
                <div>
                    <h2 id="sensitive-title" class="flex items-center gap-2 text-lg leading-[30px] font-semibold">
                        <ShieldCheck :size="20" :stroke-width="1.75" aria-hidden="true" />
                        {{ $t('people.sensitive') }}
                    </h2>
                    <p class="text-sm leading-6 text-ink-muted">{{ $t('people.sensitive_hint') }}</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <SField
                        id="birth_cert_no"
                        v-slot="f"
                        :label="$t('people.birth_cert_no')"
                        :error="form.errors.birth_cert_no"
                    >
                        <input
                            id="birth_cert_no"
                            v-model="form.birth_cert_no"
                            class="s-input"
                            :aria-invalid="f.invalid"
                            :aria-describedby="f.describedby"
                        />
                    </SField>
                    <SField
                        id="id_poor_level"
                        v-slot="f"
                        :label="$t('people.id_poor_level')"
                        :error="form.errors.id_poor_level"
                    >
                        <select
                            id="id_poor_level"
                            v-model="form.id_poor_level"
                            class="s-input"
                            :aria-describedby="f.describedby"
                        >
                            <option value="">{{ $t('people.id_poor_levels.none') }}</option>
                            <option v-for="l in idPoorLevels" :key="l" :value="l">
                                {{ $t(`people.id_poor_levels.${l}`) }}
                            </option>
                        </select>
                    </SField>
                </div>
                <SField id="disability" v-slot="f" :label="$t('people.disability')" :error="form.errors.disability">
                    <textarea
                        id="disability"
                        v-model="form.disability"
                        rows="2"
                        class="s-input"
                        :aria-invalid="f.invalid"
                        :aria-describedby="f.describedby"
                    />
                </SField>
            </section>

            <div class="flex gap-2">
                <SButton type="submit" variant="primary" :loading="form.processing">{{ $t('app.save') }}</SButton>
                <Link :href="student ? `/people/students/${student.id}` : '/people/students'" class="s-btn s-btn-ghost">
                    {{ $t('app.cancel') }}
                </Link>
            </div>
        </form>
    </AdminLayout>
</template>
