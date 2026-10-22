<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SButton from '@/Components/SButton.vue';
import SField from '@/Components/SField.vue';

// Adds a guardian to a student, or edits one already linked. When adding, a phone that already belongs to a
// guardian (a sibling's parent) is looked up first, and saving links that guardian instead of making a copy.
const props = defineProps({
    studentId: { type: Number, required: true },
    guardian: { type: Object, default: null },
    relationships: { type: Array, required: true },
    genders: { type: Array, required: true },
});
const emit = defineEmits(['done']);

const g = props.guardian ?? {};
const prefix = props.guardian ? `g${props.guardian.id}` : 'new';
const form = useForm({
    phone: g.phone ?? '',
    name_km: g.name_km ?? '',
    name_en: g.name_en ?? '',
    gender: g.gender ?? '',
    occupation: g.occupation ?? '',
    address: g.address ?? '',
    relationship: g.relationship ?? 'father',
    is_primary_contact: g.is_primary_contact ?? false,
    can_pickup: g.can_pickup ?? true,
});

const existing = ref(null);

async function lookup() {
    if (props.guardian || !form.phone.trim()) {
        existing.value = null;
        return;
    }
    const response = await fetch(`/people/guardians/lookup?phone=${encodeURIComponent(form.phone)}`, {
        headers: { Accept: 'application/json' },
    });
    existing.value = response.ok ? (await response.json()).guardian : null;
}

function submit() {
    const options = { preserveScroll: true, onSuccess: () => emit('done') };
    props.guardian
        ? form.put(`/people/students/${props.studentId}/guardians/${props.guardian.id}`, options)
        : form.post(`/people/students/${props.studentId}/guardians`, options);
}

const id = (key) => `${prefix}-${key}`;
</script>

<template>
    <form class="space-y-4" novalidate @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
            <SField :id="id('phone')" v-slot="f" :label="$t('people.phone')" :error="form.errors.phone">
                <input
                    :id="id('phone')"
                    v-model="form.phone"
                    type="tel"
                    inputmode="tel"
                    class="s-input tabular-nums"
                    :placeholder="$t('people.phone_hint')"
                    :aria-invalid="f.invalid"
                    :aria-describedby="f.describedby"
                    @blur="lookup"
                />
            </SField>
            <SField
                :id="id('relationship')"
                v-slot="f"
                :label="$t('people.relationship')"
                :error="form.errors.relationship"
            >
                <select
                    :id="id('relationship')"
                    v-model="form.relationship"
                    class="s-input"
                    :aria-describedby="f.describedby"
                >
                    <option v-for="r in relationships" :key="r" :value="r">
                        {{ $t(`people.relationships.${r}`) }}
                    </option>
                </select>
            </SField>
        </div>

        <div
            v-if="existing"
            role="status"
            class="rounded-md border border-primary bg-primary-soft p-3 text-sm leading-6"
        >
            <p class="font-semibold">{{ $t('people.guardian_exists', { name: existing.name_km }) }}</p>
            <p v-if="existing.children.length">
                {{ $t('people.guardian_of', { names: existing.children.join(', ') }) }}
            </p>
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2">
            <SField :id="id('name_km')" v-slot="f" :label="$t('people.guardian_name_km')" :error="form.errors.name_km">
                <input
                    :id="id('name_km')"
                    v-model="form.name_km"
                    class="s-input"
                    :aria-invalid="f.invalid"
                    :aria-describedby="f.describedby"
                />
            </SField>
            <SField :id="id('name_en')" v-slot="f" :label="$t('people.guardian_name_en')" :error="form.errors.name_en">
                <input
                    :id="id('name_en')"
                    v-model="form.name_en"
                    lang="en"
                    class="s-input"
                    :aria-invalid="f.invalid"
                    :aria-describedby="f.describedby"
                />
            </SField>
            <SField :id="id('gender')" v-slot="f" :label="$t('people.gender')" :error="form.errors.gender">
                <select :id="id('gender')" v-model="form.gender" class="s-input" :aria-describedby="f.describedby">
                    <option value="">—</option>
                    <option v-for="gd in genders" :key="gd" :value="gd">{{ $t(`people.genders.${gd}`) }}</option>
                </select>
            </SField>
            <SField :id="id('occupation')" v-slot="f" :label="$t('people.occupation')" :error="form.errors.occupation">
                <input
                    :id="id('occupation')"
                    v-model="form.occupation"
                    class="s-input"
                    :aria-invalid="f.invalid"
                    :aria-describedby="f.describedby"
                />
            </SField>
            <SField
                :id="id('address')"
                v-slot="f"
                class="sm:col-span-2"
                :label="$t('people.address')"
                :error="form.errors.address"
            >
                <input
                    :id="id('address')"
                    v-model="form.address"
                    class="s-input"
                    :aria-invalid="f.invalid"
                    :aria-describedby="f.describedby"
                />
            </SField>
        </div>

        <div class="flex flex-wrap gap-x-6 gap-y-2">
            <label class="flex min-h-11 items-center gap-2">
                <input v-model="form.is_primary_contact" type="checkbox" class="size-5 accent-primary" />
                {{ $t('people.primary_contact') }}
            </label>
            <label class="flex min-h-11 items-center gap-2">
                <input v-model="form.can_pickup" type="checkbox" class="size-5 accent-primary" />
                {{ $t('people.can_pickup') }}
            </label>
        </div>

        <div class="flex gap-2">
            <SButton type="submit" variant="primary" :loading="form.processing">
                {{ existing ? $t('people.link_guardian') : $t('app.save') }}
            </SButton>
            <SButton variant="ghost" @click="emit('done')">{{ $t('app.cancel') }}</SButton>
        </div>
    </form>
</template>
