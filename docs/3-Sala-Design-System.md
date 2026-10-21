# Sala — design system for the Cambodia School Management System

Sala (សាលា, "school") is the design system for the Cambodia School Management System: a calm, trustworthy interface for directors, teachers and parents, built **Khmer first, English always**. Scores and ranks are official records, so the look is quiet and exact — colour is spent on meaning (pass, absent, honour), never on decoration. It was made from the product's architecture and implementation plans, not from an existing brand, so treat every value as a first version to tune with the pilot school.

## Who it is for

- **Teachers on phones** entering scores and attendance between classes — large targets, one task per screen.
- **Office staff on desktops** managing hundreds of students — dense tables, keyboard-friendly.
- **Parents on cheap Android phones**, mostly through Telegram links — readable Khmer, no jargon.

## Voice

- Khmer is the default language; English sits beside it in `ink-muted`, never instead of it.
- Do: short sentences, polite but direct, verbs on buttons — រក្សាទុក (Save), ដាក់ស្នើ (Submit), ចាក់សោ (Lock), បោះពុម្ព (Print).
- Do: say what happened and what to do next — "ពិន្ទុលើសពិន្ទុអតិបរមា ៥០។ សូមពិនិត្យម្តងទៀត។" (Score is above the maximum of 50. Please check it.)
- Do: use the school's own words — និទ្ទេស (mention), ចំណាត់ថ្នាក់ (rank), មានច្បាប់ / ឥតច្បាប់ (with / without permission).
- Don't: English-only jargon ("submit failed: 422"), ALL CAPS, exclamation marks on errors, emoji in the interface.
- Numbers: Latin digits in the app by default; Khmer numerals (០១២៣) when the school turns them on and always available on printed documents.

## Visual foundations

- **Colour.** Pages sit on `surface`; content lives on `surface-raised` cards and tables; headers and locked areas use `surface-sunken`. `primary` (a deep blue) is the only colour for primary actions, links, selection and focus — one primary button per view. `gold` is reserved for honour: rank 1–3 badges and the Excellent mention. `success`, `warning` and `danger` carry meaning only (pass, late/absent, fail/error) and each has a `-soft` background for chips and cells.
- **Every status has a word.** Colour is never the only signal: a failed score is red **and** keeps its number visible; an absent cell shows "A"; a mention chip always shows its Khmer label. Many printed report cards are black-and-white.
- **School branding.** Each school sets its own logo and brand colour. The brand colour replaces `primary` (and a lighter tint replaces `primary-soft`) at runtime; the settings page refuses a colour that fails 4.5:1 against `on-primary` and suggests a darker shade. `gold`, `success`, `warning` and `danger` never change — a red "fail" must mean the same at every school.
- **Type.** One family for the whole interface, **Kantumruy Pro** (Google Fonts, Khmer + Latin, weights 400/500/600/700), falling back to Noto Sans Khmer. Khmer needs taller lines than Latin, so every style uses a line height of at least 1.6. `score` uses tabular numerals so grid columns line up. **Moul** is only for the Kingdom header and school name on printed documents. Details: the Khmer typography section.
- **Shape.** Gentle, not playful: inputs and grid cells `radius-sm`, buttons and cards `radius-md`, chips and rank badges `radius-full`.
- **Space.** 4px base. Phones: `space-4` gutters and card padding; desktop: `space-8` gutters, `space-6` card padding. Touch targets at least 44 × 44px on phones.
- **Depth.** Mostly flat: `border` hairlines on cards and tables, `shadow-sm` for cards on the page, `shadow-lg` only for things that float (menus, dialogs, toasts). Focus is always the 2px `focus-ring`.
- **Dark theme.** Every colour has a dark value; teachers entering scores at night get the same meanings. Printed documents are always the light theme.

## Iconography

Use **Lucide** (`lucide-vue-next`), 20px in the interface and 16px inside chips, stroke width 1.75, coloured `currentColor`. An icon always sits next to a Khmer label — never icon-only buttons except close (×) and the menu toggle, which carry an `aria-label`. Suggested set: `users` students, `user-round` teachers, `school` school, `clipboard-list` scores, `calendar-check` attendance, `trophy` ranking, `file-text` report card, `lock` locked exam, `send` publish.

## Logo

Sala has no logo of its own: the product shows **each school's uploaded logo** beside its Khmer name. Until a school uploads one, set the school name in Kantumruy Pro 700, `ink`, with no placeholder mark.

## Contents of this file

1. Brand book (this section)
2. Tokens: colours, type, spacing, radius, shadow
3. Khmer typography
4. School data patterns
5. Using Sala with Tailwind
6. Components: Button, ScoreCell, RankBadge, MentionChip, AttendanceChip, Dialog (SweetAlert2)
7. Component stylesheet (CSS)

## Tokens

### Colour

