<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import SButton from '@/Components/SButton.vue';
import SField from '@/Components/SField.vue';

const recovery = ref(false);
const form = useForm({ code: '', recovery_code: '' });

function toggle() {
    recovery.value = !recovery.value;
    form.reset();
    form.clearErrors();
}
</script>

<template>
    <GuestLayout :title="$t('app.two_factor_title')">
        <form class="space-y-4" novalidate @submit.prevent="form.post('/two-factor-challenge')">
            <p v-if="!recovery" class="text-sm leading-6 text-ink-muted">{{ $t('app.two_factor_text') }}</p>

            <SField v-if="!recovery" id="code" v-slot="f" :label="$t('app.two_factor_code')" :error="form.errors.code">
                <input
                    id="code"
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    required
                    autofocus
                    class="s-input tabular-nums"
                    :aria-invalid="f.invalid"
                    :aria-describedby="f.describedby"
                />
            </SField>
            <SField
                v-else
                id="recovery_code"
                v-slot="f"
                :label="$t('app.recovery_code')"
                :error="form.errors.recovery_code"
            >
                <input
                    id="recovery_code"
                    v-model="form.recovery_code"
                    type="text"
                    autocomplete="off"
                    required
                    autofocus
                    class="s-input"
                    :aria-invalid="f.invalid"
                    :aria-describedby="f.describedby"
                />
            </SField>

            <SButton type="submit" variant="primary" class="w-full" :loading="form.processing">
                {{ $t('app.verify') }}
            </SButton>
            <SButton variant="ghost" class="w-full" @click="toggle">
                {{ recovery ? $t('app.use_auth_code') : $t('app.use_recovery_code') }}
            </SButton>
        </form>
    </GuestLayout>
</template>
