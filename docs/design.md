---
name: MoEYS Sovereign Standard
colors:
  surface: '#faf8ff'
  surface-dim: '#d2d9f4'
  surface-bright: '#faf8ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f3ff'
  surface-container: '#eaedff'
  surface-container-high: '#e2e7ff'
  surface-container-highest: '#dae2fd'
  on-surface: '#131b2e'
  on-surface-variant: '#43474e'
  inverse-surface: '#283044'
  inverse-on-surface: '#eef0ff'
  outline: '#74777f'
  outline-variant: '#c4c6cf'
  surface-tint: '#455f88'
  primary: '#002045'
  on-primary: '#ffffff'
  primary-container: '#1a365d'
  on-primary-container: '#86a0cd'
  inverse-primary: '#adc7f7'
  secondary: '#904d00'
  on-secondary: '#ffffff'
  secondary-container: '#fe932c'
  on-secondary-container: '#663500'
  tertiary: '#002618'
  on-tertiary: '#ffffff'
  tertiary-container: '#003e29'
  on-tertiary-container: '#39b282'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d6e3ff'
  primary-fixed-dim: '#adc7f7'
  on-primary-fixed: '#001b3c'
  on-primary-fixed-variant: '#2d476f'
  secondary-fixed: '#ffdcc3'
  secondary-fixed-dim: '#ffb77d'
  on-secondary-fixed: '#2f1500'
  on-secondary-fixed-variant: '#6e3900'
  tertiary-fixed: '#85f8c4'
  tertiary-fixed-dim: '#68dba9'
  on-tertiary-fixed: '#002114'
  on-tertiary-fixed-variant: '#005137'
  background: '#faf8ff'
  on-background: '#131b2e'
  surface-variant: '#dae2fd'
typography:
  headline-xl:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-xl-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: '0'
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 18px
  label-numeric:
    fontFamily: JetBrains Mono
    fontSize: 13px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: -0.01em
  label-badge:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 14px
    letterSpacing: 0.04em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  gutter: 1rem
  gutter-desktop: 1.5rem
  margin: 1rem
  margin-tablet: 1.5rem
  margin-desktop: 2rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 0.75rem
  space-lg: 1.25rem
  space-xl: 2rem
---

## Brand & Style

This design system establishes an authoritative, institutional aesthetic for primary, secondary, and tertiary educational administration in Cambodia. It merges rigorous ministerial compliance (MoEYS EMIS standards) with modern enterprise ergonomics. 

The aesthetic sits at the intersection of **Corporate Modern** and **High-Density Utility**:
- **Authoritative & Trustworthy:** Built upon deep, sovereign indigos and architectural lines that project governmental discipline, legitimacy, and stability.
- **Dignified Distinction:** Accentuated with warm temple gold to elevate honors, official seals, and critical actions above everyday data entry.
- **Data-First Utility:** Engineered for administrative staff, school directors, and teachers working across heavy spreadsheets, bilingual reports, and rapid-entry attendance registries. The interface minimizes fatigue by avoiding arbitrary ornamentation, relying instead on surgical grid lines, disciplined padding, and purposeful state indicators.

## Colors

The palette operates primarily in a disciplined light mode to ensure compatibility with classroom sunlight, legacy office monitors, and printed ministerial transcripts.

- **Primary (`#1A365D`):** Royal Indigo serves as the cornerstone for institutional headers, primary action buttons, focused input outlines, and primary navigation sidebars.
- **Secondary (`#D97706`):** Khmer Temple Gold handles official honors, merit badges, academic year switchers, and active semester filters.
- **Tertiary (`#059669`):** Jade Green designates validated MoEYS records, passing scores (≥50%), and approved attendance.
- **Alert System:** Crimson (`#DC2626`) signals urgent interventions, failing marks, and unauthorized absences; Informational Cyan (`#0284C7`) denotes homeroom statuses and class shift tags.
- **Surfaces & Borders:** The ground plane relies on warm slate tinting (`#F8FAFC` base, `#FFFFFF` elevated cards, `#F1F5F9` table headers) bound by structural, low-contrast borders (`#E2E8F0`). Dark neutral text (`#0F172A`) guarantees WCAG AAA legibility against white cards.

## Typography

The typography architecture uses a strict tri-tier structure to resolve bilingual English/Khmer scripts, administrative scanning, and dense mathematical notation:

1. **System & Structural Latin (`Plus Jakarta Sans` & `Inter`):** Renders clean navigation, high-contrast operational tables, and UI controls.
2. **Khmer Script Integration:** When serving Khmer strings, the font stack systematically cascades to `Kantumruy Pro` (for clean, legible UI labels/subheadings) and `Moul` (strictly for institutional headers, ministerial seals, and official state documents like "ក្រសួងអប់រំ យុវជន និងកីឡា"). For large body runs, `Siemreap` or `Khmer OS Battambang` is prioritized with a line-height multiplier of `1.35x` relative to English to ensure diacritics do not clip.
3. **Tabular Numerics (`JetBrains Mono`):** Applied across all decimal grade inputs, classroom capacity ratios, national student ID codes, rankings, and currency calculations (KHR / USD) to maintain absolute vertical alignment across grid rows.