| Token | Light | Dark | Usage |
| --- | --- | --- | --- |
| `surface` | `#f6f7f9` | `#0f1318` | Page background behind every screen. |
| `surface-raised` | `#ffffff` | `#181e26` | Cards, tables, the score grid, dialogs and inputs. |
| `surface-sunken` | `#eceff3` | `#0a0d11` | Table header rows, read-only cells, locked exams, sidebar. |
| `border` | `#d5dbe3` | `#2a323d` | Hairlines: card edges, table rules, dividers. Decorative only, never the only edge of a control. |
| `border-strong` | `#7b8798` | `#6b7787` | Edges of inputs, checkboxes and grid cells (3:1 on surface-raised in both themes). |
| `ink` | `#141c26` | `#e7ecf2` | Body text, scores, names. Text on surface, surface-raised and surface-sunken. |
| `ink-muted` | `#4f5b6a` | `#9aa6b4` | Secondary text: Latin names under Khmer names, captions, help text, exempt marks. 6:1 on every surface. |
| `primary` | `#1f4fbf` | `#7ea3ff` | Default brand colour: primary buttons, links, selection, active tab, focus. Each school may replace it with its own brand colour (see README). |
| `on-primary` | `#ffffff` | `#0a1530` | Text and icons on a primary fill. |
| `primary-soft` | `#e5ecfb` | `#203156` | Selected row, hover, 'Good' mention chip, absent-with-permission chip. Text on it is primary. |
| `gold` | `#e9b949` | `#e3b44a` | Honour only: rank 1–3 badges and the 'Excellent' mention. Never for warnings or buttons. |
| `on-gold` | `#1b1405` | `#1b1405` | Text on a gold fill, the same dark ink in both themes. |
| `success` | `#16794a` | `#4fc48a` | Passed, present, submitted, saved. Text on surface-raised or success-soft. |
| `success-soft` | `#e2f3e9` | `#133322` | Background of pass, present and 'Very good' chips. |
| `warning` | `#975800` | `#f0b35a` | Late, absent mark 'A', near the pass line, 'Average' mention, unsubmitted subject. |
| `warning-soft` | `#fcefd9` | `#38270e` | Background of warning chips and absent score cells. |
| `danger` | `#be2a31` | `#ff7d82` | Failed, below pass line, absent without permission, 'Weak' mention, errors, destructive actions. |
| `danger-soft` | `#fbe4e5` | `#401a1c` | Background of error messages and danger chips; invalid score cells. |
| `focus-ring` | `{primary}` | `{primary}` | 2px outline, 2px offset, on every focused control and grid cell. |

### Type

| Family key | Stack |
| --- | --- |
| `sans` | `"Kantumruy Pro", "Noto Sans Khmer", system-ui, sans-serif` |
| `document` | `Moul, "Khmer OS Muol Light", serif` |
| `mono` | `ui-monospace, "SFMono-Regular", Menlo, Consolas, monospace` |

| Style | Family | Size / line height | Weight | Usage |
| --- | --- | --- | --- | --- |
| `display` | sans | 28px / 44px | 600 | Page titles on desktop. |
| `title` | sans | 22px / 36px | 600 | Section and card titles; page titles on phones. |
| `heading` | sans | 18px / 30px | 600 | Sub-sections, dialog titles. |
| `body` | sans | 16px / 28px | 400 | Default text, form fields. |
| `body-sm` | sans | 14px / 24px | 400 | Table cells, secondary text, Latin names under Khmer names. |
| `label` | sans | 13px / 22px | 500 | Field labels, chips, table headers. The smallest size allowed for Khmer. |
| `score` | sans | 15px / 24px | 600 | Scores, averages and ranks in grids; use tabular numerals so columns align. |
| `doc-header` | document | 18px / 34px | 400 | Kingdom header and school name on report cards, certificates and score sheets only. Never in the app interface. |

### Spacing

| Token | Value | Usage |
| --- | --- | --- |
| `space-1` | 4px | Icon-to-label gap, chip inner padding (vertical). |
| `space-2` | 8px | Gaps inside controls, between chips, grid cell padding. |
| `space-3` | 12px | Input padding, table cell padding on phones. |
| `space-4` | 16px | Card padding on phones, page side gutter on phones, form field gap. |
| `space-6` | 24px | Card padding on desktop, gap between cards. |
| `space-8` | 32px | Section spacing, page side gutter on desktop. |

### Radius

| Token | Value | Usage |
| --- | --- | --- |
| `radius-sm` | 6px | Inputs, score grid cells, checkboxes. |
| `radius-md` | 10px | Buttons, cards, menus, dialogs. |
| `radius-lg` | 16px | Large panels, the student profile header, sheets on phones. |
| `radius-full` | 9999px | Chips, rank badges, avatars. |

### Shadow

| Token | Light | Dark | Usage |
| --- | --- | --- | --- |
| `shadow-sm` | `0 1px 2px rgba(20,28,38,0.06), 0 1px 3px rgba(20,28,38,0.08)` | `0 1px 2px rgba(0,0,0,0.45)` | Cards on the page surface. |
| `shadow-lg` | `0 8px 24px rgba(20,28,38,0.14)` | `0 8px 24px rgba(0,0,0,0.6)` | Menus, dialogs, toasts — anything floating. |

The same tokens as JSON (`tokens.json`) are in the design system artifact; its generated `tokens.css` is what the app imports.

## Khmer typography

Khmer text has consonants stacked below (subscripts, ្) and vowels above and below the line, so it needs more vertical room than Latin and breaks in ways Latin does not. These rules keep it readable on cheap phones and correct on paper.

### In the interface

