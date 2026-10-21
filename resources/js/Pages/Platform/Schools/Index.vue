<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { Building2, Plus } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InviteLink from '@/Components/InviteLink.vue';
import SButton from '@/Components/SButton.vue';
import SField from '@/Components/SField.vue';

defineProps({ schools: { type: Array, required: true } });

const page = usePage();
const inviting = ref(null);
const inviteForm = useForm({ email: '', phone: '' });

function newLink(school) {
    inviteForm.post(`/platform/schools/${school.id}/invitations`, {
        preserveScroll: true,
        onSuccess: () => {
            inviting.value = null;
            inviteForm.reset();
        },
    });
}
</script>

<template>
    <AdminLayout :title="$t('platform.schools_title')">
        <div class="mb-6 flex justify-end">
            <Link href="/platform/schools/create" class="s-btn s-btn-primary">
                <Plus :size="20" :stroke-width="1.75" aria-hidden="true" />
                {{ $t('platform.new_school') }}
            </Link>
        </div>

        <InviteLink v-if="page.props.flash.inviteLink" :link="page.props.flash.inviteLink" class="mb-6" />

        <EmptyState
            v-if="schools.length === 0"
            :icon="Building2"
            :title="$t('platform.empty_title')"
            :text="$t('platform.empty_text')"
        />

        <ul v-else class="divide-y divide-border rounded-md border border-border bg-surface-raised shadow-sm">
            <li v-for="school in schools" :key="school.id" class="p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold">{{ school.name_km }}</p>
                        <p class="text-sm leading-6 text-ink-muted">
                            {{ school.name_en }} · {{ school.code }} · {{ school.province }} ·
                            {{ $t('platform.members_count', { count: school.members }) }}
                        </p>
                    </div>
                    <SButton variant="ghost" @click="inviting = inviting === school.id ? null : school.id">
                        {{ $t('platform.new_director_link') }}
                    </SButton>
                </div>

                <form
                    v-if="inviting === school.id"
                    class="mt-4 grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                    novalidate
                    @submit.prevent="newLink(school)"
                >
                    <SField
                        :id="`email-${school.id}`"
                        v-slot="f"
                        :label="$t('app.email')"
                        :error="inviteForm.errors.email"
                    >
                        <input
                            :id="`email-${school.id}`"
                            v-model="inviteForm.email"
                            type="email"
                            class="s-input"
                            :aria-invalid="f.invalid"
                            :aria-describedby="f.describedby"
                        />
                    </SField>
                    <SField
                        :id="`phone-${school.id}`"
                        v-slot="f"
                        :label="$t('app.phone')"
                        :error="inviteForm.errors.phone"
                    >
                        <input
                            :id="`phone-${school.id}`"
                            v-model="inviteForm.phone"
                            type="tel"
                            class="s-input"
                            :aria-invalid="f.invalid"
                            :aria-describedby="f.describedby"
                        />
                    </SField>
                    <SButton type="submit" variant="primary" :loading="inviteForm.processing">
                        {{ $t('members.create_link') }}
                    </SButton>
                </form>
            </li>
        </ul>
    </AdminLayout>
</template>
