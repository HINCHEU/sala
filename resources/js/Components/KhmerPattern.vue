<script setup>
import { computed, useId } from 'vue';

defineProps({
    /**
     * 'hero': Rich royal blue & radiant gold kbach for login / guest pages
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
const patternId = computed(() => `khmer-pat-${uid}`);
const ribbonPatId = computed(() => `khmer-ribbon-${uid}`);
</script>

<template>
    <!-- 1. HERO / LOGIN VARIANT: Full vector royal backdrop with gold Kbach lattice -->
    <div v-if="variant === 'hero'" class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <svg class="size-full" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <defs>
                <!-- Deep Royal Blue & Radiant Cyan Vignette Gradient -->
                <radialGradient :id="bgGradId" cx="50%" cy="32%" r="75%">
                    <stop offset="0%" stop-color="#0084ad" stop-opacity="1" />
                    <stop offset="30%" stop-color="#005785" stop-opacity="1" />
                    <stop offset="65%" stop-color="#002b54" stop-opacity="1" />
                    <stop offset="90%" stop-color="#001630" stop-opacity="1" />
                    <stop offset="100%" stop-color="#000e1f" stop-opacity="1" />
                </radialGradient>

                <!-- Luxurious Metallic Gold Gradient for Kbach Motifs -->
                <linearGradient :id="goldGradId" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#fffbeb" />
                    <stop offset="20%" stop-color="#fef08a" />
                    <stop offset="50%" stop-color="#f59e0b" />
                    <stop offset="85%" stop-color="#d97706" />
                    <stop offset="100%" stop-color="#92400e" />
                </linearGradient>

                <!-- Seamless Khmer Kbach Repeating Pattern Unit (160x160) -->
                <pattern :id="patternId" width="160" height="160" patternUnits="userSpaceOnUse">
                    <!-- Diagonal Trellis Dual-Line Diamond Lattice -->
                    <g :stroke="`url(#${goldGradId})`" fill="none">
                        <!-- Main outer diamond grid -->
                        <path d="M 80 0 L 160 80 L 80 160 L 0 80 Z" stroke-width="1.75" stroke-opacity="0.55" />
                        <!-- Inner beaded accent diamond -->
                        <path
                            d="M 80 6 L 154 80 L 80 154 L 6 80 Z"
                            stroke-width="1"
                            stroke-dasharray="2 3.5"
                            stroke-opacity="0.4"
                        />

                        <!-- Corner intersecting diamonds -->
                        <path d="M 0 0 L 80 80 L 160 0" stroke-width="1.5" stroke-opacity="0.35" />
                        <path d="M 0 160 L 80 80 L 160 160" stroke-width="1.5" stroke-opacity="0.35" />
                    </g>

                    <!-- Trellis Junction Rosettes (Phka Chan / ផ្កាច័ន្ទ) at 4 vertices -->
                    <g :fill="`url(#${goldGradId})`" fill-opacity="0.85">
                        <!-- Top vertex (80, 0) -->
                        <circle cx="80" cy="0" r="3" />
                        <path d="M 80 -7 L 82.5 0 L 80 7 L 77.5 0 Z M 73 0 L 80 2.5 L 87 0 L 80 -2.5 Z" />
                        <circle cx="80" cy="-9" r="1" />
                        <circle cx="80" cy="9" r="1" />
                        <circle cx="71" cy="0" r="1" />
                        <circle cx="89" cy="0" r="1" />

                        <!-- Bottom vertex (80, 160) -->
                        <circle cx="80" cy="160" r="3" />
                        <path d="M 80 153 L 82.5 160 L 80 167 L 77.5 160 Z M 73 160 L 80 162.5 L 87 160 L 80 157.5 Z" />
                        <circle cx="80" cy="151" r="1" />
                        <circle cx="80" cy="169" r="1" />
                        <circle cx="71" cy="160" r="1" />
                        <circle cx="89" cy="160" r="1" />

                        <!-- Left vertex (0, 80) -->
                        <circle cx="0" cy="80" r="3" />
                        <path d="M 0 73 L 2.5 80 L 0 87 L -2.5 80 Z M -7 80 L 0 82.5 L 7 80 L 0 77.5 Z" />
                        <circle cx="0" cy="71" r="1" />
                        <circle cx="0" cy="89" r="1" />
                        <circle cx="-9" cy="80" r="1" />
                        <circle cx="9" cy="80" r="1" />

                        <!-- Right vertex (160, 80) -->
                        <circle cx="160" cy="80" r="3" />
                        <path d="M 160 73 L 162.5 80 L 160 87 L 157.5 80 Z M 153 80 L 160 82.5 L 167 80 L 160 77.5 Z" />
                        <circle cx="160" cy="71" r="1" />
                        <circle cx="160" cy="89" r="1" />
                        <circle cx="151" cy="80" r="1" />
                        <circle cx="169" cy="80" r="1" />
                    </g>

                    <!-- Central Primary Motif: Traditional Khmer Kbach Phni Tes / Lotus Flame (at 80, 80) -->
                    <g transform="translate(80, 80)" :fill="`url(#${goldGradId})`" fill-opacity="0.9">
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
                    <g transform="translate(0, 0)" :fill="`url(#${goldGradId})`" fill-opacity="0.8">
                        <path d="M 0 -18 C 2 -13 3 -10 0 -4 C -3 -10 -2 -13 0 -18 Z" />
                        <path d="M 0 18 C 2 13 3 10 0 4 C -3 10 -2 13 0 18 Z" />
                        <path d="M -18 0 C -13 2 -10 3 -4 0 C -10 -3 -13 -2 -18 0 Z" />
                        <path d="M 18 0 C 13 2 10 3 4 0 C 10 -3 13 -2 18 0 Z" />
                        <circle cx="0" cy="0" r="2.5" />
                    </g>

                    <!-- Top-Right (160, 0) -->
                    <g transform="translate(160, 0)" :fill="`url(#${goldGradId})`" fill-opacity="0.8">
                        <path d="M 0 -18 C 2 -13 3 -10 0 -4 C -3 -10 -2 -13 0 -18 Z" />
                        <path d="M 0 18 C 2 13 3 10 0 4 C -3 10 -2 13 0 18 Z" />
                        <path d="M -18 0 C -13 2 -10 3 -4 0 C -10 -3 -13 -2 -18 0 Z" />
                        <path d="M 18 0 C 13 2 10 3 4 0 C 10 -3 13 -2 18 0 Z" />
                        <circle cx="0" cy="0" r="2.5" />
                    </g>

                    <!-- Bottom-Left (0, 160) -->
                    <g transform="translate(0, 160)" :fill="`url(#${goldGradId})`" fill-opacity="0.8">
                        <path d="M 0 -18 C 2 -13 3 -10 0 -4 C -3 -10 -2 -13 0 -18 Z" />
                        <path d="M 0 18 C 2 13 3 10 0 4 C -3 10 -2 13 0 18 Z" />
                        <path d="M -18 0 C -13 2 -10 3 -4 0 C -10 -3 -13 -2 -18 0 Z" />
                        <path d="M 18 0 C 13 2 10 3 4 0 C 10 -3 13 -2 18 0 Z" />
                        <circle cx="0" cy="0" r="2.5" />
                    </g>

                    <!-- Bottom-Right (160, 160) -->
                    <g transform="translate(160, 160)" :fill="`url(#${goldGradId})`" fill-opacity="0.8">
                        <path d="M 0 -18 C 2 -13 3 -10 0 -4 C -3 -10 -2 -13 0 -18 Z" />
                        <path d="M 0 18 C 2 13 3 10 0 4 C -3 10 -2 13 0 18 Z" />
                        <path d="M -18 0 C -13 2 -10 3 -4 0 C -10 -3 -13 -2 -18 0 Z" />
                        <path d="M 18 0 C 13 2 10 3 4 0 C 10 -3 13 -2 18 0 Z" />
                        <circle cx="0" cy="0" r="2.5" />
                    </g>
                </pattern>
            </defs>

            <!-- Base Royal Blue / Cyan Gradient -->
            <rect width="100%" height="100%" :fill="`url(#${bgGradId})`" />

            <!-- Seamless Vector Khmer Kbach Trellis Layer -->
            <rect width="100%" height="100%" :fill="`url(#${patternId})`" />
        </svg>

        <!-- Ambient Lighting Glows for depth -->
        <div class="pointer-events-none absolute -top-32 -left-32 size-96 rounded-full bg-amber-400/20 blur-3xl" />
        <div class="pointer-events-none absolute -bottom-32 -right-32 size-96 rounded-full bg-cyan-400/25 blur-3xl" />
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