- **Line height at least 1.6.** Every Sala text style already follows this (`body` is 16/28). Never set `line-height: 1` or a fixed small height on anything that can hold Khmer — buttons, chips and table rows included; use padding instead.
- **Smallest Khmer size is 13px** (`label`). Below that, subscripts blur together on low-resolution screens.
- **No letter spacing, no uppercase, no italics** on Khmer. `letter-spacing` splits clusters; Khmer has no case; synthetic italics distort the script.
- **Bold sparingly.** Use weight 600 for headings and scores; Khmer at 700+ fills in at small sizes.
- **Names: Khmer first, Latin under it.** Show "សុខ ដារ៉ា" in `ink`, then "Sok Dara" in `ink-muted` `body-sm` — on lists, profiles and the score grid.
- **Truncate whole lines, never characters.** Use `line-clamp` or CSS `text-overflow: ellipsis` on a whole line; never cut a string with `substr`, which can split a cluster and show broken glyphs.
- **Sorting.** Sort Khmer names with the database's `utf8mb4_unicode_ci` collation (or `Intl.Collator('km')` in the browser), not by byte order.
- **Line breaks.** Khmer has no spaces between words; browsers break it with dictionaries. Allow wrapping (`overflow-wrap: anywhere` on narrow cells) instead of forcing one line.
- **Inputs.** Set `lang="km"` on the page so keyboards and spell-check pick Khmer; accept both Khmer (០-៩) and Latin digits in number fields and store Latin.

### Fonts

| Use | Font | Source |
| --- | --- | --- |
| Interface, all text | Kantumruy Pro 400 / 500 / 600 / 700 | Google Fonts (`family=Kantumruy+Pro:wght@400;500;600;700`) |
| Fallback | Noto Sans Khmer | Google Fonts or the device |
| Printed Kingdom header and school name | Moul (Khmer OS Muol Light where available) | Google Fonts / self-hosted |
| Printed body text | Kantumruy Pro, or Khmer OS Battambang if the school prefers the MoEYS look | Self-hosted |

### In PDFs (report cards, score sheets, certificates)

- Render with headless Chromium (Gotenberg); Khmer shaping breaks in dompdf.
- **Self-host and embed** every font file in the PDF; do not rely on Google Fonts at render time.
- Kingdom header block, centred, in `doc-header` (Moul): ព្រះរាជាណាចក្រកម្ពុជា / ជាតិ សាសនា ព្រះមហាក្សត្រ, then the school name.
- Dates in both calendars when the school asks for it; Khmer numerals when the school setting is on.
- Black ink only on printed pages: statuses keep their words ("ជាប់" passed, "ធ្លាក់" failed), because colour is lost on black-and-white printers.
- Print a test page on the school's own printer before the first real report cards — subscripts are where problems appear.

## School data patterns

The same few kinds of data appear on almost every screen — scores, ranks, mentions, attendance, exam status. Each has one look everywhere, so a teacher learns it once. Colour always comes with a word or a mark.

### Score grid cells

| State | Looks like | Tokens |
| --- | --- | --- |
| Empty, editable | Blank cell with a strong edge | `surface-raised`, edge `border-strong` |
| Entered, at or above pass line | The number, right-aligned | `ink`, `score` style |
| Below pass line | The number stays visible | text `danger` |
| Absent | "A" | text `warning` on `warning-soft` |
| Exempt | "E" | text `ink-muted` on `surface-sunken` |
| Invalid (above max, bad format) | Number kept, message under the grid | edge `danger`, background `danger-soft` |
| Saving / saved / failed to save | Small dot in the cell corner | `primary` / `success` / `danger` |
| Locked exam | Read-only, lock icon in the header | `surface-sunken` |
| Focused | 2px ring | `focus-ring` |

### Rank

- Rank prints as "5 / 42" (rank / class size) in the `score` style — the class size is part of the meaning.
- Ranks 1, 2 and 3 get a round badge: `gold` with `on-gold` text. Every other rank is plain `ink`. Shared ranks print the same number (1, 2, 2, 4).
- Students excluded from ranking (absent policy) show "—" in `ink-muted` with a tooltip saying why.

### Mentions (និទ្ទេស)

Chips with `radius-full`, `label` style, always showing the Khmer word (English in tooltips and the English UI).

| Mention | Chip |
| --- | --- |
| ល្អប្រសើរ Excellent | `on-gold` on `gold` |
| ល្អណាស់ Very good | `success` on `success-soft` |
| ល្អ Good | `primary` on `primary-soft` |
| ល្អបង្គួរ Fairly good | `ink` on `surface-sunken` |
| មធ្យម Average | `warning` on `warning-soft` |
| ខ្សោយ Weak | `danger` on `danger-soft` |

### Attendance

| Status | Mark | Chip |
| --- | --- | --- |
| Present | ✓ | `success` on `success-soft` |
| Absent with permission (មានច្បាប់) | P | `primary` on `primary-soft` — not alarming, it was approved |
| Absent without permission (ឥតច្បាប់) | A | `danger` on `danger-soft` |
| Late | L | `warning` on `warning-soft` |
| Holiday / no school | — | `ink-muted` on `surface-sunken` |

The daily sheet starts with everyone present; the teacher taps only the exceptions.

### Exam status

| Status | Chip |
| --- | --- |
| Draft | `ink-muted` on `surface-sunken` |
| Open for scores | `primary` on `primary-soft` |
| Returned to teacher | `warning` on `warning-soft` |
| Approved | `success` on `success-soft` |
| Locked | `ink` on `surface-sunken` with a lock icon |
| Published to parents | `on-primary` on `primary` |

### Statistics

