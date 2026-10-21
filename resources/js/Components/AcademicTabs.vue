<script setup>
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const tabs = ['years', 'structure', 'curriculum', 'grading', 'classrooms'];
const href = (tab) => `/academic/${tab}`;
</script>

<template>
    <nav :aria-label="$t('academic.title')" class="-mx-4 mb-6 overflow-x-auto px-4">
        <ul class="flex min-w-max gap-1 border-b border-border">
            <li v-for="tab in tabs" :key="tab">
                <Link
                    :href="href(tab)"
                    class="-mb-px flex min-h-11 items-center border-b-2 px-3 text-sm whitespace-nowrap"
                    :class="
                        page.url.startsWith(href(tab))
                            ? 'border-primary font-semibold text-primary'
                            : 'border-transparent text-ink-muted hover:text-ink'
                    "
                    :aria-current="page.url.startsWith(href(tab)) ? 'page' : undefined"
                >
                    {{ $t(`academic.tabs.${tab}`) }}
                </Link>
            </li>
        </ul>
    </nav>
</template>
