<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import SButton from '@/Components/SButton.vue';
import SField from '@/Components/SField.vue';

const form = useForm({ login: '', password: '', remember: false });

const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <GuestLayout :title="$t('app.sign_in')">
        <form class="space-y-4" novalidate @submit.prevent="submit">
            <SField
                id="login"
                v-slot="f"
                :label="$t('app.login')"
                :hint="$t('app.login_hint')"
                :error="form.errors.login"
            >
                <input
                    id="login"
                    v-model="form.login"
                    type="text"
                    inputmode="email"
                    autocomplete="username"
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
                    autocomplete="current-password"
                    required
                    class="s-input"
                    :aria-invalid="f.invalid"
                    :aria-describedby="f.describedby"
                />
            </SField>

            <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                <label
                    class="flex min-h-11 cursor-pointer items-center gap-2 text-sm text-ink-muted transition-colors hover:text-ink"
                >
                    <input v-model="form.remember" type="checkbox" class="size-4 rounded accent-primary" />
                    {{ $t('app.remember') }}
                </label>
                <Link
                    href="/forgot-password"
                    class="text-sm font-medium text-primary underline-offset-4 hover:underline"
                >
                    {{ $t('app.forgot_password') }}
                </Link>
            </div>

            <SButton type="submit" variant="primary" class="w-full shadow-md" :loading="form.processing">
                {{ form.processing ? $t('app.signing_in') : $t('app.sign_in') }}
            </SButton>
        </form>
    </GuestLayout>
</template>