Counts are always shown as total and female (សរុប / ស្រី), e.g. "42 / ស្រី 20". Charts use `primary` for the main series and `gold` for one highlighted comparison; pass and fail use `success` and `danger` with labels on the bars.

## Using Sala with Tailwind

The app is Laravel 12 + Inertia + Vue 3 in plain JavaScript with Tailwind 4. Sala's tokens become CSS variables once, and Tailwind utilities point at those variables, so light/dark and each school's brand colour switch without rebuilding CSS.

### 1. Copy the tokens

Download this system's `tokens.css` into `resources/css/tokens.css`. It defines every colour per theme under `[data-theme="light"]` / `[data-theme="dark"]`, plus spacing, radius and font variables.

### 2. Map them in `resources/css/app.css`

```css
@import "tailwindcss";
@import "./tokens.css";

@custom-variant dark (&:where([data-theme="dark"], [data-theme="dark"] *));

@theme inline {
  --color-surface: var(--surface);
  --color-surface-raised: var(--surface-raised);
  --color-surface-sunken: var(--surface-sunken);
  --color-border: var(--border);
  --color-border-strong: var(--border-strong);
  --color-ink: var(--ink);
  --color-ink-muted: var(--ink-muted);
  --color-primary: var(--primary);
  --color-on-primary: var(--on-primary);
  --color-primary-soft: var(--primary-soft);
  --color-gold: var(--gold);
  --color-on-gold: var(--on-gold);
  --color-success: var(--success);
  --color-success-soft: var(--success-soft);
  --color-warning: var(--warning);
  --color-warning-soft: var(--warning-soft);
  --color-danger: var(--danger);
  --color-danger-soft: var(--danger-soft);
}

body { @apply bg-surface text-ink font-sans; }
```

Fonts and radii need no mapping: Sala's `--font-sans`, `--font-mono`, `--radius-sm`, `--radius-md`, `--radius-lg` have the same names as Tailwind's theme variables, and `tokens.css` (unlayered, imported after Tailwind) overrides them. Do **not** re-declare them inside `@theme inline` — `--font-sans: var(--font-sans)` points at itself and breaks. For printed documents use `font-[family-name:var(--font-document)]`.

Now `bg-surface-raised`, `text-ink-muted`, `border-border-strong`, `rounded-md`, `font-sans`, `bg-primary text-on-primary` all follow Sala.

### 3. Load the font

In the Blade layout `<head>`:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
```

Self-host the same files for PDFs (Khmer typography section).

### 4. Theme and school colour at runtime

In the Blade layout, set the theme and the school's brand colour on `<html>`:

```blade
<html lang="{{ app()->getLocale() }}" data-theme="{{ $theme ?? 'light' }}"
      style="@if($school?->brand_color)--primary: {{ $school->brand_color }}; --primary-soft: {{ $school->brand_color_soft }};@endif">
```

- `brand_color_soft` is computed and saved when the director picks a colour (mix with white at about 88%).
- The settings page validates contrast: the chosen colour must reach 4.5:1 with `on-primary`; otherwise it suggests a darker shade.
- Never override `gold`, `success`, `warning` or `danger` per school.

### 5. Small Vue components to build first

Plain JavaScript, `<script setup>`, Tailwind classes from the tokens:

| Component | Props | Notes |
| --- | --- | --- |
| `SButton` | `variant` (primary, secondary, danger, ghost), `size`, `loading` | One primary per view; min height 44px on phones |
| `SChip` | `tone` (neutral, primary, success, warning, danger, gold), `icon` | `rounded-full`, `label` text style |
| `MentionChip` | `mention` (excellent … weak) | Maps to the mention table in School data patterns |
| `RankBadge` | `rank`, `size` | Gold for 1–3, prints "5 / 42" form |
| `ScoreCell` | `modelValue`, `max`, `passScore`, `state` | All score grid states; Excel-like keyboard |
| `AttendanceChip` | `status` | Present / permission / no permission / late |
| `PersonName` | `khmer`, `latin` | Khmer first, Latin under in `ink-muted` |

Example:

```vue
<script setup>
const props = defineProps({
  rank: { type: Number, default: null },
  classSize: { type: Number, required: true },
})
</script>

<template>
  <span v-if="rank === null" class="text-ink-muted">—</span>
  <span v-else class="inline-flex items-center gap-1 font-semibold tabular-nums">
    <span :class="rank <= 3 ? 'bg-gold text-on-gold' : 'text-ink'"
          class="inline-flex min-w-7 justify-center rounded-full px-2">{{ rank }}</span>
    <span class="text-ink-muted">/ {{ classSize }}</span>
  </span>
