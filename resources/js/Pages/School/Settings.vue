<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SButton from '@/Components/SButton.vue';
import SField from '@/Components/SField.vue';

const props = defineProps({
    school: { type: Object, required: true },
    settings: { type: Object, required: true },
    roles: { type: Array, required: true },
});

const form = useForm({
    name_km: props.school.name_km,
    name_en: props.school.name_en,
    report_header: props.school.report_header ?? '',
    brand_color: props.school.brand_color ?? '',
    logo: null,
    settings: { ...props.settings },
});

const logoPreview = computed(() => (form.logo ? URL.createObjectURL(form.logo) : props.school.logo_url));

// POST with forceFormData so the logo file goes up in the same request.
const submit = () =>
    form.post('/settings', { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset('logo') });

const choices = {
    averageMethod: ['divisor', 'weighted', 'simple'],
    absentSubjectPolicy: ['zero', 'exclude_from_rank'],
    attendanceMode: ['daily', 'period', 'both'],
};
const err = (key) => form.errors[`settings.${key}`];
const card = 'space-y-4 rounded-md border border-border bg-surface-raised p-4 shadow-sm sm:p-6';
</script>

<template>
    <AdminLayout :title="$t('settings.title')">
        <form class="max-w-2xl space-y-6" novalidate @submit.prevent="submit">
            <section :class="card" aria-labelledby="profile-title">
                <h2 id="profile-title" class="text-lg leading-[30px] font-semibold">{{ $t('settings.profile') }}</h2>

                <SField id="name_km" v-slot="f" :label="$t('settings.name_km')" :error="form.errors.name_km">
                    <input
                        id="name_km"
                        v-model="form.name_km"
                        class="s-input"
                        :aria-invalid="f.invalid"
                        :aria-describedby="f.describedby"
                    />
                </SField>
                <SField id="name_en" v-slot="f" :label="$t('settings.name_en')" :error="form.errors.name_en">
                    <input
                        id="name_en"
                        v-model="form.name_en"
                        lang="en"
                        class="s-input"
                        :aria-invalid="f.invalid"
                        :aria-describedby="f.describedby"
                    />
                </SField>
                <SField
                    id="report_header"
                    v-slot="f"
                    :label="$t('settings.report_header')"
                    :hint="$t('settings.report_header_hint')"
                    :error="form.errors.report_header"
                >
                    <textarea
                        id="report_header"
                        v-model="form.report_header"
                        rows="3"
                        class="s-input"
                        :aria-invalid="f.invalid"
                        :aria-describedby="f.describedby"
                    />
                </SField>

                <SField
                    id="logo"
                    v-slot="f"
                    :label="$t('settings.logo')"
                    :hint="$t('settings.logo_hint')"
                    :error="form.errors.logo"
                >
                    <div class="flex items-center gap-4">
                        <img
                            v-if="logoPreview"
                            :src="logoPreview"
                            alt=""
                            class="size-16 rounded-md border border-border object-contain"
                        />
                        <input
                            id="logo"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            class="text-sm"
                            :aria-invalid="f.invalid"
                            :aria-describedby="f.describedby"
                            @input="form.logo = $event.target.files[0]"
                        />
                    </div>
                </SField>

                <SField
                    id="brand_color"
                    v-slot="f"
                    :label="$t('settings.brand_color')"
                    :hint="$t('settings.brand_color_hint')"
                    :error="form.errors.brand_color"
                >
                    <div class="flex flex-wrap items-center gap-3">
                        <input
                            id="brand_color"
                            v-model="form.brand_color"
                            type="color"
                            class="h-11 w-16 cursor-pointer rounded-sm border border-border-strong"
                            :aria-invalid="f.invalid"
                            :aria-describedby="f.describedby"
                        />
                        <code class="font-mono text-sm">{{ form.brand_color || '—' }}</code>
                        <SButton v-if="form.brand_color" variant="ghost" @click="form.brand_color = ''">
                            {{ $t('settings.brand_color_reset') }}
                        </SButton>
                    </div>
                </SField>
            </section>

            <section :class="card" aria-labelledby="rules-title">
                <h2 id="rules-title" class="text-lg leading-[30px] font-semibold">{{ $t('settings.rules') }}</h2>

                <SField id="locale" v-slot="f" :label="$t('settings.locale')" :error="err('locale')">
                    <select
                        id="locale"
                        v-model="form.settings.locale"
                        class="s-input"
                        :aria-describedby="f.describedby"
                    >
                        <option value="km">ខ្មែរ</option>
                        <option value="en">English</option>
                    </select>
                </SField>

                <SField
                    id="averageMethod"
                    v-slot="f"
                    :label="$t('settings.average_method')"
                    :error="err('averageMethod')"
                >
                    <select
                        id="averageMethod"
                        v-model="form.settings.averageMethod"
                        class="s-input"
                        :aria-describedby="f.describedby"
                    >
                        <option v-for="c in choices.averageMethod" :key="c" :value="c">
                            {{ $t(`settings.average_methods.${c}`) }}
                        </option>
                    </select>
                </SField>

                <fieldset>
                    <legend class="mb-2 text-sm leading-6 text-ink-muted">{{ $t('settings.semester_weights') }}</legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <SField
                            id="semesterMonthlyWeight"
                            v-slot="f"
                            :label="$t('settings.semester_monthly_weight')"
                            :error="err('semesterMonthlyWeight')"
                        >
                            <input
                                id="semesterMonthlyWeight"
                                v-model.number="form.settings.semesterMonthlyWeight"
                                type="number"
                                step="0.05"
                                min="0"
                                max="1"
                                class="s-input tabular-nums"
                                :aria-invalid="f.invalid"
                                :aria-describedby="f.describedby"
                            />
                        </SField>
                        <SField
                            id="semesterExamWeight"
                            v-slot="f"
                            :label="$t('settings.semester_exam_weight')"
                            :error="err('semesterExamWeight')"
                        >
                            <input
                                id="semesterExamWeight"
                                v-model.number="form.settings.semesterExamWeight"
                                type="number"
                                step="0.05"
                                min="0"
                                max="1"
                                class="s-input tabular-nums"
                                :aria-invalid="f.invalid"
                                :aria-describedby="f.describedby"
                            />
                        </SField>
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="mb-2 text-sm leading-6 text-ink-muted">{{ $t('settings.annual_weights') }}</legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <SField
                            id="annualS1Weight"
                            v-slot="f"
                            :label="$t('settings.annual_s1_weight')"
                            :error="err('annualS1Weight')"
                        >
                            <input
                                id="annualS1Weight"
                                v-model.number="form.settings.annualS1Weight"
                                type="number"
                                step="0.05"
                                min="0"
                                max="1"
                                class="s-input tabular-nums"
                                :aria-invalid="f.invalid"
                                :aria-describedby="f.describedby"
                            />
                        </SField>
                        <SField
                            id="annualS2Weight"
                            v-slot="f"
                            :label="$t('settings.annual_s2_weight')"
                            :error="err('annualS2Weight')"
                        >
                            <input
                                id="annualS2Weight"
                                v-model.number="form.settings.annualS2Weight"
                                type="number"
                                step="0.05"
                                min="0"
                                max="1"
                                class="s-input tabular-nums"
                                :aria-invalid="f.invalid"
                                :aria-describedby="f.describedby"
                            />
                        </SField>
                    </div>
                </fieldset>

                <SField
                    id="absentSubjectPolicy"
                    v-slot="f"
                    :label="$t('settings.absent_subject_policy')"
                    :error="err('absentSubjectPolicy')"
                >
                    <select
                        id="absentSubjectPolicy"
                        v-model="form.settings.absentSubjectPolicy"
                        class="s-input"
                        :aria-describedby="f.describedby"
                    >
                        <option v-for="c in choices.absentSubjectPolicy" :key="c" :value="c">
                            {{ $t(`settings.absent_policies.${c}`) }}
                        </option>
                    </select>
                </SField>

                <SField
                    id="scoreApproverRole"
                    v-slot="f"
                    :label="$t('settings.score_approver')"
                    :error="err('scoreApproverRole')"
                >
                    <select
                        id="scoreApproverRole"
                        v-model="form.settings.scoreApproverRole"
                        class="s-input"
                        :aria-describedby="f.describedby"
                    >
                        <option v-for="role in roles" :key="role" :value="role">{{ $t(`roles.${role}`) }}</option>
                    </select>
                </SField>

                <SField
                    id="attendanceMode"
                    v-slot="f"
                    :label="$t('settings.attendance_mode')"
                    :error="err('attendanceMode')"
                >
                    <select
                        id="attendanceMode"
                        v-model="form.settings.attendanceMode"
                        class="s-input"
                        :aria-describedby="f.describedby"
                    >
                        <option v-for="c in choices.attendanceMode" :key="c" :value="c">
                            {{ $t(`settings.attendance_modes.${c}`) }}
                        </option>
                    </select>
                </SField>

                <label class="flex min-h-11 items-center gap-3">
                    <input v-model="form.settings.rankAcrossGrade" type="checkbox" class="size-5 accent-primary" />
                    {{ $t('settings.rank_across_grade') }}
                </label>
                <label class="flex min-h-11 items-center gap-3">
                    <input v-model="form.settings.khmerNumerals" type="checkbox" class="size-5 accent-primary" />
                    {{ $t('settings.khmer_numerals') }}
                </label>

                <p class="rounded-sm bg-surface-sunken px-3 py-2 text-sm leading-6 text-ink-muted">
                    {{ $t('settings.promotion_note') }}
                </p>
            </section>

            <SButton type="submit" variant="primary" class="w-full sm:w-auto" :loading="form.processing">
                {{ form.processing ? $t('app.saving') : $t('app.save') }}
            </SButton>
        </form>
    </AdminLayout>
</template>
