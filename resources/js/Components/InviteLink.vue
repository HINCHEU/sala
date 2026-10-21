<script setup>
import { trans } from 'laravel-vue-i18n';
import { Copy } from 'lucide-vue-next';
import SButton from '@/Components/SButton.vue';
import { notifySuccess } from '@/lib/alert';

// Shown once after an invitation is created: the plain link is not stored anywhere.
const props = defineProps({ link: { type: String, required: true } });

async function copy() {
    await navigator.clipboard.writeText(props.link);
    notifySuccess(trans('app.copied'));
}
</script>

<template>
    <div role="status" class="rounded-md border border-success bg-success-soft p-4">
        <p class="font-semibold text-success">{{ $t('members.link_ready') }}</p>
        <p class="mb-3 text-sm leading-6">{{ $t('members.link_hint') }}</p>
        <div class="flex flex-wrap gap-2">
            <input
                :value="link"
                readonly
                class="s-input min-w-0 flex-1 font-mono text-sm"
                :aria-label="$t('members.link_ready')"
                @focus="$event.target.select()"
            />
            <SButton @click="copy">
                <Copy :size="20" :stroke-width="1.75" aria-hidden="true" />
                {{ $t('app.copy') }}
            </SButton>
        </div>
    </div>
</template>
