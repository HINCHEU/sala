<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import SButton from '@/Components/SButton.vue';
import SField from '@/Components/SField.vue';

const props = defineProps({
    state: { type: String, required: true }, // register | login | accept | mismatch | invalid
    token: { type: String, required: true },
    invitation: { type: Object, default: null },
});

const form = useForm({ name: '', password: '', password_confirmation: '' });
const submit = () => form.post(`/invitations/${props.token}`);
</script>

<template>
    <GuestLayout :title="invitation ? $t('members.accept_title', { school: invitation.school }) : $t('app.name')">
        <p v-if="invitation" class="mb-4 text-sm leading-6 text-ink-muted">
            {{ $t('members.accept_role', { role: $t(`roles.${invitation.role}`) }) }}
            <span v-if="invitation.email || invitation.phone"> · {{ invitation.email ?? invitation.phone }}</span>
        </p>

        <form v-if="state === 'register'" class="space-y-4" novalidate @submit.prevent="submit">
            <p class="text-sm leading-6">{{ $t('members.register_text') }}</p>
            <SField id="name" v-slot="f" :label="$t('app.person_name')" :error="form.errors.name">
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    autocomplete="name"
                    required
                    autofocus
                    class="s-input"
                    :aria-invalid="f.invalid"
                    :aria-describedby="f.describedby"
                />
            </SField>
            <SField id="password" v-slot="f" :label="$t('app.password')" :error="form.errors.password">
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    required
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
                {{ $t('members.create_account') }}
            </SButton>
        </form>

        <form v-else-if="state === 'accept'" class="space-y-4" @submit.prevent="submit">
            <p class="text-sm leading-6">{{ $t('members.accept_text') }}</p>
            <SButton type="submit" variant="primary" class="w-full" :loading="form.processing">
                {{ $t('members.accept') }}
            </SButton>
        </form>

        <div v-else-if="state === 'login'" class="space-y-4">
            <p class="text-sm leading-6">{{ $t('members.login_text') }}</p>
            <Link href="/login" class="s-btn s-btn-primary w-full">{{ $t('app.sign_in') }}</Link>
        </div>

        <div v-else-if="state === 'mismatch'" class="space-y-4">
            <p class="text-sm leading-6 text-danger">{{ $t('members.mismatch_text') }}</p>
            <Link href="/logout" method="post" as="button" class="s-btn w-full">{{ $t('app.sign_out') }}</Link>
        </div>

        <p v-else class="text-sm leading-6 text-danger">{{ $t('members.invalid_text') }}</p>
    </GuestLayout>
</template>
