<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ email: '', password: '', remember: false });

const submit = () => form.post('/login', { onFinish: () => form.reset('password') });

const input =
    'block w-full min-h-11 rounded-sm border border-border-strong bg-surface-raised px-3 py-2 text-ink ' +
    'focus:outline-2 focus:outline-offset-2 focus:outline-primary';
</script>

<template>
    <Head :title="$t('app.sign_in')" />
    <main class="flex min-h-screen items-center justify-center px-4 py-8">
        <div class="w-full max-w-sm">
            <h1 class="text-[22px] leading-9 font-semibold">{{ $t('app.name') }}</h1>
            <p class="mb-6 text-sm leading-6 text-ink-muted">{{ $t('app.tagline') }}</p>

            <form
                class="space-y-4 rounded-md border border-border bg-surface-raised p-4 shadow-sm sm:p-6"
                novalidate
                @submit.prevent="submit"
            >
                <div>
                    <label for="email" class="mb-1 block text-[13px] leading-[22px] font-medium">
                        {{ $t('app.email') }}
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        autocomplete="username"
                        required
                        autofocus
                        :class="input"
                        :aria-invalid="!!form.errors.email"
                        aria-describedby="email-error"
                    />
                    <p v-if="form.errors.email" id="email-error" class="mt-1 text-sm leading-6 text-danger">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label for="password" class="mb-1 block text-[13px] leading-[22px] font-medium">
                        {{ $t('app.password') }}
                    </label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        required
                        :class="input"
                        :aria-invalid="!!form.errors.password"
                        aria-describedby="password-error"
                    />
                    <p v-if="form.errors.password" id="password-error" class="mt-1 text-sm leading-6 text-danger">
                        {{ form.errors.password }}
                    </p>
                </div>

                <label class="flex min-h-11 items-center gap-2 text-sm">
                    <input v-model="form.remember" type="checkbox" class="size-4 accent-primary" />
                    {{ $t('app.remember') }}
                </label>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full min-h-11 rounded-md bg-primary px-4 py-2 font-medium text-on-primary hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:opacity-60"
                >
                    {{ form.processing ? $t('app.signing_in') : $t('app.sign_in') }}
                </button>
            </form>
        </div>
    </main>
</template>