</template>
```

## Components

### Button

A labelled action; one `primary` button per view, everything else secondary, danger or ghost.

#### Variants (`SButton`)

| Prop | Values | Default |
| --- | --- | --- |
| `variant` | `primary`, `secondary`, `danger`, `ghost` | `secondary` |
| `loading` | boolean — shows a spinner, keeps the label ("កំពុងរក្សាទុក…"), disables the button | `false` |
| `disabled` | boolean | `false` |
| `type` | `button`, `submit` | `button` |

- **primary** — the main action of the screen: រក្សាទុក (Save), ដាក់ស្នើ (Submit), បោះពុម្ព (Print).
- **secondary** — every other action: បោះបង់ (Cancel), នាំចេញ Excel (Export Excel).
- **danger** — destructive or irreversible: លុបសិស្ស (Delete student), ចាក់សោ (Lock exam). Always followed by a confirm dialog that repeats the consequence.
- **ghost** — low-emphasis links inside cards and tables: មើលលម្អិត (View details).

#### Rules

- Label is a Khmer verb (plus an object when needed); no icon-only buttons except close and menu, which need `aria-label`.
- Minimum height 44px on every screen; full width on phones when it ends a form.
- Focus: 2px `focus-ring`, 2px offset. Loading keeps the label so the width does not jump.
- Disabled buttons explain why in a tooltip or helper text ("ពិន្ទុមិនទាន់គ្រប់" — scores are not complete).

### ScoreCell

One editable score in the score grid, with a distinct look for every state a teacher meets: empty, passed, below the pass line, absent, exempt, invalid, focused, saving and locked.

#### Props (`ScoreCell`)

| Prop | Type | Notes |
| --- | --- | --- |
| `modelValue` | Number, `'A'`, `'E'` or `null` | `null` = not entered; never 0 by default |
| `max` | Number | From the curriculum; values above it are invalid |
| `passScore` | Number | Below it the number turns `danger` |
| `saveState` | `idle`, `saving`, `saved`, `error` | Shown as the corner dot |
| `locked` | Boolean | Read-only after the exam is locked |

Emits `update:modelValue`, `move` (`up`, `down`, `left`, `right`) for keyboard navigation.

#### States

| State | Look |
| --- | --- |
| Empty | Blank, `border-strong` edge |
| Passed | Number in `ink`, right-aligned, tabular |
| Below pass line | Number in `danger` — the number stays readable |
| Absent | "A" in `warning` on `warning-soft` |
| Exempt | "E" in `ink-muted` on `surface-sunken` |
| Invalid | `danger-soft` fill, `danger` edge, a message under the grid naming the student and subject |
| Focused | 2px `focus-ring` |
| Saving / saved / error | Corner dot in `primary` / `success` / `danger` |
| Locked | `surface-sunken`, no edge emphasis, not focusable for editing |

#### Rules

- Keyboard like Excel: arrows move, Enter moves down, Tab moves right, typing replaces, Escape restores.
- Accept Khmer digits (០–៩) and convert to Latin; at most two decimals.
- Type "a" or "A" for absent, "e" or "E" for exempt.
- Autosave each cell 500ms after the last keystroke; never block typing while saving.
- Colour is never the only signal: invalid cells also get a message, absent and exempt use letters.
- On phones the grid becomes one student per card with large number inputs; the same states apply.

### RankBadge

A student's rank printed with the class size ("5 / 42"); ranks 1–3 sit on a gold disc, all others are plain.

#### Props (`RankBadge`)

| Prop | Type | Notes |
| --- | --- | --- |
| `rank` | Number or `null` | `null` = excluded from ranking, shows "—" |
| `of` | Number | Class or grade size |
| `scope` | `class`, `grade` | Tooltip says which ranking it is |
| `reason` | String | Tooltip for an excluded student, e.g. absent from an exam |

#### Rules

- Shared ranks print the same number and the next rank skips (1, 2, 2, 4).
- Gold is honour only: ranks 1, 2 and 3 — never for a warning or a selected state.
- Always print the class size; "5" alone means nothing on a report card.
- Tabular numerals so a column of ranks lines up.
- On printed report cards the gold disc is dropped; the number stays.

### MentionChip

The mention (និទ្ទេស) for an average, as a pill with its Khmer word; tone follows the mention, from gold for Excellent to danger for Weak.

#### Props (`MentionChip`)

| Prop | Type | Notes |
| --- | --- | --- |
| `mention` | `excellent`, `very_good`, `good`, `fairly_good`, `average`, `weak` | From the school's grading scale |
| `label` | String (optional) | Overrides the default word when the school names its bands differently |

#### Mapping

| Mention | Tone |
| --- | --- |
| ល្អប្រសើរ Excellent | gold |
| ល្អណាស់ Very good | success |
| ល្អ Good | primary |
| ល្អបង្គួរ Fairly good | neutral |
| មធ្យម Average | warning |
| ខ្សោយ Weak | danger |

#### Rules

- The word is always printed; colour only reinforces it.
- Khmer word in the Khmer UI, English word in the English UI, the other language in the tooltip.
- Band thresholds come from `grading_scales`; the chip never computes a mention itself.

### AttendanceChip

A student's attendance for one day or period, as a letter plus its Khmer word: present, absent with permission, absent without permission, late, or no school.

#### Props (`AttendanceChip`)

| Prop | Type | Notes |
| --- | --- | --- |
| `status` | `present`, `permission`, `no_permission`, `late`, `holiday` | From `attendances.status` or the school calendar |
| `compact` | Boolean | Letter only, for calendars and dense sheets; the word moves to the tooltip |

#### Mapping

| Status | Mark | Tone |
| --- | --- | --- |
| Present (មានវត្តមាន) | ✓ | success |
| Absent with permission (មានច្បាប់) | P | primary — approved, so not alarming |
| Absent without permission (ឥតច្បាប់) | A | danger |
| Late (មកយឺត) | L | warning |
| No school (ថ្ងៃឈប់សម្រាក) | — | muted |

#### Rules

- The letter is always shown, so the compact form still works without colour and on paper.
- On the daily sheet everyone starts as present; tapping a chip cycles present → late → permission → no permission.
- Only "without permission" triggers a parent notification.

### Dialog

Pop-ups and toasts built on SweetAlert2, restyled with Sala tokens so they match the rest of the app in both themes: Kantumruy Pro, Sala buttons, token colours for icons, Khmer default labels.

#### Kinds

| Kind | When | Example |
| --- | --- | --- |
| Confirm (danger) | Irreversible or destructive: lock exam, delete student, publish results | "ចាក់សោការប្រឡងខែមករា?" + consequence + ចាក់សោ / បោះបង់ |
| Input | A short required answer: change-request reason, rename | Textarea with label and validation |
| Success / error | The result of a long action the user waited for | "បានបោះពុម្ពលទ្ធផល" |
| Toast | Quick confirmation that needs no action: saved, copied, sent | Top-right on desktop, top on phones, 3s |

Do **not** use a pop-up for: validation of a form field (show it under the field), score-cell errors (message under the grid), or anything shown on every save (use a toast or the cell dot).

#### Setup in the app

```bash
npm install sweetalert2
```

`resources/js/lib/alert.js` (plain JavaScript):

```js
import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

