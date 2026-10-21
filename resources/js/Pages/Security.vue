<script setup>
import { computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { useRoleLayout } from '@/lib/roleLayout';
import SButton from '@/Components/SButton.vue';
import SField from '@/Components/SField.vue';
import { notifySuccess } from '@/lib/alert';

const props = defineProps({ twoFactor: { type: Object, required: true } });

const page = usePage();
const layout = useRoleLayout();

const enable = () => router.post('/user/two-factor-authentication', {}, { preserveScroll: true });
const disable = () => router.delete('/user/two-factor-authentication', { preserveScroll: true });
const regenerate = () => router.post('/user/two-factor-recovery-codes', {}, { preserveScroll: true });

const confirmForm = useForm({ code: '' });
const confirm = () =>
    confirmForm.post('/user/confirmed-two-factor-authentication', {
        errorBag: 'confirmTwoFactorAuthentication',
        preserveScroll: true,
        onSuccess: () => notifySuccess(trans('security.two_factor_confirmed')),
    });

const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });
const updatePassword = () =>
    passwordForm.put('/user/password', {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            notifySuccess(trans('security.password_updated'));
        },
    });

const confirmError = computed(() => page.props.errors.confirmTwoFactorAuthentication?.code);
const passwordErrors = computed(() => page.props.errors.updatePassword ?? {});
const card = 'rounded-md border border-border bg-surface-raised p-4 shadow-sm sm:p-6';
</script>

<template>
    <component :is="layout" :title="$t('security.title')">
        <div class="max-w-xl space-y-6">
            <section :class="card" aria-labelledby="two-factor-title">
                <h2 id="two-factor-title" class="mb-2 text-lg leading-[30px] font-semibold">
                    {{ $t('security.two_factor') }}
                </h2>
                <p
                    v-if="props.twoFactor.required && !props.twoFactor.confirmed"
                    class="mb-4 rounded-sm bg-warning-soft px-3 py-2 text-sm leading-6 text-warning"
                    role="alert"
                >
                    {{ $t('security.two_factor_required') }}
                </p>

                <template v-if="!props.twoFactor.enabled">
                    <p class="mb-4 text-sm leading-6 text-ink-muted">{{ $t('security.two_factor_off') }}</p>
                    <SButton variant="primary" @click="enable">{{ $t('security.enable') }}</SButton>
                </template>

                <template v-else-if="!props.twoFactor.confirmed">
                    <p class="mb-4 text-sm leading-6">{{ $t('security.scan_text') }}</p>
                    <!-- eslint-disable-next-line vue/no-v-html -- SVG generated server-side by Fortify, not user input -->
                    <div class="mb-3 inline-block rounded-md bg-white p-3" v-html="props.twoFactor.qrSvg" />
                    <p class="mb-4 text-sm leading-6 text-ink-muted">
                        {{ $t('security.setup_key') }}
                        <code class="font-mono break-all text-ink">{{ props.twoFactor.setupKey }}</code>
                    </p>
                    <form class="flex flex-wrap items-end gap-2" novalidate @submit.prevent="confirm">
                        <SField
                            id="code"
                            v-slot="f"
                            :label="$t('app.two_factor_code')"
                            :error="confirmError"
                            class="w-40"
                        >
                            <input
                                id="code"
                                v-model="confirmForm.code"
                                type="text"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                maxlength="6"
                                class="s-input tabular-nums"
                                :aria-invalid="f.invalid"
                                :aria-describedby="f.describedby"
                            />
                        </SField>
                        <SButton type="submit" variant="primary" :loading="confirmForm.processing">
                            {{ $t('security.confirm') }}
                        </SButton>
                    </form>
                </template>

                <template v-else>
                    <p class="mb-4 text-sm leading-6 text-success">{{ $t('security.enabled_text') }}</p>
                    <h3 class="text-[13px] leading-[22px] font-medium">{{ $t('security.recovery_codes') }}</h3>
                    <p class="mb-2 text-sm leading-6 text-ink-muted">{{ $t('security.recovery_text') }}</p>
                    <ul class="mb-4 grid grid-cols-2 gap-1 rounded-sm bg-surface-sunken p-3 font-mono text-sm">
                        <li v-for="code in props.twoFactor.recoveryCodes" :key="code">{{ code }}</li>
                    </ul>
                    <div class="flex flex-wrap gap-2">
                        <SButton @click="regenerate">{{ $t('security.regenerate') }}</SButton>
                        <SButton v-if="!props.twoFactor.required" variant="danger" @click="disable">
                            {{ $t('security.disable') }}
                        </SButton>
                    </div>
                </template>
            </section>

            <section :class="card" aria-labelledby="password-title">
                <h2 id="password-title" class="mb-4 text-lg leading-[30px] font-semibold">
                    {{ $t('security.change_password') }}
                </h2>
                <form class="space-y-4" novalidate @submit.prevent="updatePassword">
                    <SField
                        id="current_password"
                        v-slot="f"
                        :label="$t('security.current_password')"
                        :error="passwordErrors.current_password"
                    >
                        <input
                            id="current_password"
                            v-model="passwordForm.current_password"
                            type="password"
                            autocomplete="current-password"
                            class="s-input"
                            :aria-invalid="f.invalid"
                            :aria-describedby="f.describedby"
                        />
                    </SField>
                    <SField
                        id="new_password"
                        v-slot="f"
                        :label="$t('security.new_password')"
                        :error="passwordErrors.password"
                    >
                        <input
                            id="new_password"
                            v-model="passwordForm.password"
                            type="password"
                            autocomplete="new-password"
                            class="s-input"
                            :aria-invalid="f.invalid"
                            :aria-describedby="f.describedby"
                        />
                    </SField>
                    <SField id="new_password_confirmation" v-slot="f" :label="$t('app.confirm_password')">
                        <input
                            id="new_password_confirmation"
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="s-input"
                            :aria-describedby="f.describedby"
                        />
                    </SField>
                    <SButton type="submit" variant="primary" :loading="passwordForm.processing">
                        {{ $t('app.save') }}
                    </SButton>
                </form>
            </section>
        </div>
    </component>
</template>
