# Sentinel — Design System

> **Version 2.0** · Self-Hosted Security Monitoring Dashboard  
> Stack: PHP 8.1 / PostgreSQL / Redis · Dark-mode only · No light theme

---

## Table of Contents

1. [Design Philosophy](#1-design-philosophy)
2. [Brand Identity](#2-brand-identity)
3. [Color System](#3-color-system)
4. [Typography](#4-typography)
5. [Spacing & Layout](#5-spacing--layout)
6. [Elevation & Depth](#6-elevation--depth)
7. [Shape & Radii](#7-shape--radii)
8. [Iconography](#8-iconography)
9. [Motion & Animation](#9-motion--animation)
10. [Components](#10-components)
11. [Page Layouts](#11-page-layouts)
12. [Data Visualization](#12-data-visualization)
13. [Interaction States](#13-interaction-states)
14. [Accessibility](#14-accessibility)
15. [Design Tokens (Full Reference)](#15-design-tokens-full-reference)

---

## 1. Design Philosophy

Sentinel is **mission-critical security infrastructure**. Its interface must project four qualities simultaneously:

| Quality | What it means in practice |
| --- | --- |
| **Authority** | Looks like enterprise tooling an analyst would trust under pressure |
| **Precision** | Every number is readable, every status is unambiguous at a glance |
| **Calm vigilance** | High information density without cognitive overwhelm |
| **Responsiveness** | Interactions feel immediate; latency is never masked, always surfaced |

### Core Design Rules

- **Dark by default, always.** There is no light mode. The dark theme *is* the product.
- **Indigo is the north star.** Every interactive element, focus state, and hover glow traces back to `#6366f1`. New UI additions must follow this rule without exception.
- **Monospace for all data.** Risk scores, IP addresses, timestamps, API keys, and numeric KPIs always use JetBrains Mono. Never Inter for numbers in data-dense contexts.
- **Glass, not flat.** Every card uses `backdrop-filter: blur()`. Flat solid surfaces feel wrong and should be treated as a bug.
- **Zero decorative imagery.** No illustrations, stock photography, or hero graphics. Authority is communicated through data density and color precision, not visual metaphor.
- **Animate data, not chrome.** Transitions exist to confirm interaction and draw attention to changing values — not to entertain.

---

## 2. Brand Identity

### Logo & Wordmark

The Sentinel brand mark is a `36×36px` icon container using an `indigo → cyan` linear gradient (`135deg, #6366f1 → #22d3ee`) with `border-radius: 10px` and an indigo glow `box-shadow: 0 0 20px rgba(99,102,241,0.30)`.

The wordmark "Sentinel" uses a CSS gradient text technique:

```css
background: linear-gradient(135deg, #ffffff, #22d3ee);
-webkit-background-clip: text;
-webkit-text-fill-color: transparent;
```text
This is the **only decorative text treatment** in the entire product. All other text uses solid color values.

### Voice & Tone (UI Copy)

- Labels are **terse and technical**: "Credential Stuffing" not "We detected credential stuffing activity"
- Status messages are **factual, not reassuring**: "Suspended" not "Account safely suspended"
- Error states identify the problem precisely: "Signature mismatch — timestamp drift exceeds 5 min" not "Something went wrong"
- Empty states include a **diagnostic hint**, not just "No data found"

---

## 3. Color System

### Background Stack

The background is **never pure black**. The deepest surface is `#0a0e1a` — a dark indigo-navy that keeps the interface from feeling harsh under ambient light.

```text
#060810  surface-container-lowest  (almost never used — extreme depth)
#0a0e1a  background / surface-dim  (page canvas)
#0f1423  surface / sidebar         (primary surfaces)
#151b2e  surface-container         (inputs, code blocks)
#1a2238  surface-container-high    (slightly elevated panels)
#202840  surface-container-highest (maximum non-card elevation)
```text
Glass surfaces use semi-transparent fills over the dark canvas:

```text
rgba(20, 27, 48, 0.70)   Level 1 — standard card
rgba(30, 40, 70, 0.80)   Level 2 — hovered card
rgba(15, 20, 35, 0.90)   Level 3 — modal / sticky header
```text
### Text Colors

```text
#ffffff   on-surface-bright   (page titles, critical labels)
#e8eaed   on-surface          (primary body text)
#9aa0b4   on-surface-variant  (secondary text, table cells)
#5a6178   on-surface-muted    (table headers, timestamps, placeholders)
```text
Minimum contrast ratio for `on-surface-muted` against `surface`: **4.5:1** — do not make text any dimmer than this value.

### Brand & Accent

```text
#6366f1   primary             (buttons, active nav, focus rings, glow source)
#22d3ee   secondary           (metric highlights, brand gradient partner)
#a78bfa   tertiary            (KPI accent, avatar gradient fills)
```text
### Semantic Status Palette

Risk levels form a strict five-step traffic-light scale. These colors must not be reused for non-risk purposes.

| Token | Hex | Usage |
| --- | --- | --- |
| `risk-low` | `#34d399` | 0–20 score |
| `risk-moderate` | `#60a5fa` | 21–40 score |
| `risk-elevated` | `#fbbf24` | 41–60 score |
| `risk-high` | `#fb923c` | 61–80 score |
| `risk-critical` | `#ef4444` | 81–100 score |

Status labels for user accounts:

| Token | Hex | State |
| --- | --- | --- |
| `status-active` | `#34d399` | Normal operation |
| `status-flagged` | `#fbbf24` | Pending manual review |
| `status-suspended` | `#ef4444` | Auto-suspended by rule engine |
| `status-blocked` | `#dc2626` | Hard-blocked via blacklist |
| `status-pending` | `#fbbf24` | Awaiting first event |
| `status-resolved` | `#34d399` | Reviewed and cleared |
| `status-dismissed` | `#5a6178` | Dismissed without action |

### Border & Outline

```text
rgba(255, 255, 255, 0.06)    outline-variant   (default card/input border)
rgba(99, 102, 241, 0.15)     outline           (default interactive border)
rgba(99, 102, 241, 0.30)     outline-hover     (hover / focus border)
```text
### Ambient Glow

A single `600×600px` radial gradient fixed at the top-right of the viewport viewport provides the sole "light source" for the entire app:

```css
background: radial-gradient(circle, rgba(99,102,241,0.30) 0%, transparent 70%);
position: fixed;
top: -100px;
right: -100px;
pointer-events: none;
opacity: 0.5;
z-index: 0;
```text
This element must not appear behind the sidebar. It is scoped to `.main-content` only.

---

## 4. Typography

### Typeface Stack

**Inter** — all UI text, headings, navigation, labels, body copy.  
**JetBrains Mono** — all numeric data, code, API keys, IP addresses, timestamps, risk scores.

No third typeface is permitted. Adding a display font would undermine the precision aesthetic.

**Base font-size on `<html>`: 14px.** This makes `rem` units slightly more compact than browser defaults, enabling higher information density.

### Type Scale

| Token | Family | Size | Weight | Line Height | Tracking | Usage |
| --- | --- | --- | --- | --- | --- | --- |
| `display-lg` | Inter | 32px | 800 | 40px | −0.03em | Hero numbers, empty state headings |
| `headline-lg` | Inter | 24px | 700 | 32px | −0.02em | Page titles |
| `headline-md` | Inter | 20px | 700 | 28px | −0.02em | Section headings |
| `title-lg` | Inter | 16px | 600 | 24px | 0 | Card titles |
| `title-md` | Inter | 14px | 600 | 20px | 0 | Nav items, sub-headings |
| `body-lg` | Inter | 14px | 400 | 22px | 0 | Primary body text |
| `body-md` | Inter | 13px | 400 | 20px | 0 | Table cells, descriptions |
| `body-sm` | Inter | 12px | 400 | 18px | 0 | Captions, footnotes |
| `label-lg` | Inter | 12px | 600 | 16px | +0.05em | Section group labels |
| `label-md` | Inter | 11px | 600 | 14px | +0.06em | Card headers, table headers |
| `label-sm` | Inter | 10px | 600 | 12px | +0.08em | Badges, uppercase status tags |
| `mono-md` | JetBrains Mono | 13px | 500 | 20px | 0 | Inline code, IPs, timestamps |
| `mono-sm` | JetBrains Mono | 11px | 400 | 16px | 0 | Secondary mono data |
| `kpi-value` | JetBrains Mono | 28px | 800 | 36px | −0.03em | KPI card primary numbers |

### Typography Rules

- **Table headers**: always `label-md` + uppercase + `letter-spacing: 0.05em` — deliberately de-emphasized to let data lead
- **Sidebar nav group labels**: `label-sm` + uppercase + `letter-spacing: 0.10em` — maximum tracking for hierarchy disambiguation
- **Numeric columns**: always `font-variant-numeric: tabular-nums` — prevents column jitter on live updates
- **Negative / decreasing values**: use `on-surface-muted` with a `▼` prefix glyph rather than red coloring (red is reserved for critical risk states)
- **Truncation**: never truncate risk scores, IP addresses, or rule names — these are critical data. Truncate user IDs and emails with `text-overflow: ellipsis` as a last resort only

---

## 5. Spacing & Layout

### Base Unit

All spacing derives from a **4px base unit** — more compact than the standard 8px, enabling the information density a security dashboard requires.

```text
xs:   4px
sm:   8px
md:   12px
lg:   16px
xl:   24px
2xl:  32px
```text
### Layout Shell

```text
Sidebar:         260px fixed-left, full-height, z-index: 40
Main content:    calc(100vw - 260px), scrollable
Page header:     sticky top-0, z-index: 50, height: ~72px
Page body:       padding: 24px 32px
```text
### Grid Systems

| Context | Grid definition |
| --- | --- |
| KPI row | `repeat(auto-fit, minmax(180px, 1fr))` |
| Charts row — primary | `2fr 1fr` |
| Charts row — secondary | `1fr 1fr` |
| User profile meta fields | `repeat(auto-fill, minmax(150px, 1fr))` |
| Rule cards | single-column, stacked |
| Auth pages | centered single column, `max-width: 420px` |

### Card Spacing

- Card padding: `20px`
- Card gap in grid: `16px`
- Section gap (between card rows): `24px`
- Card header bottom border: `1px solid rgba(255,255,255,0.06)` with `padding-bottom: 12px; margin-bottom: 16px`

---

## 6. Elevation & Depth

The glass stack has four effective levels. Treat any surface that doesn't match one of these as a design error.

| Level | Use case | Background | Blur | Border |
| --- | --- | --- | --- | --- |
| **0 — Canvas** | Page background | `#0a0e1a` | none | none |
| **1 — Card** | KPI cards, data cards, sidebar | `rgba(20,27,48,0.70)` | `blur(12px)` | `rgba(255,255,255,0.06)` |
| **2 — Elevated** | Hovered / active cards | `rgba(30,40,70,0.80)` | `blur(20px)` | `rgba(99,102,241,0.30)` |
| **3 — Overlay** | Modals, sticky header, tooltips | `rgba(15,20,35,0.90)` | `blur(24px)` | `rgba(99,102,241,0.20)` |

The sticky page header uses Level 3 so it visually floats above scrolling content without a harsh dividing line.

Modal overlay backdrop: `rgba(0,0,0,0.70)` — dark enough to focus attention, not opaque enough to destroy context.

---

## 7. Shape & Radii

The shape language is **consistently rounded but never pill-shaped at the structural level** — pills are reserved for badges to create clear component disambiguation.

| Radius | Value | Applied to |
| --- | --- | --- |
| `sm` | 6px | Buttons, inputs, nav items, pagination, dropdowns |
| `DEFAULT` | 8px | General-purpose containers |
| `md` | 10px | Code blocks, KPI icon containers, sidebar brand icon |
| `lg` | 16px | Cards, modals, user profile header |
| `xl` | 20px | Large feature cards |
| `full` | 9999px | Badges, tags, toggle tracks, avatar circles |

Never mix `lg` and `sm` radius on the same component (e.g., a button inside a card should use `6px` regardless of the card's `16px`).

---

## 8. Iconography

Use **Lucide Icons** (stroke-width: `1.5px`, size: `16px` default). This weight is intentionally lighter than the bold UI text — icons support, they do not compete.

| Context | Icon size | Color |
| --- | --- | --- |
| Nav items | 16px | `on-surface-variant` → `primary` on active |
| KPI card icon | 20px | Semantic accent color, inside `40×40px` tinted container |
| Table action buttons | 14px | `on-surface-variant` |
| Alert / status icons | 16px | Semantic status color |
| Empty state | 32px | `on-surface-muted` |
| Modal close button | 16px | `on-surface-variant` |

**Animated icons** are permitted only for live/streaming states:

- A `pulse` animation on the live-feed indicator dot (indigo, 2s infinite)
- A `spin` animation on loading spinners (indigo ring, 0.7s linear infinite)

No other icons should animate.

---

## 9. Motion & Animation

All transitions use **ease-out cubic-bezier `(0.4, 0, 0.2, 1)`** — snappy entry, smooth exit. This is the Material Design standard easing and communicates precision without sluggishness.

### Timing Tiers

| Tier | Duration | Applied to |
| --- | --- | --- |
| `fast` | 150ms | Nav hover, button hover, row hover, badge appearance, tooltip show |
| `base` | 250ms | Card hover lift, border transitions, modal open/close, input focus |
| `slow` | 400ms | Risk bar fill animation (on page load/data change), chart enter |
| `page` | 300ms | Page-level enter animations (staggered card entrance) |

### Entrance Animations

Cards, KPI tiles, and table rows animate in on page load:

```css
@keyframes card-enter {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}
```text
Stagger delay: `50ms × element index`. Maximum stagger: `300ms` (cap after 6 elements).

### Live Data Updates

When a KPI value changes on a polling interval:

1. Outgoing value: `opacity → 0.3` over `150ms`
2. New value replaces in DOM
3. Incoming value: `opacity → 1` over `150ms` + color flash to semantic accent for `400ms` then fade back

This confirms data freshness without a full re-render animation.

### Hover States

- Cards: `translateY(-2px)` + `box-shadow: 0 0 20px rgba(99,102,241,0.15)` — `base` timing
- Buttons: background color shift only — `fast` timing
- Nav items: background tint — `fast` timing
- Table rows: background tint — `fast` timing (no transform — rows should not move)

### What NOT to animate

- Risk score values mid-table (creates noise during live updates)
- Sidebar width or position
- Page title / breadcrumb
- Any element the user has not directly interacted with, except live data indicators

---

## 10. Components

### Sidebar

**Structure:** Brand block → Nav sections (Overview, Investigation, Configuration) → Admin footer

- Width: `260px`, fixed
- Background: `#0f1423`, `border-right: 1px solid rgba(255,255,255,0.06)`
- Brand icon: `36×36px`, `border-radius: 10px`, indigo→cyan gradient, `box-shadow: 0 0 20px rgba(99,102,241,0.30)`
- Wordmark: gradient text, `title-lg` weight
- Nav group labels: `label-sm` + uppercase + `letter-spacing: 0.10em` + `on-surface-muted`
- Nav items: `padding: 10px 12px`, `border-radius: 6px`, `title-md`
- Active indicator: `3px` wide left-edge indigo bar, absolutely positioned — **not** a border
- Nav badge (review queue count): `background: #fb7185`, `border-radius: 10px`, `label-md`
- Admin footer: avatar with initials (violet gradient, circle), name + role in stacked text, settings icon

### KPI Cards

Each card occupies one grid cell in the auto-fit KPI row.

- Background: Level 1 glass
- Border-radius: `16px`
- Top accent stripe: `2px`, full card width, semantic color per KPI theme
- Icon container: `40×40px`, `border-radius: 10px`, `15% opacity tint` of semantic color, icon at `20px`
- KPI value: `kpi-value` token (JetBrains Mono 28px / 800)
- Label: `label-md` + uppercase + `on-surface-muted`
- Delta indicator: `body-sm` colored by positive/negative change
- Hover: `translateY(-2px)` + indigo glow shadow

**The six KPI cards and their accent colors:**

| KPI | Accent |
| --- | --- |
| Total Events | `primary` indigo |
| Active Users | `secondary` cyan |
| Flagged Accounts | `warning` amber |
| Critical Alerts | `error-dim` rose |
| Rules Triggered | `success` emerald |
| Avg Risk Score | `tertiary` violet |

### Data Tables

- Container: Level 1 glass card with `16px` border-radius
- Table: `width: 100%`, `border-collapse: separate`, `border-spacing: 0`
- Header row: `background: rgba(99,102,241,0.06)`, `label-md` + uppercase, `on-surface-muted`
- Data rows: `border-bottom: 1px solid rgba(255,255,255,0.06)`, `cursor: pointer`
- Row hover: `background: rgba(99,102,241,0.04)` — barely visible, just enough to confirm hover
- Cell text: `body-md`, `on-surface-variant`
- Numeric cells: `mono-md`, `tabular-nums`
- Risk score column: value + mini progress bar + badge, all in-cell
- Empty state row: centered, `on-surface-muted`, with diagnostic hint text
- Pagination: ghost buttons with indigo active state, `label-md`

#### Sortable Columns

Column headers with sort affordance show a `↕` icon by default, replacing with `↑` or `↓` on sort. Icon at `12px`, `on-surface-muted`. Sorted column header text uses `on-surface` (not muted).

#### Row Actions

Inline action buttons appear on row hover only (`opacity: 0 → 1` on `fast` timing). Max two actions per row: primary action (e.g., "View") as ghost button, destructive action (e.g., "Suspend") as danger ghost.

### Risk Score Display

The risk score trio appears in table rows, user profile headers, and event detail panels:

```text
[78]  [████████░░]  [HIGH]
 ↑          ↑         ↑
mono-md   70×6px    badge-pill
colored   progress  semantic bg
```text
- Numeric value: `mono-md`, colored by risk level token
- Progress bar: `70px × 6px`, `border-radius: 3px`, track `rgba(255,255,255,0.06)`, fill color = risk level token
- Badge: pill with `15% opacity tint` background + full-saturation text

### Badges & Tags

All status and label chips use the pill badge:

```css
border-radius: 9999px;
padding: 3px 10px;
font-size: 11px;
font-weight: 600;
letter-spacing: 0.03em;
background: rgba(<semantic-color-rgb>, 0.15);
color: <semantic-color>;
```text
Never use a solid filled badge background — it creates false visual weight.

### Buttons

| Variant | Background | Text | Border |
| --- | --- | --- | --- |
| Primary | `#6366f1` | white | none |
| Danger | `rgba(239,68,68,0.15)` | `#fb7185` | `rgba(239,68,68,0.20)` |
| Success | `rgba(52,211,153,0.15)` | `#34d399` | `rgba(52,211,153,0.20)` |
| Ghost | transparent | `on-surface-variant` | `rgba(255,255,255,0.06)` |

All buttons: `border-radius: 6px`, `padding: 8px 16px`, `body-md / 500 weight`.

Primary button hover: `background: #5558e6` + `box-shadow: 0 0 16px rgba(99,102,241,0.30)`.

### Form Inputs

```text
background:    #151b2e
border:        1px solid rgba(255,255,255,0.06)
border-radius: 6px
color:         on-surface (#e8eaed)
padding:       10px 14px
font-size:     14px

:focus
  border:     1px solid #6366f1
  box-shadow: 0 0 0 3px rgba(99,102,241,0.30)
```text
The focus ring matches the brand glow — this is intentional continuity, not coincidence.

Placeholder text: `on-surface-muted (#5a6178)`.

Search inputs prepend a `search` Lucide icon at `16px` inside left padding.

### Toggle Switch

```text
width: 36px  height: 20px
track active:   #6366f1
track inactive: rgba(255,255,255,0.10)
thumb:          #ffffff, 16×16px, border-radius: 50%
transition:     base (250ms)
```text
Never use a toggle for destructive actions — use a confirmation modal instead.

### Timeline (User Activity)

Vertical line: `2px`, `rgba(255,255,255,0.06)` — intentionally recessive.

Dot defaults: `10px`, indigo fill, `2px solid #0a0e1a` border (creates separation from line).

Dot overrides by event severity:

- `risk-high` event: `#fb923c`
- `risk-critical` event: `#ef4444`
- System event: `on-surface-muted`

Timestamps: `mono-sm`, `on-surface-muted`.

Event title: `body-md`, `on-surface`.

Event detail: `body-sm`, `on-surface-variant`, collapsible with `▶ / ▼` disclosure.

### Modals

- Overlay: `rgba(0,0,0,0.70)` backdrop
- Container: Level 3 glass, `border-radius: 16px`, `max-width: 480px`
- Header: title (`title-lg`) + close icon (`×` at `16px`), `border-bottom: 1px solid rgba(255,255,255,0.06)`
- Footer: action buttons right-aligned, ghost cancel on left
- Enter animation: `scale(0.96) + opacity(0) → scale(1) + opacity(1)`, `base` timing

**One-time secret display** (API key creation): after generation, the key renders in a highlighted mono block with a copy-to-clipboard icon. A rose warning label reads: "This key will not be displayed again." The copy button changes to a checkmark for `2000ms` on success.

### Code Blocks

```text
background:    #151b2e
border:        1px solid rgba(255,255,255,0.06)
border-radius: 10px
font-family:   JetBrains Mono
font-size:     12px
line-height:   1.8
padding:       16px
overflow-x:    auto
```text
Syntax color mapping (used only in code blocks):

| Token type | Color |
| --- | --- |
| Commands / keywords | `#fbbf24` (amber) |
| Strings / values | `#34d399` (emerald) |
| JSON data / objects | `#22d3ee` (cyan) |
| Comments | `#5a6178` (muted) |
| Errors | `#ef4444` (red) |

This is the **only location in the UI** where multiple accent colors co-exist in the same region.

### Detection Rule Cards

Horizontal card layout:

```text
[Rule name]   [Active/Disabled badge]        [Weight input]  [Toggle]
[Category]    [Trigger count: 142 this week]
```text
- Rule name: `title-md`
- Category: `label-sm` + icon + `on-surface-muted`
- Trigger count: `mono-sm`, `on-surface-variant`
- Weight input: `width: 64px`, same styling as form inputs
- Toggle: standard toggle component

Rules grouped under category headings (Authentication, Automation, Geography, Identity, Fraud, Access Control) with icon + `label-lg` + uppercase heading.

### Scrollbars

```css
::-webkit-scrollbar        { width: 6px; height: 6px; }
::-webkit-scrollbar-track  { background: #0a0e1a; }
::-webkit-scrollbar-thumb  { background: rgba(99,102,241,0.15); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: #6366f1; }
```text
---

## 11. Page Layouts

### Dashboard (Overview)

```text
[Page header — sticky]
[KPI grid — 6 cards, auto-fit]
[Charts row 1 — 2fr | 1fr]
  Left: Events over time (line chart, 7d/30d/90d toggle)
  Right: Risk distribution (doughnut chart)
[Charts row 2 — 1fr | 1fr]
  Left: Events by type (bar chart)
  Right: Riskiest users (compact table, top 5)
```text
### User List

```text
[Page header with search + filter bar]
[Data table — full width]
  Columns: User ID · Email · Risk Score · Status · Last Seen · Events · Actions
[Pagination footer]
```text
### User Profile (Single User View)

```text
[Profile header card — full width]
  Avatar · Name / ID · Status badge · Risk score trio · Meta fields grid
[Content grid — 2fr | 1fr]
  Left: Activity timeline (scrollable, all events)
  Right:
    Top: Risk breakdown (per-rule scores)
    Bottom: Device / IP history
```text
### Event Log

```text
[Page header with date range picker + filter chips]
[Data table — full width]
  Columns: Time · Event Type · User ID · IP · Risk Score · Rules Triggered
[Expandable row detail: full event JSON in code block]
[Pagination footer]
```text
### Review Queue

```text
[Page header + status filter tabs (Flagged | Suspended | All)]
[Data table]
  Columns: User · Risk · Reason · Triggered At · Actions (Resolve / Dismiss / Suspend)
```text
### Rule Engine

```text
[Page header with "New Rule" button]
[Category sections — grouped, collapsible]
  Each section: header label + rule cards list
```text
### Settings

```text
[Tab nav: General | API Keys | Integrations | Danger Zone]
[Tab content area — single column, max-width: 640px]
```text
### Authentication Pages (Login / Signup / Forgot Password)

```text
[Full-bleed dark background with ambient glow]
[Centered card — max-width: 420px, Level 1 glass]
  Brand mark + wordmark
  Form fields
  Primary CTA button
  Ghost link (e.g., "Forgot password?")
```text
No sidebar. No nav. Brand continuity maintained through ambient glow and color system.

---

## 12. Data Visualization

All charts use **Chart.js** with custom theming. No chart library should be added without a documented reason.

### Global Chart Config

```js
Chart.defaults.color = '#9aa0b4';         // axis labels
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)'; // grid lines
Chart.defaults.font.family = 'Inter';
Chart.defaults.font.size = 11;
```text
### Events Over Time (Line Chart)

- Line color: `#6366f1`
- Fill: `rgba(99,102,241,0.08)` gradient to transparent
- Point: `4px`, filled indigo, `6px` on hover
- No bezier curve — use straight segments (`tension: 0`) for precision
- X-axis: date labels in `mono-sm`, `on-surface-muted`
- Y-axis: integer event counts, right-aligned
- Time range toggle: `7d / 30d / 90d` — ghost buttons above chart, active = primary

### Risk Distribution (Doughnut Chart)

- Segments: the five risk-level colors in order (emerald → blue → amber → orange → red)
- Gap between segments: `3px`
- Center label: total event count in `kpi-value`, below a `label-md` "Total Events"
- No chart legend — a separate legend list below uses badge chips for labeling

### Events by Type (Horizontal Bar Chart)

- Bar color: `#6366f1` with `rgba(99,102,241,0.60)` for non-focused bars
- Bar radius: `4px`
- Y-axis: event type labels (`body-sm`, left-aligned)
- X-axis: count values (`mono-sm`)
- Hover tooltip: Level 3 glass, `border: 1px solid rgba(99,102,241,0.30)`, `border-radius: 8px`

### Chart Tooltips (Global)

```js
plugins: {
  tooltip: {
    backgroundColor: 'rgba(15, 20, 35, 0.90)',
    borderColor: 'rgba(99, 102, 241, 0.20)',
    borderWidth: 1,
    titleFont: { family: 'Inter', size: 12, weight: '600' },
    bodyFont: { family: 'JetBrains Mono', size: 11 },
    padding: 10,
    cornerRadius: 8,
  }
}
```text
---

## 13. Interaction States

Every interactive element must implement all applicable states. Missing states are treated as incomplete implementations.

| State | Visual treatment |
| --- | --- |
| **Default** | Base design token values |
| **Hover** | Background tint or `translateY(-2px)` + border brightens to `outline-hover` |
| **Focus-visible** | `box-shadow: 0 0 0 3px rgba(99,102,241,0.30)` — keyboard nav only, not on click |
| **Active / Pressed** | `translateY(0)` + slight background darken |
| **Disabled** | `opacity: 0.40`, `cursor: not-allowed`, no hover effects |
| **Loading** | Spinner replaces icon or text, button width locked to prevent layout shift |
| **Error** | Border `1px solid #ef4444`, `box-shadow: 0 0 0 3px rgba(239,68,68,0.20)` |
| **Success** | Border `1px solid #34d399`, checkmark icon confirmation, `2000ms` then revert |

### Empty States

Every table and data region must have a designed empty state:

```text
[Icon — 32px, on-surface-muted]
[Heading — title-md, on-surface]
[Diagnostic hint — body-sm, on-surface-variant]
[Optional: CTA button if action would resolve the empty state]
```text
Example: Events table empty state: `"No events ingested yet" / "Send your first event via the API or SDK to start monitoring." / [View API Docs button]`

### Error States

Network/API errors surface as a toast notification (bottom-right, Level 3 glass, `error` left border, `4000ms` auto-dismiss). They do **not** replace page content unless the entire page depends on the failed request.

---

## 14. Accessibility

- **Contrast**: all text meets WCAG AA (4.5:1 minimum for normal text, 3:1 for large text / UI components)
- **Focus management**: modals trap focus on open, return focus to trigger on close
- **Keyboard navigation**: all interactive elements reachable via Tab; custom components implement ARIA roles (`role="button"`, `role="switch"`, `role="dialog"` etc.)
- **Motion**: wrap all non-essential animations in `@media (prefers-reduced-motion: reduce)` — disable transforms and entrance animations, keep opacity transitions only
- **Color**: risk levels are never communicated by color alone — always accompanied by a text label or icon
- **Screen readers**: risk score bars use `aria-valuenow`, `aria-valuemin`, `aria-valuemax`. Status badges use `aria-label` with the full status string. Live KPI values use `aria-live="polite"` for polling updates.

---

## 15. Design Tokens (Full Reference)

```yaml
colors:
  background:                   "#0a0e1a"
  surface:                      "#0f1423"
  surface-dim:                  "#0a0e1a"
  surface-bright:               "#1e2840"
  surface-container-lowest:     "#060810"
  surface-container-low:        "#0f1423"
  surface-container:            "#151b2e"
  surface-container-high:       "#1a2238"
  surface-container-highest:    "#202840"
  surface-glass:                "rgba(20, 27, 48, 0.70)"
  surface-glass-hover:          "rgba(30, 40, 70, 0.80)"

  on-surface:                   "#e8eaed"
  on-surface-variant:           "#9aa0b4"
  on-surface-muted:             "#5a6178"
  on-surface-bright:            "#ffffff"

  outline:                      "rgba(99, 102, 241, 0.15)"
  outline-variant:              "rgba(255, 255, 255, 0.06)"
  outline-hover:                "rgba(99, 102, 241, 0.30)"

  primary:                      "#6366f1"
  on-primary:                   "#ffffff"
  primary-container:            "rgba(99, 102, 241, 0.12)"
  on-primary-container:         "#6366f1"
  primary-glow:                 "rgba(99, 102, 241, 0.30)"

  secondary:                    "#22d3ee"
  on-secondary:                 "#ffffff"
  secondary-container:          "rgba(34, 211, 238, 0.10)"
  secondary-glow:               "rgba(34, 211, 238, 0.20)"

  tertiary:                     "#a78bfa"
  on-tertiary:                  "#ffffff"
  tertiary-container:           "rgba(167, 139, 250, 0.10)"

  success:                      "#34d399"
  success-container:            "rgba(52, 211, 153, 0.15)"
  warning:                      "#fbbf24"
  warning-container:            "rgba(251, 191, 36, 0.15)"
  error:                        "#ef4444"
  error-container:              "rgba(239, 68, 68, 0.15)"
  error-dim:                    "#fb7185"
  orange:                       "#fb923c"
  orange-container:             "rgba(251, 146, 60, 0.15)"
  blue:                         "#60a5fa"
  blue-container:               "rgba(96, 165, 250, 0.15)"

  risk-low:                     "#34d399"
  risk-moderate:                "#60a5fa"
  risk-elevated:                "#fbbf24"
  risk-high:                    "#fb923c"
  risk-critical:                "#ef4444"

  status-active:                "#34d399"
  status-flagged:               "#fbbf24"
  status-suspended:             "#ef4444"
  status-blocked:               "#dc2626"
  status-pending:               "#fbbf24"
  status-resolved:              "#34d399"
  status-dismissed:             "#5a6178"

  inverse-surface:              "#e8eaed"
  inverse-on-surface:           "#151b2e"

typography:
  display-lg:    { fontFamily: "Inter",           fontSize: 32px, fontWeight: 800, lineHeight: 40px, letterSpacing: -0.03em }
  headline-lg:   { fontFamily: "Inter",           fontSize: 24px, fontWeight: 700, lineHeight: 32px, letterSpacing: -0.02em }
  headline-md:   { fontFamily: "Inter",           fontSize: 20px, fontWeight: 700, lineHeight: 28px, letterSpacing: -0.02em }
  title-lg:      { fontFamily: "Inter",           fontSize: 16px, fontWeight: 600, lineHeight: 24px }
  title-md:      { fontFamily: "Inter",           fontSize: 14px, fontWeight: 600, lineHeight: 20px }
  body-lg:       { fontFamily: "Inter",           fontSize: 14px, fontWeight: 400, lineHeight: 22px }
  body-md:       { fontFamily: "Inter",           fontSize: 13px, fontWeight: 400, lineHeight: 20px }
  body-sm:       { fontFamily: "Inter",           fontSize: 12px, fontWeight: 400, lineHeight: 18px }
  label-lg:      { fontFamily: "Inter",           fontSize: 12px, fontWeight: 600, lineHeight: 16px, letterSpacing: 0.05em }
  label-md:      { fontFamily: "Inter",           fontSize: 11px, fontWeight: 600, lineHeight: 14px, letterSpacing: 0.06em }
  label-sm:      { fontFamily: "Inter",           fontSize: 10px, fontWeight: 600, lineHeight: 12px, letterSpacing: 0.08em }
  mono-md:       { fontFamily: "JetBrains Mono",  fontSize: 13px, fontWeight: 500, lineHeight: 20px }
  mono-sm:       { fontFamily: "JetBrains Mono",  fontSize: 11px, fontWeight: 400, lineHeight: 16px }
  kpi-value:     { fontFamily: "JetBrains Mono",  fontSize: 28px, fontWeight: 800, lineHeight: 36px, letterSpacing: -0.03em }

rounded:
  sm:      6px
  DEFAULT: 8px
  md:      10px
  lg:      16px
  xl:      20px
  full:    9999px

spacing:
  unit:            4px
  xs:              4px
  sm:              8px
  md:              12px
  lg:              16px
  xl:              24px
  2xl:             32px
  sidebar-width:   260px
  page-padding-x:  32px
  page-padding-y:  24px
  card-gap:        16px
  kpi-grid-gap:    16px
  section-gap:     24px

elevation:
  level-0: { background: "#0a0e1a", shadow: none }
  level-1: { background: "rgba(20,27,48,0.70)",  blur: blur(12px), border: "rgba(255,255,255,0.06)",   shadow: "0 1px 2px rgba(0,0,0,0.30)" }
  level-2: { background: "rgba(30,40,70,0.80)",  blur: blur(20px), border: "rgba(99,102,241,0.30)",    shadow: "0 4px 12px rgba(0,0,0,0.40)" }
  level-3: { background: "rgba(15,20,35,0.90)",  blur: blur(24px), border: "rgba(99,102,241,0.20)",    shadow: "0 8px 32px rgba(0,0,0,0.50)" }
  glow:    { shadow: "0 0 20px rgba(99,102,241,0.15)" }

motion:
  fast:       "150ms cubic-bezier(0.4, 0, 0.2, 1)"
  base:       "250ms cubic-bezier(0.4, 0, 0.2, 1)"
  slow:       "400ms cubic-bezier(0.4, 0, 0.2, 1)"
  page:       "300ms cubic-bezier(0.4, 0, 0.2, 1)"
  hover-lift: "translateY(-2px)"
```text
---

*Last updated: 2026 · Sentinel Design System v2.0*