export const dialog = Swal.mixin({
  buttonsStyling: false,          // use Sala buttons, not SweetAlert2's
  reverseButtons: true,           // cancel left, main action right
  confirmButtonText: 'យល់ព្រម',
  cancelButtonText: 'បោះបង់',
  customClass: {
    popup: 's-swal',
    title: 's-swal-title',
    htmlContainer: 's-swal-text',
    actions: 's-swal-actions',
    confirmButton: 's-btn s-btn-primary',
    denyButton: 's-btn s-btn-danger',
    cancelButton: 's-btn',
    input: 's-swal-input',
    inputLabel: 's-swal-input-label',
  },
})

export const toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  timer: 3000,
  timerProgressBar: true,
  showConfirmButton: false,
  customClass: { popup: 's-swal-toast' },
})

// Destructive confirm: focus starts on Cancel, outside click does nothing
export async function confirmDanger({ title, text, confirmText }) {
  const result = await dialog.fire({
    icon: 'warning',
    title,
    text,
    showCancelButton: true,
    focusCancel: true,
    allowOutsideClick: false,
    confirmButtonText: confirmText,
    customClass: { confirmButton: 's-btn s-btn-danger' },
  })
  return result.isConfirmed
}

export const notifySuccess = (title) => toast.fire({ icon: 'success', title })
export const notifyError = (title, text) => dialog.fire({ icon: 'error', title, text })
```

Copy the Dialog rules from `components/bundle.css` into `resources/css/dialog.css` and import it **after** `sweetalert2.min.css` (or after `app.css`), so Sala wins. The `.s-btn` rules from the Button component must be in the app CSS too.

Using it in a page:

```js
import { router } from '@inertiajs/vue3'
import { confirmDanger, notifySuccess } from '@/lib/alert'

async function lockExam(exam) {
  if (!await confirmDanger({
    title: `ចាក់សោ ${exam.name_km}?`,
    text: 'គ្រូនឹងមិនអាចកែពិន្ទុបានទៀតទេ។',
    confirmText: 'ចាក់សោ',
  })) return
  router.post(`/admin/exams/${exam.id}/lock`, {}, {
    onSuccess: () => notifySuccess('បានចាក់សោការប្រឡង'),
  })
}
```

Laravel flash messages: in the main layout, watch `usePage().props.flash` and call `notifySuccess(flash.success)` or `notifyError(...)`, so controllers only do `return back()->with('success', __('exams.locked'))`.

#### Rules

- **Title is a question or a result** in Khmer: "លុបសិស្សនេះ?" / "បានរក្សាទុក". The text says the consequence, not "Are you sure?".
- **Button labels are verbs** naming the action — ចាក់សោ, លុប, ដាក់ស្នើ — never just "OK" for destructive actions.
- **Destructive confirm** uses the danger button, `focusCancel: true`, `allowOutsideClick: false`.
- **Loading**: for a confirm that calls the server, use `showLoaderOnConfirm: true` + `preConfirm` returning the axios promise; show the server's Khmer error with `Swal.showValidationMessage`.
- **One dialog at a time**; never open a dialog from inside a toast.
- **Language**: pass Khmer or English strings from `lang/` through `laravel-vue-i18n`; never hard-code text in the component.
- **Phones**: buttons stack full width with the main action on top (handled in the CSS).
- Pin the SweetAlert2 version in `package.json` and review the changelog before upgrading.

## Component stylesheet

Plain CSS on Sala tokens (`components/bundle.css`). Put it in `resources/css/components.css`, imported after `tokens.css` and after `sweetalert2.min.css`.

```css
@import url("https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;500;600;700&display=swap");

/* Sala components — plain CSS on Sala tokens. The Vue components in the app use the same rules through Tailwind utilities. */
body { margin: 0; font-family: var(--font-sans); background: var(--surface); color: var(--ink); font-size: 16px; line-height: 28px; }
.s-row { display: flex; flex-wrap: wrap; gap: var(--space-3); align-items: center; padding: var(--space-4); }
.s-stack { display: flex; flex-direction: column; gap: var(--space-2); padding: var(--space-4); }
.s-caption { font-size: 13px; line-height: 22px; color: var(--ink-muted); }

