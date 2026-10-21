<script setup>
import { router, useForm, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import dayjs from 'dayjs';
import { Users } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InviteLink from '@/Components/InviteLink.vue';
import SButton from '@/Components/SButton.vue';
import SField from '@/Components/SField.vue';
import { confirmDanger } from '@/lib/alert';

defineProps({
    members: { type: Array, required: true },
    invitations: { type: Array, required: true },
    roles: { type: Array, required: true },
});

const page = usePage();
const form = useForm({ role: 'subject_teacher', email: '', phone: '' });

const invite = () =>
    form.post('/members/invitations', { preserveScroll: true, onSuccess: () => form.reset('email', 'phone') });

async function resetPassword(member) {
    const ok = await confirmDanger({
        title: trans('members.reset_confirm_title', { name: member.name }),
        text: trans('members.reset_confirm_text'),
        confirmText: trans('members.reset_password'),
    });
    if (ok) router.post(`/members/${member.id}/password`, {}, { preserveScroll: true });
}

const card = 'rounded-md border border-border bg-surface-raised shadow-sm';
</script>

<template>
    <AdminLayout :title="$t('members.title')">
        <div class="space-y-6">
            <section :class="[card, 'p-4 sm:p-6']" aria-labelledby="invite-title">
                <h2 id="invite-title" class="text-lg leading-[30px] font-semibold">{{ $t('members.invite_title') }}</h2>
                <p class="mb-4 text-sm leading-6 text-ink-muted">{{ $t('members.contact_hint') }}</p>
                <form
                    class="grid gap-4 md:grid-cols-[1fr_1fr_1fr_auto] md:items-end"
                    novalidate
                    @submit.prevent="invite"
                >
                    <SField id="role" v-slot="f" :label="$t('members.role')" :error="form.errors.role">
                        <select id="role" v-model="form.role" class="s-input" :aria-describedby="f.describedby">
                            <option v-for="role in roles" :key="role" :value="role">{{ $t(`roles.${role}`) }}</option>
                        </select>
                    </SField>
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
                    <SButton type="submit" variant="primary" :loading="form.processing">
                        {{ $t('members.create_link') }}
                    </SButton>
                </form>
                <InviteLink v-if="page.props.flash.inviteLink" :link="page.props.flash.inviteLink" class="mt-4" />
            </section>

            <div
                v-if="page.props.flash.tempPassword"
                role="status"
                class="rounded-md border border-warning bg-warning-soft p-4"
            >
                <p class="font-semibold text-warning">
                    {{ $t('members.temp_password_title', { name: page.props.flash.tempPassword.name }) }}
                </p>
                <p class="my-2 font-mono text-lg tracking-wider">{{ page.props.flash.tempPassword.password }}</p>
                <p class="text-sm leading-6">{{ $t('members.temp_password_text') }}</p>
            </div>

            <section v-if="invitations.length" :class="card" aria-labelledby="pending-title">
                <h2 id="pending-title" class="border-b border-border px-4 py-3 font-semibold">
                    {{ $t('members.pending') }}
                </h2>
                <ul class="divide-y divide-border">
                    <li
                        v-for="inv in invitations"
                        :key="inv.id"
                        class="flex flex-wrap justify-between gap-2 px-4 py-3 text-sm"
                    >
                        <span>{{ inv.email ?? inv.phone }} · {{ $t(`roles.${inv.role}`) }}</span>
                        <span class="text-ink-muted">
                            {{ $t('members.expires', { date: dayjs(inv.expires_at).format('DD/MM/YYYY') }) }}
                        </span>
                    </li>
                </ul>
            </section>

            <EmptyState
                v-if="members.length === 0"
                :icon="Users"
                :title="$t('members.empty_title')"
                :text="$t('members.empty_text')"
            />

            <ul v-else :class="[card, 'divide-y divide-border']">
                <li
                    v-for="member in members"
                    :key="member.id"
                    class="flex flex-wrap items-center justify-between gap-3 p-4"
                >
                    <div class="min-w-0">
                        <p class="font-semibold">{{ member.name }}</p>
                        <p class="text-sm leading-6 text-ink-muted">
                            {{ [member.email, member.phone].filter(Boolean).join(' · ') }}
                        </p>
                        <div class="mt-1 flex flex-wrap gap-1">
                            <span
                                v-for="role in member.roles"
                                :key="role"
                                class="rounded-full bg-primary-soft px-2.5 text-[13px] leading-[22px] font-medium text-primary"
                            >
                                {{ $t(`roles.${role}`) }}
                            </span>
                            <span
                                v-if="member.status !== 'active'"
                                class="rounded-full bg-surface-sunken px-2.5 text-[13px] leading-[22px] font-medium text-ink-muted"
                            >
                                {{ $t(`members.status.${member.status}`) }}
                            </span>
                        </div>
                    </div>
                    <SButton
                        v-if="member.id !== page.props.auth.user.id"
                        variant="ghost"
                        @click="resetPassword(member)"
                    >
                        {{ $t('members.reset_password') }}
                    </SButton>
                </li>
            </ul>
        </div>
    </AdminLayout>
</template>
