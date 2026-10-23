<script setup>
import { computed, useId } from 'vue';

defineProps({
    /**
     * 'hero': Deep royal midnight blue with subtle antique gold kbach for login / guest pages
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
</script>

<template>
    <!-- 1. HERO / LOGIN VARIANT: Deep midnight royal backdrop with soft antique gold Kbach lattice -->
    <div v-if="variant === 'hero'" class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <svg class="size-full" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <defs>
                <!-- Deep Royal Midnight Navy & Dark Teal Vignette Gradient -->
                <radialGradient :id="bgGradId" cx="50%" cy="38%" r="70%">
                    <stop offset="0%" stop-color="#0a3c54" stop-opacity="1" />
                    <stop offset="35%" stop-color="#052438" stop-opacity="1" />
                    <stop offset="70%" stop-color="#021421" stop-opacity="1" />
                    <stop offset="100%" stop-color="#010a12" stop-opacity="1" />
                </radialGradient>

                <!-- Soft Vignette Shadow to mute brightness and create executive depth -->
                <radialGradient :id="vignetteGradId" cx="50%" cy="45%" r="65%">
                    <stop offset="0%" stop-color="#000000" stop-opacity="0.1" />
                    <stop offset="55%" stop-color="#000000" stop-opacity="0.45" />
                    <stop offset="100%" stop-color="#000000" stop-opacity="0.82" />
                </radialGradient>

                <!-- Refined Antique Champagne Gold Gradient for Kbach Motifs -->
                <linearGradient :id="goldGradId" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#e8d5a8" />
                    <stop offset="35%" stop-color="#c9aa6c" />
                    <stop offset="75%" stop-color="#a68444" />
                    <stop offset="100%" stop-color="#735520" />
                </linearGradient>

                <!-- Seamless Khmer Kbach Repeating Pattern Unit (scaled to refined 96x96 tile) -->
                <pattern :id="patternId" width="96" height="96" viewBox="0 0 160 160" patternUnits="userSpaceOnUse">
                    <!-- Diagonal Trellis Dual-Line Diamond Lattice -->
                    <g :stroke="`url(#${goldGradId})`" fill="none">
                        <!-- Main outer diamond grid -->
                        <path d="M 80 0 L 160 80 L 80 160 L 0 80 Z" stroke-width="1.5" stroke-opacity="0.32" />
                        <!-- Inner beaded accent diamond -->
                        <path
                            d="M 80 6 L 154 80 L 80 154 L 6 80 Z"
                            stroke-width="0.85"
                            stroke-dasharray="2 3.5"
                            stroke-opacity="0.22"
                        />

                        <!-- Corner intersecting diamonds -->
                        <path d="M 0 0 L 80 80 L 160 0" stroke-width="1.25" stroke-opacity="0.2" />
                        <path d="M 0 160 L 80 80 L 160 160" stroke-width="1.25" stroke-opacity="0.2" />
                    </g>

                    <!-- Trellis Junction Rosettes (Phka Chan / ផ្កាច័ន្ទ) at 4 vertices -->
                    <g :fill="`url(#${goldGradId})`" fill-opacity="0.45">
                        <!-- Top vertex (80, 0) -->
                        <circle cx="80" cy="0" r="2.5" />
                        <path d="M 80 -6 L 82 0 L 80 6 L 78 0 Z M 74 0 L 80 2 L 86 0 L 80 -2 Z" />
                        <circle cx="80" cy="-8" r="0.8" />
                        <circle cx="80" cy="8" r="0.8" />
                        <circle cx="72" cy="0" r="0.8" />
                        <circle cx="88" cy="0" r="0.8" />

                        <!-- Bottom vertex (80, 160) -->
                        <circle cx="80" cy="160" r="2.5" />
                        <path d="M 80 154 L 82 160 L 80 166 L 78 160 Z M 74 160 L 80 162 L 86 160 L 80 158 Z" />
                        <circle cx="80" cy="152" r="0.8" />
                        <circle cx="80" cy="168" r="0.8" />
                        <circle cx="72" cy="160" r="0.8" />
                        <circle cx="88" cy="160" r="0.8" />

                        <!-- Left vertex (0, 80) -->
                        <circle cx="0" cy="80" r="2.5" />
                        <path d="M 0 74 L 2 80 L 0 86 L -2 80 Z M -6 80 L 0 82 L 6 80 L 0 78 Z" />
                        <circle cx="0" cy="72" r="0.8" />
                        <circle cx="0" cy="88" r="0.8" />
                        <circle cx="-8" cy="80" r="0.8" />
                        <circle cx="8" cy="80" r="0.8" />

                        <!-- Right vertex (160, 80) -->
                        <circle cx="160" cy="80" r="2.5" />
                        <path d="M 160 74 L 162 80 L 160 86 L 158 80 Z M 154 80 L 160 82 L 166 80 L 160 78 Z" />
                        <circle cx="160" cy="72" r="0.8" />
                        <circle cx="160" cy="88" r="0.8" />
                        <circle cx="152" cy="80" r="0.8" />
                        <circle cx="168" cy="80" r="0.8" />
                    </g>

                    <!-- Central Primary Motif: Traditional Khmer Kbach Phni Tes / Lotus Flame (at 80, 80) -->
                    <g transform="translate(80, 80)" :fill="`url(#${goldGradId})`" fill-opacity="0.48">
                        <!-- Top Pointed Flame Tip & Spire -->
                        <circle cx="0" cy="-35" r="1.5" />
                        <path
                            d="M 0 -33 C 1.5 -28 3.5 -24 3 -20 C 2 -16 0 -13 0 -10 C 0 -13 -2 -16 -3 -20 C -3.5 -24 -1.5 -28 0 -33 Z"
                        />

                        <!-- Tier 1: Inner Layered Flame -->
                        <path
                            d="M 0 -22 C 3.5 -18 7 -13 5.5 -8 C 4 -4 1 -3 0 1 C -1 -3 -4 -4 -5.5 -8 C -7 -13 -3.5 -18 0 -22 Z"
                        />

                        <!-- Tier 2: Upper Sweeping Tendrils (Kbach Kranok Wings) -->
                        <path
                            d="M 0 -10 C 4 -12 12 -12 15 -6 C 17 -1 13 4 8 2 C 11 0 12 -3 10 -5 C 7 -8 3 -7 0 -4 C -3 -7 -7 -8 -10 -5 C -12 -3 -11 0 -8 2 C -13 4 -17 -1 -15 -6 C -12 -12 -4 -12 0 -10 Z"
                        />

                        <!-- Tier 3: Main Majestic Outward Wings (Curling Lotus Petals) -->
                        <path
                            d="M 0 -1 C 6 -3 18 -1 21 8 C 23 16 16 20 10 17 C 14 14 15 9 12 6 C 9 3 4 5 0 8 C -4 5 -9 3 -12 6 C -15 9 -14 14 -10 17 C -16 20 -23 16 -21 8 C -18 -1 -6 -3 0 -1 Z"
                        />

                        <!-- Tier 4: Lower Lateral Curves -->
                        <path
                            d="M 0 9 C 5 7 14 9 16 16 C 17 21 12 24 8 22 C 11 20 11 16 9 14 C 6 12 3 13 0 16 C -3 13 -6 12 -9 14 C -11 16 -11 20 -8 22 C -12 24 -17 21 -16 16 C -14 9 -5 7 0 9 Z"
                        />

                        <!-- Base Lotus Pedestal / Calyx -->
                        <path d="M -8 22 C -4 26 4 26 8 22 C 6 27 -6 27 -8 22 Z" />
                        <circle cx="0" cy="27" r="1.5" />

                        <!-- Delicate Accent Beads / Jewelry Dots -->
                        <circle cx="-19" cy="8" r="1.2" />
                        <circle cx="19" cy="8" r="1.2" />
                        <circle cx="-14" cy="18" r="1" />
                        <circle cx="14" cy="18" r="1" />
                    </g>

                    <!-- Secondary Corner Triad Flames (at 0,0 / 160,0 / 0,160 / 160,160) -->
                    <!-- Top-Left (0, 0) -->
                    <g transform="translate(0, 0)" :fill="`url(#${goldGradId})`" fill-opacity="0.38">
                        <path d="M 0 -18 C 2 -13 3 -10 0 -4 C -3 -10 -2 -13 0 -18 Z" />
                        <path d="M 0 18 C 2 13 3 10 0 4 C -3 10 -2 13 0 18 Z" />
                        <path d="M -18 0 C -13 2 -10 3 -4 0 C -10 -3 -13 -2 -18 0 Z" />
                        <path d="M 18 0 C 13 2 10 3 4 0 C 10 -3 13 -2 18 0 Z" />
                        <circle cx="0" cy="0" r="2.5" />
                    </g>

                    <!-- Top-Right (160, 0) -->
                    <g transform="translate(160, 0)" :fill="`url(#${goldGradId})`" fill-opacity="0.38">
                        <path d="M 0 -18 C 2 -13 3 -10 0 -4 C -3 -10 -2 -13 0 -18 Z" />
                        <path d="M 0 18 C 2 13 3 10 0 4 C -3 10 -2 13 0 18 Z" />
                        <path d="M -18 0 C -13 2 -10 3 -4 0 C -10 -3 -13 -2 -18 0 Z" />
                        <path d="M 18 0 C 13 2 10 3 4 0 C 10 -3 13 -2 18 0 Z" />
                        <circle cx="0" cy="0" r="2.5" />
                    </g>

                    <!-- Bottom-Left (0, 160) -->
                    <g transform="translate(0, 160)" :fill="`url(#${goldGradId})`" fill-opacity="0.38">
                        <path d="M 0 -18 C 2 -13 3 -10 0 -4 C -3 -10 -2 -13 0 -18 Z" />
                        <path d="M 0 18 C 2 13 3 10 0 4 C -3 10 -2 13 0 18 Z" />
                        <path d="M -18 0 C -13 2 -10 3 -4 0 C -10 -3 -13 -2 -18 0 Z" />
                        <path d="M 18 0 C 13 2 10 3 4 0 C 10 -3 13 -2 18 0 Z" />
                        <circle cx="0" cy="0" r="2.5" />
                    </g>

                    <!-- Bottom-Right (160, 160) -->
                    <g transform="translate(160, 160)" :fill="`url(#${goldGradId})`" fill-opacity="0.38">
                        <path d="M 0 -18 C 2 -13 3 -10 0 -4 C -3 -10 -2 -13 0 -18 Z" />
                        <path d="M 0 18 C 2 13 3 10 0 4 C -3 10 -2 13 0 18 Z" />
                        <path d="M -18 0 C -13 2 -10 3 -4 0 C -10 -3 -13 -2 -18 0 Z" />
                        <path d="M 18 0 C 13 2 10 3 4 0 C 10 -3 13 -2 18 0 Z" />
                        <circle cx="0" cy="0" r="2.5" />
                    </g>
                </pattern>
            </defs>

            <!-- 1. Deep Midnight Base Gradient -->
            <rect width="100%" height="100%" :fill="`url(#${bgGradId})`" />

            <!-- 2. Seamless Vector Khmer Kbach Trellis Layer -->
            <rect width="100%" height="100%" :fill="`url(#${patternId})`" />

            <!-- 3. Soft Dark Vignette Layer on top to mute excessive brightness -->
            <rect width="100%" height="100%" :fill="`url(#${vignetteGradId})`" />
        </svg>
    </div>

    <!-- 2. SUBTLE APP WATERMARK VARIANT: Lightweight currentColor vector watermark for Admin & Teacher layouts -->
    <div
        v-else-if="variant === 'subtle'"
        class="pointer-events-none absolute inset-0 overflow-hidden text-primary transition-opacity duration-300"
        :style="{ opacity: opacity ?? '0.035' }"
        aria-hidden="true"
    >
        <svg class="size-full" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern :id="patternId" width="96" height="96" viewBox="0 0 160 160" patternUnits="userSpaceOnUse">
                    <!-- Diamond Grid -->
                    <path
                        d="M 80 0 L 160 80 L 80 160 L 0 80 Z"
                        stroke="currentColor"
                        stroke-width="1.2"
                        stroke-opacity="0.6"
                        fill="none"
                    />

                    <!-- Center Kbach Motif -->
                    <g transform="translate(80, 80) scale(0.85)" fill="currentColor" fill-opacity="0.8">
                        <path
                            d="M 0 -33 C 1.5 -28 3.5 -24 3 -20 C 2 -16 0 -13 0 -10 C 0 -13 -2 -16 -3 -20 C -3.5 -24 -1.5 -28 0 -33 Z"
                        />
                        <path
                            d="M 0 -22 C 3.5 -18 7 -13 5.5 -8 C 4 -4 1 -3 0 1 C -1 -3 -4 -4 -5.5 -8 C -7 -13 -3.5 -18 0 -22 Z"
                        />
                        <path
                            d="M 0 -10 C 4 -12 12 -12 15 -6 C 17 -1 13 4 8 2 C 11 0 12 -3 10 -5 C 7 -8 3 -7 0 -4 C -3 -7 -7 -8 -10 -5 C -12 -3 -11 0 -8 2 C -13 4 -17 -1 -15 -6 C -12 -12 -4 -12 0 -10 Z"
                        />
                        <path
                            d="M 0 -1 C 6 -3 18 -1 21 8 C 23 16 16 20 10 17 C 14 14 15 9 12 6 C 9 3 4 5 0 8 C -4 5 -9 3 -12 6 C -15 9 -14 14 -10 17 C -16 20 -23 16 -21 8 C -18 -1 -6 -3 0 -1 Z"
                        />
                        <circle cx="0" cy="24" r="1.5" />
                    </g>

                    <!-- Corner Rosettes -->
                    <circle cx="80" cy="0" r="2" fill="currentColor" fill-opacity="0.7" />
                    <circle cx="80" cy="160" r="2" fill="currentColor" fill-opacity="0.7" />
                    <circle cx="0" cy="80" r="2" fill="currentColor" fill-opacity="0.7" />
                    <circle cx="160" cy="80" r="2" fill="currentColor" fill-opacity="0.7" />
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
                    <stop offset="0%" stop-color="#b45309" />
                    <stop offset="50%" stop-color="#d97706" />
                    <stop offset="100%" stop-color="#b45309" />
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
            <rect width="100%" height="100%" :fill="`url(#${goldGradId})`" opacity="0.25" />
            <rect width="100%" height="100%" :fill="`url(#${ribbonPatId})`" />
        </svg>
    </div>
</template>