/* Button */
.s-btn { font: inherit; font-size: 15px; line-height: 24px; font-weight: 600; min-height: 44px; padding: 0 var(--space-4); display: inline-flex; align-items: center; gap: var(--space-2); border-radius: var(--radius-md); border: 1px solid var(--border-strong); background: var(--surface-raised); color: var(--ink); cursor: pointer; }
.s-btn-primary { background: var(--primary); border-color: var(--primary); color: var(--on-primary); }
.s-btn-danger { color: var(--danger); border-color: var(--danger); }
.s-btn-ghost { background: none; border-color: transparent; color: var(--primary); }
.s-btn:disabled { cursor: not-allowed; opacity: .5; }
.s-btn:focus-visible, .s-btn.is-focus { outline: 2px solid var(--focus-ring); outline-offset: 2px; }
.s-spin { width: 14px; height: 14px; border-radius: var(--radius-full); border: 2px solid currentColor; border-right-color: transparent; animation: s-spin .8s linear infinite; }
@keyframes s-spin { to { transform: rotate(360deg); } }

/* Chip (mentions, attendance, exam status) */
.s-chip { display: inline-flex; align-items: center; gap: var(--space-1); font-size: 13px; line-height: 22px; font-weight: 500; padding: 1px 10px; border-radius: var(--radius-full); white-space: nowrap; }
.s-chip b { font-weight: 700; min-width: 12px; text-align: center; }
.s-tone-neutral { background: var(--surface-sunken); color: var(--ink); }
.s-tone-muted { background: var(--surface-sunken); color: var(--ink-muted); }
.s-tone-primary { background: var(--primary-soft); color: var(--primary); }
.s-tone-success { background: var(--success-soft); color: var(--success); }
.s-tone-warning { background: var(--warning-soft); color: var(--warning); }
.s-tone-danger { background: var(--danger-soft); color: var(--danger); }
.s-tone-gold { background: var(--gold); color: var(--on-gold); }

/* Rank badge */
.s-rank { display: inline-flex; align-items: center; gap: var(--space-1); font-size: 15px; line-height: 24px; font-weight: 600; font-variant-numeric: tabular-nums; }
.s-rank-n { min-width: 28px; height: 28px; padding: 0 var(--space-2); box-sizing: border-box; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-full); color: var(--ink); }
.s-rank-top .s-rank-n { background: var(--gold); color: var(--on-gold); }
.s-rank-of { color: var(--ink-muted); font-weight: 500; }
.s-rank-none { color: var(--ink-muted); }

/* Score grid */
.s-grid { border-collapse: separate; border-spacing: 0; background: var(--surface-raised); border: 1px solid var(--border); border-radius: var(--radius-md); overflow: hidden; }
.s-grid th { background: var(--surface-sunken); font-size: 13px; line-height: 20px; font-weight: 500; text-align: right; padding: var(--space-2) var(--space-3); color: var(--ink); border-bottom: 1px solid var(--border); }
.s-grid th:first-child { text-align: left; }
.s-grid th small { display: block; color: var(--ink-muted); font-size: 12px; font-weight: 400; }
.s-grid td { padding: var(--space-1) var(--space-2); border-bottom: 1px solid var(--border); }
.s-grid tr:last-child td { border-bottom: 0; }
.s-name { padding: var(--space-1) var(--space-3) !important; font-size: 14px; line-height: 22px; min-width: 150px; }
.s-name small { display: block; color: var(--ink-muted); font-size: 13px; line-height: 20px; }
.s-cell { position: relative; width: 72px; height: 40px; box-sizing: border-box; display: flex; align-items: center; justify-content: flex-end; padding: 0 10px; border: 1px solid var(--border-strong); border-radius: var(--radius-sm); background: var(--surface-raised); color: var(--ink); font-size: 15px; font-weight: 600; font-variant-numeric: tabular-nums; }
.s-cell-fail { color: var(--danger); }
.s-cell-absent { justify-content: center; background: var(--warning-soft); border-color: var(--warning); color: var(--warning); }
.s-cell-exempt { justify-content: center; background: var(--surface-sunken); color: var(--ink-muted); }
.s-cell-invalid { background: var(--danger-soft); border-color: var(--danger); color: var(--danger); }
.s-cell-focus { outline: 2px solid var(--focus-ring); outline-offset: 2px; }
.s-cell-locked { background: var(--surface-sunken); border-color: var(--border); }
.s-dot { position: absolute; top: 5px; left: 5px; width: 6px; height: 6px; border-radius: var(--radius-full); }
.s-dot-saving { background: var(--primary); }
.s-dot-saved { background: var(--success); }
.s-dot-error { background: var(--danger); }
.s-msg { display: flex; gap: var(--space-2); align-items: center; font-size: 14px; line-height: 24px; color: var(--danger); margin-top: var(--space-2); }

/* Dialog — SweetAlert2 restyled with Sala tokens.
   Use with buttonsStyling:false and the customClass map in components/Dialog/README.md. */
