<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import SButton from '@/Components/SButton.vue';
import SField from '@/Components/SField.vue';

const form = useForm({ email: '' });
</script>

<template>
    <GuestLayout :title="$t('app.forgot_password')">
        <p class="mb-4 text-sm leading-6 text-ink-muted">{{ $t('app.forgot_text') }}</p>
        <form class="space-y-4" novalidate @submit.prevent="form.post('/forgot-password')">
            <SField id="email" v-slot="f" :label="$t('app.email')" :error="form.errors.email">
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    required
                    autofocus
                    class="s-input"
                    :aria-invalid="f.invalid"
                    :aria-describedby="f.describedby"
                />
            </SField>
            <SButton type="submit" variant="primary" class="w-full" :loading="form.processing">
                {{ $t('app.send_reset_link') }}
            </SButton>
            <Link href="/login" class="block text-center text-sm text-primary">{{ $t('app.back_to_login') }}</Link>
        </form>
    </GuestLayout>
</template>
