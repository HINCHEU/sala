<script setup>
import { computed, useId } from 'vue';

defineProps({
    /**
     * 'hero': Teal & tan-gold Khmer kbach lattice for login / guest pages
     * 'subtle': Subtle currentColor watermark for admin / teacher dashboards
     * 'ribbon': Horizontal ornamental trim bar for cards & headers
     */
    variant: {
        type: String,
        default: 'hero', // 'hero' | 'subtle' | 'ribbon'
    },
    opacity: {
        type: [Number, String],
        default: null,
    },
});

const uid = useId();
const goldGradId = computed(() => `khmer-gold-${uid}`);
const bgGradId = computed(() => `khmer-bg-${uid}`);
const vignetteGradId = computed(() => `khmer-vig-${uid}`);
const patternId = computed(() => `khmer-pat-${uid}`);
const ribbonPatId = computed(() => `khmer-ribbon-${uid}`);
const bandId = computed(() => `khmer-band-${uid}`);
const juncId = computed(() => `khmer-junc-${uid}`);
const smallId = computed(() => `khmer-small-${uid}`);
const bigId = computed(() => `khmer-big-${uid}`);
</script>

<template>
    <!-- 1. HERO / LOGIN VARIANT: teal backdrop with tan-gold Khmer Kbach lattice
         (beaded diagonal bands, junction flowers, large Kbach bouquet + small flame crown) -->
    <div v-if="variant === 'hero'" class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <svg class="size-full" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <defs>
                <!-- Deep Royal Midnight Teal Background Gradient -->
                <radialGradient :id="bgGradId" cx="50%" cy="40%" r="72%">
                    <stop offset="0%" stop-color="#0a3c4f" />
                    <stop offset="35%" stop-color="#062837" />
                    <stop offset="70%" stop-color="#031823" />
                    <stop offset="100%" stop-color="#010c12" />
                </radialGradient>

                <!-- Soft Dark Vignette to eliminate glare and create executive contrast -->
                <radialGradient :id="vignetteGradId" cx="50%" cy="48%" r="68%">
                    <stop offset="0%" stop-color="#000000" stop-opacity="0.12" />
                    <stop offset="50%" stop-color="#000000" stop-opacity="0.45" />
                    <stop offset="100%" stop-color="#000000" stop-opacity="0.8" />
                </radialGradient>

                <g fill="#c3ad8e">
                    <!-- Beaded lattice band (runs along +x from a junction) -->
                    <g :id="bandId">
                        <path
                            d="M15.5 -3.6H88.44V3.6H15.5Z"
                            fill="none"
                            stroke="#c3ad8e"
                            stroke-opacity="0.6"
                            stroke-width="1.6"
                        />
                        <circle cx="18" cy="0" r="1.9" />
                        <circle cx="23.23" cy="0" r="1.9" />
                        <circle cx="28.45" cy="0" r="1.9" />
                        <circle cx="33.68" cy="0" r="1.9" />
                        <circle cx="38.91" cy="0" r="1.9" />
                        <circle cx="44.13" cy="0" r="1.9" />
                        <circle cx="49.36" cy="0" r="1.9" />
                        <circle cx="54.59" cy="0" r="1.9" />
                        <circle cx="59.81" cy="0" r="1.9" />
                        <circle cx="65.04" cy="0" r="1.9" />
                        <circle cx="70.27" cy="0" r="1.9" />
                        <circle cx="75.49" cy="0" r="1.9" />
                        <circle cx="80.72" cy="0" r="1.9" />
                        <circle cx="85.94" cy="0" r="1.9" />
                    </g>

                    <!-- Junction flower: 4 teardrop petals + 4 slim diagonal leaves -->
                    <g :id="juncId">
                        <circle r="2.9" />
                        <g>
                            <g transform="translate(0 -4.3)">
                                <path
                                    d="M0 0C2.89 0 3.7 -2.44 3.7 -4.64C3.7 -7.32 1.85 -8.78 0.59 -10.74L0 -12.2L-0.59 -10.74C-1.85 -8.78 -3.7 -7.32 -3.7 -4.64C-3.7 -2.44 -2.89 0 0 0Z"
                                />
                            </g>
                        </g>
                        <g transform="rotate(90)">
                            <g transform="translate(0 -4.3)">
                                <path
                                    d="M0 0C2.89 0 3.7 -2.44 3.7 -4.64C3.7 -7.32 1.85 -8.78 0.59 -10.74L0 -12.2L-0.59 -10.74C-1.85 -8.78 -3.7 -7.32 -3.7 -4.64C-3.7 -2.44 -2.89 0 0 0Z"
                                />
                            </g>
                        </g>
                        <g transform="rotate(180)">
                            <g transform="translate(0 -4.3)">
                                <path
                                    d="M0 0C2.89 0 3.7 -2.44 3.7 -4.64C3.7 -7.32 1.85 -8.78 0.59 -10.74L0 -12.2L-0.59 -10.74C-1.85 -8.78 -3.7 -7.32 -3.7 -4.64C-3.7 -2.44 -2.89 0 0 0Z"
                                />
                            </g>
                        </g>
                        <g transform="rotate(270)">
                            <g transform="translate(0 -4.3)">
                                <path
                                    d="M0 0C2.89 0 3.7 -2.44 3.7 -4.64C3.7 -7.32 1.85 -8.78 0.59 -10.74L0 -12.2L-0.59 -10.74C-1.85 -8.78 -3.7 -7.32 -3.7 -4.64C-3.7 -2.44 -2.89 0 0 0Z"
                                />
                            </g>
                        </g>
                        <ellipse cx="0" cy="-9.2" rx="0.9" ry="3" transform="rotate(45)" />
                        <ellipse cx="0" cy="-9.2" rx="0.9" ry="3" transform="rotate(135)" />
                        <ellipse cx="0" cy="-9.2" rx="0.9" ry="3" transform="rotate(225)" />
                        <ellipse cx="0" cy="-9.2" rx="0.9" ry="3" transform="rotate(315)" />
                    </g>

                    <!-- Small motif: flame bud crown with kranok hooks -->
                    <g :id="smallId" stroke="#072b3a" stroke-width="0.95" stroke-linejoin="round">
                        <g transform="translate(-0.3 19.2) scale(0.66 0.86)">
                            <path
                                d="M-23 2.6C-28 3.2-35 1.5-37.8-3.5C-40-8-38.5-14-34.5-15.6C-31-16.8-28.4-13.6-29.6-10.8C-30.6-8.8-33-9-33.4-10.6C-32.2-7.2-29.6-2.2-23 2.6Z"
                            />
                        </g>
                        <g transform="translate(0.3 19.2) scale(0.66 0.86)">
                            <g transform="scale(-1 1)">
                                <path
                                    d="M-23 2.6C-28 3.2-35 1.5-37.8-3.5C-40-8-38.5-14-34.5-15.6C-31-16.8-28.4-13.6-29.6-10.8C-30.6-8.8-33-9-33.4-10.6C-32.2-7.2-29.6-2.2-23 2.6Z"
                                />
                            </g>
                        </g>
                        <g transform="translate(-5 20) rotate(-27)">
                            <path
                                d="M0 0C5.28 0 6.6 -3.08 6.6 -7.26C6.6 -9.9 5.84 -11.66 5.14 -13.64C4.65 -13.42 4.03 -13.2 3.55 -13.86C3.19 -16.72 0.37 -18.92 -1.8 -22C-3.07 -18.92 -4.99 -16.72 -5.17 -13.86C-5.47 -13.2 -5.91 -13.42 -6.22 -13.64C-6.56 -11.66 -6.6 -9.9 -6.6 -7.26C-6.6 -3.08 -5.28 0 0 0Z"
                            />
                            <path d="M0.79 -1.32C3.63 -3.52 4.39 -7.92 3.33 -12.76" fill="none" />
                            <path d="M-0.79 -1.32C-3.63 -3.52 -5.11 -7.92 -4.59 -12.76" fill="none" />
                            <g transform="translate(0 -2.2)">
                                <path
                                    d="M0 0C2.06 0 2.64 -2.2 2.64 -4.18C2.64 -6.6 0.96 -7.92 -0.19 -9.68L-0.72 -11L-1.03 -9.68C-1.68 -7.92 -2.64 -6.6 -2.64 -4.18C-2.64 -2.2 -2.06 0 0 0Z"
                                />
                            </g>
                        </g>
                        <g transform="translate(5 20) rotate(27)">
                            <path
                                d="M0 0C5.28 0 6.6 -3.08 6.6 -7.26C6.6 -9.9 6.56 -11.66 6.22 -13.64C5.91 -13.42 5.47 -13.2 5.17 -13.86C4.99 -16.72 3.07 -18.92 1.8 -22C-0.37 -18.92 -3.19 -16.72 -3.55 -13.86C-4.03 -13.2 -4.65 -13.42 -5.14 -13.64C-5.84 -11.66 -6.6 -9.9 -6.6 -7.26C-6.6 -3.08 -5.28 0 0 0Z"
                            />
                            <path d="M0.79 -1.32C3.63 -3.52 5.11 -7.92 4.59 -12.76" fill="none" />
                            <path d="M-0.79 -1.32C-3.63 -3.52 -4.39 -7.92 -3.33 -12.76" fill="none" />
                            <g transform="translate(0 -2.2)">
                                <path
                                    d="M0 0C2.06 0 2.64 -2.2 2.64 -4.18C2.64 -6.6 1.68 -7.92 1.03 -9.68L0.72 -11L0.19 -9.68C-0.96 -7.92 -2.64 -6.6 -2.64 -4.18C-2.64 -2.2 -2.06 0 0 0Z"
                                />
                            </g>
                        </g>
                        <g transform="translate(0 3)">
                            <path
                                d="M0 0C7.04 0 8.8 -3.64 8.8 -8.58C8.8 -11.7 8.39 -13.78 7.75 -16.12C7.25 -15.86 6.58 -15.6 6.08 -16.38C5.76 -19.76 2.74 -22.36 0.6 -26C-1.84 -22.36 -5.16 -19.76 -5.54 -16.38C-6.1 -15.6 -6.83 -15.86 -7.39 -16.12C-8.15 -13.78 -8.8 -11.7 -8.8 -8.58C-8.8 -3.64 -7.04 0 0 0Z"
                            />
                            <path d="M1.06 -1.56C4.84 -4.16 6.46 -9.36 5.49 -15.08" fill="none" />
                            <path d="M-1.06 -1.56C-4.84 -4.16 -6.22 -9.36 -5.07 -15.08" fill="none" />
                            <g transform="translate(0 -2.6)">
                                <path
                                    d="M0 0C2.75 0 3.52 -2.6 3.52 -4.94C3.52 -7.8 1.88 -9.36 0.77 -11.44L0.24 -13L-0.36 -11.44C-1.64 -9.36 -3.52 -7.8 -3.52 -4.94C-3.52 -2.6 -2.75 0 0 0Z"
                                />
                            </g>
                        </g>
                        <g transform="translate(0 17.5)">
                            <path
                                d="M0 0C5.92 0 7.4 -2.38 7.4 -5.61C7.4 -7.65 6.96 -9.01 6.36 -10.54C5.92 -10.37 5.33 -10.2 4.88 -10.71C4.59 -12.92 1.92 -14.62 0 -17C-1.92 -14.62 -4.59 -12.92 -4.88 -10.71C-5.33 -10.2 -5.92 -10.37 -6.36 -10.54C-6.96 -9.01 -7.4 -7.65 -7.4 -5.61C-7.4 -2.38 -5.92 0 0 0Z"
                            />
                            <path d="M0.89 -1.02C4.07 -2.72 5.33 -6.12 4.44 -9.86" fill="none" />
                            <path d="M-0.89 -1.02C-4.07 -2.72 -5.33 -6.12 -4.44 -9.86" fill="none" />
                            <g transform="translate(0 -1.7)">
                                <path
                                    d="M0 0C2.31 0 2.96 -1.7 2.96 -3.23C2.96 -5.1 1.48 -6.12 0.47 -7.48L0 -8.5L-0.47 -7.48C-1.48 -6.12 -2.96 -5.1 -2.96 -3.23C-2.96 -1.7 -2.31 0 0 0Z"
                                />
                            </g>
                        </g>
                    </g>

                    <!-- Large motif: Kbach bouquet (top bud, lotus, side flames, hooks, leaves, inverted bud) -->
                    <g :id="bigId" stroke="#072b3a" stroke-width="1.15" stroke-linejoin="round">
                        <g transform="translate(1.5 0)">
                            <path
                                d="M-23 2.6C-28 3.2-35 1.5-37.8-3.5C-40-8-38.5-14-34.5-15.6C-31-16.8-28.4-13.6-29.6-10.8C-30.6-8.8-33-9-33.4-10.6C-32.2-7.2-29.6-2.2-23 2.6Z"
                            />
                        </g>
                        <g transform="translate(-1.5 0)">
                            <g transform="scale(-1 1)">
                                <path
                                    d="M-23 2.6C-28 3.2-35 1.5-37.8-3.5C-40-8-38.5-14-34.5-15.6C-31-16.8-28.4-13.6-29.6-10.8C-30.6-8.8-33-9-33.4-10.6C-32.2-7.2-29.6-2.2-23 2.6Z"
                                />
                            </g>
                        </g>
                        <path d="M-2 0H2V21H-2Z" />
                        <path d="M-1.8 6C-4.5 4.6-8.5 5.6-11 10C-8 8-4.8 7.8-1.8 9.2Z" />
                        <path d="M1.8 6C4.5 4.6 8.5 5.6 11 10C8 8 4.8 7.8 1.8 9.2Z" />
                        <g transform="translate(-8.5 12) rotate(-80)">
                            <path
                                d="M0 0C7.04 0 8.8 -3.71 8.8 -8.75C8.8 -11.93 8.57 -14.04 8.02 -16.43C7.57 -16.16 6.94 -15.9 6.48 -16.7C6.21 -20.14 3.41 -22.79 1.5 -26.5C-1.16 -22.79 -4.71 -20.14 -5.13 -16.7C-5.74 -15.9 -6.52 -16.16 -7.12 -16.43C-7.97 -14.04 -8.8 -11.93 -8.8 -8.75C-8.8 -3.71 -7.04 0 0 0Z"
                            />
                            <path d="M1.06 -1.59C4.84 -4.24 6.64 -9.54 5.8 -15.37" fill="none" />
                            <path d="M-1.06 -1.59C-4.84 -4.24 -6.04 -9.54 -4.76 -15.37" fill="none" />
                            <g transform="translate(0 -2.65)">
                                <path
                                    d="M0 0C2.75 0 3.52 -2.65 3.52 -5.04C3.52 -7.95 2.06 -9.54 1.07 -11.66L0.6 -13.25L-0.05 -11.66C-1.46 -9.54 -3.52 -7.95 -3.52 -5.04C-3.52 -2.65 -2.75 0 0 0Z"
                                />
                            </g>
                        </g>
                        <g transform="translate(8.5 12) rotate(80)">
                            <path
                                d="M0 0C7.04 0 8.8 -3.71 8.8 -8.75C8.8 -11.93 7.97 -14.04 7.12 -16.43C6.52 -16.16 5.74 -15.9 5.13 -16.7C4.71 -20.14 1.16 -22.79 -1.5 -26.5C-3.41 -22.79 -6.21 -20.14 -6.48 -16.7C-6.94 -15.9 -7.57 -16.16 -8.02 -16.43C-8.57 -14.04 -8.8 -11.93 -8.8 -8.75C-8.8 -3.71 -7.04 0 0 0Z"
                            />
                            <path d="M1.06 -1.59C4.84 -4.24 6.04 -9.54 4.76 -15.37" fill="none" />
                            <path d="M-1.06 -1.59C-4.84 -4.24 -6.64 -9.54 -5.8 -15.37" fill="none" />
                            <g transform="translate(0 -2.65)">
                                <path
                                    d="M0 0C2.75 0 3.52 -2.65 3.52 -5.04C3.52 -7.95 1.46 -9.54 0.05 -11.66L-0.6 -13.25L-1.07 -11.66C-2.06 -9.54 -3.52 -7.95 -3.52 -5.04C-3.52 -2.65 -2.75 0 0 0Z"
                                />
                            </g>
                        </g>
                        <g transform="translate(0 26.5) rotate(-50)">
                            <path
                                d="M0 0C3.59 0 4.6 -2.7 4.6 -5.13C4.6 -8.1 1.8 -9.72 -0.11 -11.88L-1 -13.5L-1.59 -11.88C-2.8 -9.72 -4.6 -8.1 -4.6 -5.13C-4.6 -2.7 -3.59 0 0 0Z"
                            />
                        </g>
                        <g transform="translate(0 26.5) rotate(50)">
                            <path
                                d="M0 0C3.59 0 4.6 -2.7 4.6 -5.13C4.6 -8.1 2.8 -9.72 1.59 -11.88L1 -13.5L0.11 -11.88C-1.8 -9.72 -4.6 -8.1 -4.6 -5.13C-4.6 -2.7 -3.59 0 0 0Z"
                            />
                        </g>
                        <g transform="translate(0 18.5) rotate(180)">
                            <path
                                d="M0 0C8.32 0 10.4 -4.13 10.4 -9.74C10.4 -13.28 9.78 -15.64 8.94 -18.29C8.32 -18 7.49 -17.7 6.86 -18.59C6.45 -22.42 2.7 -25.37 0 -29.5C-2.7 -25.37 -6.45 -22.42 -6.86 -18.59C-7.49 -17.7 -8.32 -18 -8.94 -18.29C-9.78 -15.64 -10.4 -13.28 -10.4 -9.74C-10.4 -4.13 -8.32 0 0 0Z"
                            />
                            <path d="M1.25 -1.77C5.72 -4.72 7.49 -10.62 6.24 -17.11" fill="none" />
                            <path d="M-1.25 -1.77C-5.72 -4.72 -7.49 -10.62 -6.24 -17.11" fill="none" />
                            <g transform="translate(0 -2.95)">
                                <path
                                    d="M0 0C3.24 0 4.16 -2.95 4.16 -5.61C4.16 -8.85 2.08 -10.62 0.67 -12.98L0 -14.75L-0.67 -12.98C-2.08 -10.62 -4.16 -8.85 -4.16 -5.61C-4.16 -2.95 -3.24 0 0 0Z"
                                />
                            </g>
                        </g>
                        <g transform="translate(-5.5 3.5) rotate(-25)">
                            <path
                                d="M0 0C7.04 0 8.8 -3.92 8.8 -9.24C8.8 -12.6 7.83 -14.84 6.91 -17.36C6.27 -17.08 5.46 -16.8 4.82 -17.64C4.36 -21.28 0.64 -24.08 -2.2 -28C-3.94 -24.08 -6.56 -21.28 -6.8 -17.64C-7.22 -16.8 -7.81 -17.08 -8.23 -17.36C-8.71 -14.84 -8.8 -12.6 -8.8 -9.24C-8.8 -3.92 -7.04 0 0 0Z"
                            />
                            <path d="M1.06 -1.68C4.84 -4.48 5.9 -10.08 4.51 -16.24" fill="none" />
                            <path d="M-1.06 -1.68C-4.84 -4.48 -6.78 -10.08 -6.05 -16.24" fill="none" />
                            <g transform="translate(0 -2.8)">
                                <path
                                    d="M0 0C2.75 0 3.52 -2.8 3.52 -5.32C3.52 -8.4 1.32 -10.08 -0.18 -12.32L-0.88 -14L-1.31 -12.32C-2.2 -10.08 -3.52 -8.4 -3.52 -5.32C-3.52 -2.8 -2.75 0 0 0Z"
                                />
                            </g>
                        </g>
                        <g transform="translate(5.5 3.5) rotate(25)">
                            <path
                                d="M0 0C7.04 0 8.8 -3.92 8.8 -9.24C8.8 -12.6 8.71 -14.84 8.23 -17.36C7.81 -17.08 7.22 -16.8 6.8 -17.64C6.56 -21.28 3.94 -24.08 2.2 -28C-0.64 -24.08 -4.36 -21.28 -4.82 -17.64C-5.46 -16.8 -6.27 -17.08 -6.91 -17.36C-7.83 -14.84 -8.8 -12.6 -8.8 -9.24C-8.8 -3.92 -7.04 0 0 0Z"
                            />
                            <path d="M1.06 -1.68C4.84 -4.48 6.78 -10.08 6.05 -16.24" fill="none" />
                            <path d="M-1.06 -1.68C-4.84 -4.48 -5.9 -10.08 -4.51 -16.24" fill="none" />
                            <g transform="translate(0 -2.8)">
                                <path
                                    d="M0 0C2.75 0 3.52 -2.8 3.52 -5.32C3.52 -8.4 2.2 -10.08 1.31 -12.32L0.88 -14L0.18 -12.32C-1.32 -10.08 -3.52 -8.4 -3.52 -5.32C-3.52 -2.8 -2.75 0 0 0Z"
                                />
                            </g>
                        </g>
                        <g transform="translate(0 -15.5)">
                            <path
                                d="M0 0C9.6 0 12 -4.62 12 -10.89C12 -14.85 11.44 -17.49 10.56 -20.46C9.88 -20.13 8.96 -19.8 8.28 -20.79C7.84 -25.08 3.72 -28.38 0.8 -33C-2.52 -28.38 -7.04 -25.08 -7.56 -20.79C-8.32 -19.8 -9.32 -20.13 -10.08 -20.46C-11.12 -17.49 -12 -14.85 -12 -10.89C-12 -4.62 -9.6 0 0 0Z"
                            />
                            <path d="M1.44 -1.98C6.6 -5.28 8.8 -11.88 7.48 -19.14" fill="none" />
                            <path d="M-1.44 -1.98C-6.6 -5.28 -8.48 -11.88 -6.92 -19.14" fill="none" />
                            <g transform="translate(0 -3.3)">
                                <path
                                    d="M0 0C3.74 0 4.8 -3.3 4.8 -6.27C4.8 -9.9 2.56 -11.88 1.04 -14.52L0.32 -16.5L-0.5 -14.52C-2.24 -11.88 -4.8 -9.9 -4.8 -6.27C-4.8 -3.3 -3.74 0 0 0Z"
                                />
                            </g>
                        </g>
                        <g transform="translate(0 3)">
                            <path
                                d="M0 0C7.68 0 9.6 -3.08 9.6 -7.26C9.6 -9.9 9.02 -11.66 8.26 -13.64C7.68 -13.42 6.91 -13.2 6.34 -13.86C5.95 -16.72 2.5 -18.92 0 -22C-2.5 -18.92 -5.95 -16.72 -6.34 -13.86C-6.91 -13.2 -7.68 -13.42 -8.26 -13.64C-9.02 -11.66 -9.6 -9.9 -9.6 -7.26C-9.6 -3.08 -7.68 0 0 0Z"
                            />
                            <path d="M1.15 -1.32C5.28 -3.52 6.91 -7.92 5.76 -12.76" fill="none" />
                            <path d="M-1.15 -1.32C-5.28 -3.52 -6.91 -7.92 -5.76 -12.76" fill="none" />
                            <g transform="translate(0 -2.2)">
                                <path
                                    d="M0 0C3 0 3.84 -2.2 3.84 -4.18C3.84 -6.6 1.92 -7.92 0.61 -9.68L0 -11L-0.61 -9.68C-1.92 -7.92 -3.84 -6.6 -3.84 -4.18C-3.84 -2.2 -3 0 0 0Z"
                                />
                            </g>
                        </g>
                        <circle cx="0" cy="3.6" r="2.8" />
                    </g>
                </g>

                <!-- Seamless 147x147 tile -->
                <pattern :id="patternId" width="147" height="147" patternUnits="userSpaceOnUse">
                    <g fill="#c3ad8e" fill-opacity="0.62">
                        <!-- lattice: the 4 edges of the diamond around the big motif -->
                        <use :href="`#${bandId}`" transform="translate(73.5 0) rotate(45)" />
                        <use :href="`#${bandId}`" transform="translate(0 73.5) rotate(-45)" />
                        <use :href="`#${bandId}`" transform="translate(0 73.5) rotate(45)" />
                        <use :href="`#${bandId}`" transform="translate(73.5 147) rotate(-45)" />
                        <!-- junction flowers on edge midpoints (both sides of each seam) -->
                        <use :href="`#${juncId}`" transform="translate(73.5 0)" />
                        <use :href="`#${juncId}`" transform="translate(73.5 147)" />
                        <use :href="`#${juncId}`" transform="translate(0 73.5)" />
                        <use :href="`#${juncId}`" transform="translate(147 73.5)" />
                        <!-- small motifs on the corners -->
                        <use :href="`#${smallId}`" transform="translate(0 0)" />
                        <use :href="`#${smallId}`" transform="translate(147 0)" />
                        <use :href="`#${smallId}`" transform="translate(0 147)" />
                        <use :href="`#${smallId}`" transform="translate(147 147)" />
                        <!-- big motif in the centre -->
                        <use :href="`#${bigId}`" transform="translate(73.5 73.5)" />
                    </g>
                </pattern>
            </defs>

            <!-- 1. Base deep royal midnight teal foundation -->
            <rect width="100%" height="100%" :fill="`url(#${bgGradId})`" />

            <!-- 2. Kbach lattice -->
            <rect width="100%" height="100%" :fill="`url(#${patternId})`" />

            <!-- 3. Soft Dark Vignette over everything to eliminate glare -->
            <rect width="100%" height="100%" :fill="`url(#${vignetteGradId})`" />
        </svg>
    </div>

    <!-- 2. SUBTLE APP WATERMARK VARIANT: Lightweight currentColor vector watermark for Admin & Teacher layouts -->
    <div
        v-else-if="variant === 'subtle'"
        class="pointer-events-none absolute inset-0 overflow-hidden text-primary transition-opacity duration-300"
        :style="{ opacity: opacity ?? '0.04' }"
        aria-hidden="true"
    >
        <svg class="size-full" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern :id="patternId" width="120" height="120" patternUnits="userSpaceOnUse">
                    <!-- Diamond Grid -->
                    <path d="M 60 0 L 120 60 L 60 120 L 0 60 Z" stroke="currentColor" stroke-width="1" fill="none" />

                    <!-- Center Kbach Motif -->
                    <g transform="translate(60, 60) scale(0.75)" fill="currentColor">
                        <path
                            d="M 0 -24 C 1.5 -20 2.5 -17 2 -14 C 1.2 -11 0 -9 0 -7 C 0 -9 -1.2 -11 -2 -14 C -2.5 -17 -1.5 -20 0 -24 Z"
                        />
                        <path
                            d="M 0 -16 C 2.5 -13 5 -9 4 -6 C 3 -3 0.8 -2 0 1 C -0.8 -2 -3 -3 -4 -6 C -5 -9 -2.5 -13 0 -16 Z"
                        />
                        <path
                            d="M 0 -7 C 3 -8 9 -8 11 -4 C 12 0 9 3 6 1 C 8 0 9 -2 7 -4 C 5 -6 2 -5 0 -3 C -2 -5 -5 -6 -7 -4 C -9 -2 -8 0 -6 1 C -9 3 -12 0 -11 -4 C -9 -8 -3 -8 0 -7 Z"
                        />
                        <path
                            d="M 0 0 C 4 -2 13 0 15 6 C 16 12 11 15 7 13 C 10 11 11 7 9 5 C 7 3 3 4 0 6 C -3 4 -7 3 -9 5 C -11 7 -10 11 -7 13 C -11 15 -16 12 -15 6 C -13 0 -4 -2 0 0 Z"
                        />
                        <circle cx="0" cy="18" r="1.5" />
                    </g>

                    <!-- Corner Rosettes -->
                    <circle cx="60" cy="0" r="2" fill="currentColor" />
                    <circle cx="60" cy="120" r="2" fill="currentColor" />
                    <circle cx="0" cy="60" r="2" fill="currentColor" />
                    <circle cx="120" cy="60" r="2" fill="currentColor" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" :fill="`url(#${patternId})`" />
        </svg>
    </div>

    <!-- 3. RIBBON / BORDER VARIANT: Sovereign gold ornamental header trim -->
    <div
        v-else-if="variant === 'ribbon'"
        class="pointer-events-none relative h-2 w-full overflow-hidden"
        aria-hidden="true"
    >
        <svg class="size-full" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient :id="goldGradId" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="#d97706" />
                    <stop offset="50%" stop-color="#fbbf24" />
                    <stop offset="100%" stop-color="#d97706" />
                </linearGradient>
                <pattern :id="ribbonPatId" width="24" height="8" patternUnits="userSpaceOnUse">
                    <path
                        d="M 12 1 L 24 4 L 12 7 L 0 4 Z"
                        fill="none"
                        :stroke="`url(#${goldGradId})`"
                        stroke-width="1"
                    />
                    <circle cx="12" cy="4" r="1.2" :fill="`url(#${goldGradId})`" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" :fill="`url(#${goldGradId})`" opacity="0.3" />
            <rect width="100%" height="100%" :fill="`url(#${ribbonPatId})`" />
        </svg>
    </div>
</template>