.swal2-container.swal2-backdrop-show { background: rgba(10, 13, 17, .48); }
.swal2-popup.s-swal { width: min(28rem, calc(100vw - 32px)); padding: 0 0 var(--space-6); border: 1px solid var(--border); border-radius: var(--radius-lg); background: var(--surface-raised); color: var(--ink); font-family: var(--font-sans); box-shadow: var(--shadow-lg); }
.swal2-popup.s-swal .s-swal-title { margin: 0; padding: var(--space-4) var(--space-6) 0; font-size: 20px; line-height: 32px; font-weight: 600; color: var(--ink); }
.swal2-popup.s-swal .s-swal-text { margin: var(--space-2) 0 0; padding: 0 var(--space-6); font-size: 16px; line-height: 28px; color: var(--ink-muted); text-align: center; }
.swal2-popup.s-swal .s-swal-actions { width: 100%; box-sizing: border-box; margin: var(--space-6) 0 0; padding: 0 var(--space-6); gap: var(--space-2); justify-content: flex-end; }
.swal2-popup.s-swal .s-swal-actions .s-btn { margin: 0; }
.swal2-popup.s-swal .s-btn:focus { box-shadow: none; }
.swal2-popup.s-swal .s-btn:focus-visible { outline: 2px solid var(--focus-ring); outline-offset: 2px; }
.swal2-popup.s-swal .swal2-loader { border-color: var(--primary) transparent var(--primary) transparent; }

/* icons: smaller, token colours, dark-safe */
.swal2-popup.s-swal .swal2-icon { font-size: 11px; margin: var(--space-6) auto 0; }
.swal2-popup.s-swal .swal2-icon.swal2-warning { border-color: var(--warning); color: var(--warning); }
.swal2-popup.s-swal .swal2-icon.swal2-question,
.swal2-popup.s-swal .swal2-icon.swal2-info { border-color: var(--primary); color: var(--primary); }
.swal2-popup.s-swal .swal2-icon.swal2-error { border-color: var(--danger); color: var(--danger); }
.swal2-popup.s-swal .swal2-icon.swal2-error [class^="swal2-x-mark-line"] { background-color: var(--danger); }
.swal2-popup.s-swal .swal2-icon.swal2-success { border-color: var(--success); color: var(--success); }
.swal2-popup.s-swal .swal2-icon.swal2-success [class^="swal2-success-line"] { background-color: var(--success); }
.swal2-popup.s-swal .swal2-icon.swal2-success .swal2-success-ring { border-color: var(--success); opacity: .3; }
.swal2-popup.s-swal .swal2-icon.swal2-success [class^="swal2-success-circular-line"],
.swal2-popup.s-swal .swal2-icon.swal2-success .swal2-success-fix { background-color: var(--surface-raised); }

/* inputs and validation */
.swal2-popup.s-swal .s-swal-input { box-sizing: border-box; width: calc(100% - 2 * var(--space-6)); margin: var(--space-4) var(--space-6) 0; padding: var(--space-2) var(--space-3); font: inherit; font-size: 16px; line-height: 28px; color: var(--ink); background: var(--surface-raised); border: 1px solid var(--border-strong); border-radius: var(--radius-sm); box-shadow: none; }
.swal2-popup.s-swal .s-swal-input:focus { outline: 2px solid var(--focus-ring); outline-offset: 2px; border-color: var(--border-strong); box-shadow: none; }
.swal2-popup.s-swal textarea.s-swal-input { height: auto; min-height: 96px; }
.swal2-popup.s-swal .s-swal-input-label { margin: var(--space-4) var(--space-6) 0; font-size: 13px; line-height: 22px; font-weight: 500; color: var(--ink); justify-content: flex-start; }
.swal2-popup.s-swal .swal2-validation-message { margin: var(--space-2) var(--space-6) 0; padding: var(--space-2) var(--space-3); border-radius: var(--radius-sm); background: var(--danger-soft); color: var(--danger); font-size: 14px; line-height: 24px; justify-content: flex-start; }
.swal2-popup.s-swal .swal2-validation-message::before { background-color: var(--danger); color: var(--surface-raised); }

/* toast */
.swal2-popup.swal2-toast.s-swal-toast { padding: var(--space-2) var(--space-4); border: 1px solid var(--border); border-radius: var(--radius-md); background: var(--surface-raised); color: var(--ink); font-family: var(--font-sans); box-shadow: var(--shadow-lg); }
.swal2-popup.swal2-toast.s-swal-toast .swal2-title { font-size: 15px; line-height: 24px; font-weight: 500; color: var(--ink); margin: 0 var(--space-2); }
.swal2-popup.swal2-toast.s-swal-toast .swal2-timer-progress-bar { background: var(--primary); }
.swal2-popup.swal2-toast.s-swal-toast .swal2-icon.swal2-success { border-color: var(--success); color: var(--success); }
.swal2-popup.swal2-toast.s-swal-toast .swal2-icon.swal2-success [class^="swal2-success-line"] { background-color: var(--success); }
.swal2-popup.swal2-toast.s-swal-toast .swal2-icon.swal2-success [class^="swal2-success-circular-line"],
.swal2-popup.swal2-toast.s-swal-toast .swal2-icon.swal2-success .swal2-success-fix { background-color: var(--surface-raised); }
.swal2-popup.swal2-toast.s-swal-toast .swal2-icon.swal2-error { border-color: var(--danger); color: var(--danger); }
.swal2-popup.swal2-toast.s-swal-toast .swal2-icon.swal2-error [class^="swal2-x-mark-line"] { background-color: var(--danger); }

/* phones: full-width buttons, primary on top */
@media (max-width: 480px) {
  .swal2-popup.s-swal .s-swal-actions { flex-direction: column-reverse; }
  .swal2-popup.s-swal .s-swal-actions .s-btn { width: 100%; justify-content: center; }
}
```