## Layout & Spacing

This design system uses a dense 12-column responsive fluid grid designed for rapid scanning and data manipulation:

- **Desktop (≥1280px):** 12-column layout with `gutter-desktop` (24px) and outer canvas margins of `margin-desktop` (32px). Maximizes lateral space for deep data tables, side-by-side student transcripts, and persistent context navigation.
- **Tablet (768px - 1279px):** 8-column layout with 24px gutters and `margin-tablet` (24px). Primary action toolbars consolidate into overflow popovers; secondary dashboard panels collapse into stack order.
- **Mobile (<768px):** 4-column layout with `gutter` (16px) and `margin` (16px). Complex tabular grids transition automatically into swipeable row-cards or pinned-column modes.

**Spatial Rhythm:** Component internals use an 8px base rhythm (`space-sm` = 8px, `space-md` = 12px, `space-lg` = 20px). Compact data entry elements leverage the micro token `space-xs` (4px) to preserve data density without sacrificing target clickability.

## Elevation & Depth

Visual hierarchy uses architectural tonal layering paired with crisp outlines, avoiding exaggerated floating effects in favor of professional restraint:

- **Level 0 (Canvas Base):** Ground color `#F8FAFC`. Unbordered, purely contextual.
- **Level 1 (Cards & Data Panels):** Solid `#FFFFFF` fill bounded by a crisp 1px `#E2E8F0` border. Shadow: `0 1px 3px 0 rgba(15, 23, 42, 0.05), 0 1px 2px -1px rgba(15, 23, 42, 0.05)`.
- **Level 2 (Hovered Records & Sticky Headers):** Elevated data rows, active filters, and fixed spreadsheet headers. Shadow: `0 4px 6px -1px rgba(15, 23, 42, 0.07), 0 2px 4px -2px rgba(15, 23, 42, 0.05)`.
- **Level 3 (Modals, Validation Popovers, Dropdowns):** Floating interactive contexts. Framed with a 1px `#CBD5E1` border and deep ambient blur: `0 10px 15px -3px rgba(15, 23, 42, 0.08), 0 4px 6px -4px rgba(15, 23, 42, 0.04)`.

## Shapes

The system implements **Soft** corner geometry (`roundedness: 1`), conveying structured institutional precision:
- **Default (4px / 0.25rem):** Applied to tabular input cells, data badges, action buttons, table row highlights, and status pills.
- **Large (8px / 0.5rem):** Applied to metric summary containers, KPI cards, and modal dialogs.
- **Pills (9999px):** Strictly reserved for high-visibility operational badges (e.g., status indicators, class shifts, and academic year pills) to distinguish them immediately from structural data frames.

## Components

### Grade Entry Spreadsheet Cells
- Built with high-density grid lines (1px solid `#E2E8F0`). 
- Focus state features a solid 2px `#1A365D` inner ring with zero outer offset.
- Real-time cell state indicators: An unsaved change features an amber top-right triangle; validation errors render a crimson background (`#FEF2F2`) with red text (`#DC2626`); auto-saved values display a momentary faint green outline (`#059669`).
- Numeric inputs use `JetBrains Mono` aligned flush right.

### Metric KPI Cards (MoEYS Dual-Count Standard)
- Metric blocks display primary total values alongside mandatory bilingual female splits (e.g., `Total: 1,240 | F: 620` / `សរុប: ១,២៤០ | ស្រី: ៦២០`).
- Female metric indicators are distinguished with an understated muted indigo chip (`bg: #EEF2F6`, `text: #1A365D`).
- Trend indicators (up/down delta) sit at the bottom-right of the card using tabular font styling.

### Academic Context Switcher Bar
- Top-level persistent context bar housing:
  - School Tenant Selector dropdown (Bilingual: e.g., Preah Sisowath High School / វិទ្យាល័យព្រះស៊ីសុវត្ថិ).
  - Academic Year pill selector (`2025–2026`) highlighted in deep navy with gold border styling.
  - Shift Badges: Pill shapes distinguishing Morning (`វេនព្រឹក` in cyan-tinted badges) and Afternoon (`វេនរសៀល` in amber-tinted badges).

### Bilingual Status Pills
- Pill-shaped badges combining Khmer and English statuses:
  - **Draft (សេចក្តីព្រាង):** Slate background (`#F1F5F9`), slate text (`#475569`).
  - **Submitted (បានដាក់ស្នើ):** Indigo tint (`#E0E7FF`), navy text (`#1A365D`).
  - **Approved (បានអនុម័ត):** Emerald tint (`#D1FAE5`), jade text (`#065F46`).
  - **Locked (ចាក់សោ):** Amber tint (`#FEF3C7`), dark amber text (`#92400E`).

### Buttons
- **Primary:** Background `#1A365D`, text `#FFFFFF`, 4px border radius. On hover, darken to `#0F172A`.
- **Secondary / Export:** Border 1px `#CBD5E1`, background `#FFFFFF`, text `#1A365D`.
- **Honorary / Validate Action:** Background `#D97706`, text `#FFFFFF`.
- Sizing defaults to a compact 32px height for data toolbar operations and 40px height for primary layout actions.