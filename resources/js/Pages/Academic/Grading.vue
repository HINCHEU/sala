<script setup>
import { reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AcademicTabs from '@/Components/AcademicTabs.vue';
import SButton from '@/Components/SButton.vue';

const props = defineProps({ scales: { type: Array, required: true } });

const mentions = ['excellent', 'very_good', 'good', 'fairly_good', 'average', 'weak'];

// Same tones as the MentionChip in the design system.
const tone = {
    excellent: 'bg-gold text-on-gold',
    very_good: 'bg-success-soft text-success',
    good: 'bg-primary-soft text-primary',
    fairly_good: 'bg-surface-sunken text-ink',
    average: 'bg-warning-soft text-warning',
    weak: 'bg-danger-soft text-danger',
};

const forms = reactive(
    Object.fromEntries(
        props.scales.map((scale) => [scale.stage, useForm({ bands: scale.bands.map((b) => ({ ...b })) })]),
    ),
);

const save = (stage) => forms[stage].put(`/academic/grading/${stage}`, { preserveScroll: true });
const addBand = (stage) =>
    forms[stage].bands.push({ min: 0, max: 0, mention: 'weak', mention_km: '', mention_en: '', letter: '' });
const removeBand = (stage, index) => forms[stage].bands.splice(index, 1);

const fields = [
    ['min', 'academic.from', 'number'],
    ['max', 'academic.to', 'number'],
];
</script>

<template>
    <AdminLayout :title="$t('academic.title')">
        <AcademicTabs />
        <p class="mb-4 text-sm leading-6 text-ink-muted">{{ $t('academic.grading_hint') }}</p>

        <div class="space-y-6">
            <section
                v-for="scale in scales"
                :key="scale.stage"
                class="rounded-md border border-border bg-surface-raised shadow-sm"
                :aria-labelledby="`stage-${scale.stage}`"
            >
                <header class="flex flex-wrap items-center gap-2 border-b border-border px-4 py-3">
                    <h2 :id="`stage-${scale.stage}`" class="text-lg leading-[30px] font-semibold">
                        {{ $t(`academic.stages.${scale.stage}`) }}
                    </h2>
                    <span
                        v-if="!scale.saved"
                        class="rounded-full bg-warning-soft px-2.5 text-[13px] leading-[22px] font-medium text-warning"
                    >
                        {{ $t('academic.unsaved_default') }}
                    </span>
                </header>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-surface-sunken text-left text-[13px] leading-[22px]">
                            <tr>
                                <th scope="col" class="px-3 py-2 font-medium">{{ $t('academic.from') }}</th>
                                <th scope="col" class="px-3 py-2 font-medium">{{ $t('academic.to') }}</th>
                                <th scope="col" class="px-3 py-2 font-medium">{{ $t('academic.mention') }}</th>
                                <th scope="col" class="px-3 py-2 font-medium">{{ $t('academic.mention_km') }}</th>
                                <th scope="col" class="px-3 py-2 font-medium">{{ $t('academic.mention_en') }}</th>
                                <th scope="col" class="px-3 py-2 font-medium">{{ $t('academic.letter') }}</th>
                                <th scope="col">
                                    <span class="sr-only">{{ $t('academic.actions') }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="(band, i) in forms[scale.stage].bands" :key="i" class="align-top">
                                <td v-for="[key, label] in fields" :key="key" class="px-2 py-1">
                                    <input
                                        v-model="band[key]"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="s-input w-24 tabular-nums"
                                        :aria-label="$t(label)"
                                        :aria-invalid="!!forms[scale.stage].errors[`bands.${i}.${key}`]"
                                    />
                                    <p
                                        v-if="forms[scale.stage].errors[`bands.${i}.${key}`]"
                                        class="mt-1 text-[13px] text-danger"
                                    >
                                        {{ forms[scale.stage].errors[`bands.${i}.${key}`] }}
                                    </p>
                                </td>
                                <td class="px-2 py-1">
                                    <div class="flex items-center gap-2">
                                        <select
                                            v-model="band.mention"
                                            class="s-input min-w-32"
                                            :aria-label="$t('academic.mention')"
                                        >
                                            <option v-for="m in mentions" :key="m" :value="m">
                                                {{ $t(`academic.mentions.${m}`) }}
                                            </option>
                                        </select>
                                        <span
                                            class="rounded-full px-2.5 text-[13px] leading-[22px] font-medium whitespace-nowrap"
                                            :class="tone[band.mention]"
                                        >
                                            {{ band.mention_km || $t(`academic.mentions.${band.mention}`) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-2 py-1">
                                    <input
                                        v-model="band.mention_km"
                                        class="s-input min-w-28"
                                        :aria-label="$t('academic.mention_km')"
                                    />
                                </td>
                                <td class="px-2 py-1">
                                    <input
                                        v-model="band.mention_en"
                                        lang="en"
                                        class="s-input min-w-28"
                                        :aria-label="$t('academic.mention_en')"
                                    />
                                </td>
                                <td class="px-2 py-1">
                                    <input
                                        v-model="band.letter"
                                        maxlength="2"
                                        class="s-input w-16"
                                        :aria-label="$t('academic.letter')"
                                    />
                                </td>
                                <td class="px-2 py-1">
                                    <button
                                        type="button"
                                        class="s-btn s-btn-ghost px-2 text-danger"
                                        :aria-label="$t('academic.delete')"
                                        @click="removeBand(scale.stage, i)"
                                    >
                                        <Trash2 :size="18" :stroke-width="1.75" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-border px-4 py-3">
                    <SButton variant="ghost" @click="addBand(scale.stage)">
                        <Plus :size="20" :stroke-width="1.75" aria-hidden="true" />
                        {{ $t('academic.add_band') }}
                    </SButton>
                    <p v-if="forms[scale.stage].errors.bands" class="text-sm text-danger">
                        {{ forms[scale.stage].errors.bands }}
                    </p>
                    <SButton variant="primary" :loading="forms[scale.stage].processing" @click="save(scale.stage)">
                        {{ $t('app.save') }}
                    </SButton>
                </footer>
            </section>
        </div>
    </AdminLayout>
</template>
