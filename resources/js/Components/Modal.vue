<script setup>
import { onMounted, onUnmounted, watch } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: 'md', // sm, md, lg, xl, 2xl
    },
    title: {
        type: String,
        default: null,
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show && props.closeable) {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});

watch(
    () => props.show,
    (show) => {
        if (show) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    },
);

const maxWidthClass = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
}[props.maxWidth] ?? 'sm:max-w-md';
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
            >
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-[#0a0d11]/50 backdrop-blur-xs transition-opacity"
                    aria-hidden="true"
                    @click="close"
                />

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <!-- Modal Panel -->
                <Transition
                    enter-active-class="ease-out duration-200"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-150"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-if="show"
                        class="relative inline-block w-full transform overflow-hidden rounded-lg border border-border-strong bg-surface-raised text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle"
                        :class="maxWidthClass"
                        role="dialog"
                        aria-modal="true"
                    >
                        <!-- Header (if title provided) -->
                        <div
                            v-if="title"
                            class="flex items-center justify-between border-b border-border px-6 py-4"
                        >
                            <h3 class="text-base font-bold text-ink">
                                {{ title }}
                            </h3>
                            <button
                                v-if="closeable"
                                type="button"
                                class="rounded p-1 text-ink-muted transition-colors hover:bg-surface-sunken hover:text-ink focus:outline-none"
                                @click="close"
                            >
                                <X class="size-4" />
                            </button>
                        </div>

                        <!-- Content Slot -->
                        <slot />
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
