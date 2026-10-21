<script setup>
import { useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import SButton from '@/Components/SButton.vue';
import SField from '@/Components/SField.vue';

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, default: '' },
});

const form = useForm({ token: props.token, email: props.email, password: '', password_confirmation: '' });

const submit = () => form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <GuestLayout :title="$t('app.reset_password')">
        <form class="space-y-4" novalidate @submit.prevent="submit">
            <SField id="email" v-slot="f" :label="$t('app.email')" :error="form.errors.email">
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="username"
                    required
                    class="s-input"
                    :aria-invalid="f.invalid"
                    :aria-describedby="f.describedby"
                />
            </SField>
            <SField id="password" v-slot="f" :label="$t('security.new_password')" :error="form.errors.password">
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    required
                    autofocus
                    class="s-input"
                    :aria-invalid="f.invalid"
                    :aria-describedby="f.describedby"
                />
            </SField>
            <SField id="password_confirmation" v-slot="f" :label="$t('app.confirm_password')">
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    required
                    class="s-input"
                    :aria-describedby="f.describedby"
                />
            </SField>
            <SButton type="submit" variant="primary" class="w-full" :loading="form.processing">
                {{ $t('app.reset_password') }}
            </SButton>
        </form>
    </GuestLayout>
</template>
