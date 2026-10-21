<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SButton from '@/Components/SButton.vue';
import SField from '@/Components/SField.vue';

defineProps({
    types: { type: Array, required: true },
    stages: { type: Array, required: true },
});

const form = useForm({
    code: '',
    name_km: '',
    name_en: '',
    type: 'public',
    stage: 'k12',
    province: '',
    email: '',
    phone: '',
});

const text = [
    ['name_km', 'platform.name_km', 'organization'],
    ['name_en', 'platform.name_en', 'organization'],
    ['province', 'platform.province', 'address-level1'],
];
</script>

<template>
    <AdminLayout :title="$t('platform.new_school')">
        <form
            class="max-w-xl space-y-4 rounded-md border border-border bg-surface-raised p-4 shadow-sm sm:p-6"
            novalidate
            @submit.prevent="form.post('/platform/schools')"
        >
            <SField
                id="code"
                v-slot="f"
                :label="$t('platform.code')"
                :hint="$t('platform.code_hint')"
                :error="form.errors.code"
            >
                <input
                    id="code"
                    v-model="form.code"
                    type="text"
                    lang="en"
                    autocapitalize="none"
                    class="s-input font-mono"
                    :aria-invalid="f.invalid"
                    :aria-describedby="f.describedby"
                />
            </SField>

            <SField
                v-for="[key, label, ac] in text"
                :id="key"
                :key="key"
                v-slot="f"
                :label="$t(label)"
                :error="form.errors[key]"
            >
                <input
                    :id="key"
                    v-model="form[key]"
                    type="text"
                    :autocomplete="ac"
                    class="s-input"
                    :aria-invalid="f.invalid"
                    :aria-describedby="f.describedby"
                />
            </SField>

            <div class="grid gap-4 sm:grid-cols-2">
                <SField id="type" v-slot="f" :label="$t('platform.type')" :error="form.errors.type">
                    <select id="type" v-model="form.type" class="s-input" :aria-describedby="f.describedby">
                        <option v-for="t in types" :key="t" :value="t">{{ $t(`platform.types.${t}`) }}</option>
                    </select>
                </SField>
                <SField id="stage" v-slot="f" :label="$t('platform.stage')" :error="form.errors.stage">
                    <select id="stage" v-model="form.stage" class="s-input" :aria-describedby="f.describedby">
                        <option v-for="s in stages" :key="s" :value="s">{{ $t(`platform.stages.${s}`) }}</option>
                    </select>
                </SField>
            </div>

            <fieldset class="space-y-4 border-t border-border pt-4">
                <legend class="pt-4 font-semibold">{{ $t('platform.director') }}</legend>
                <p class="text-sm leading-6 text-ink-muted">{{ $t('platform.director_hint') }}</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <SField id="email" v-slot="f" :label="$t('app.email')" :error="form.errors.email">
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="s-input"
                            :aria-invalid="f.invalid"
                            :aria-describedby="f.describedby"
                        />
                    </SField>
                    <SField id="phone" v-slot="f" :label="$t('app.phone')" :error="form.errors.phone">
                        <input
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            class="s-input"
                            :aria-invalid="f.invalid"
                            :aria-describedby="f.describedby"
                        />
                    </SField>
                </div>
            </fieldset>

            <SButton type="submit" variant="primary" class="w-full sm:w-auto" :loading="form.processing">
                {{ $t('platform.create') }}
            </SButton>
        </form>
    </AdminLayout>
</template>
