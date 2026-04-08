# Securecy LMS — Design System

## Brand
- **Brand name:** Securecy
- **Font:** Montserrat (Google Fonts) — Bold, SemiBold, Regular, Thin weights

---

## Color System

### Primary (Blue)
| Token | Hex (approx) | Usage |
|-------|-------------|-------|
| primary-50 | #e8f0f8 | Background |
| primary-100 | #c8ddef | Background |
| primary-200 | #a3c4e2 | Background |
| primary-300 | #7caad4 | Hover state |
| primary-400 | #5a92c6 | Hover state |
| primary-500 | #3b7ab8 | Primary actions |
| primary-600 | #2e6494 | Active/pressed |
| primary-700 | #234e74 | Active/pressed |
| primary-800 | #193a57 | Borders/shadows |
| primary-900 | #10273c | Borders/shadows |
| primary-950 | #091826 | Borders/shadows |

### Neutral (Gray)
| Token | Hex (approx) | Usage |
|-------|-------------|-------|
| neutral-0 | #ffffff | — |
| neutral-50 | #f5f5f5 | Background |
| neutral-100 | #e5e5e5 | Background |
| neutral-200 | #cccccc | Background |
| neutral-300 | #b3b3b3 | Hover state |
| neutral-400 | #999999 | Hover state |
| neutral-500 | #737373 | Primary actions (text) |
| neutral-600 | #595959 | Active/pressed |
| neutral-700 | #404040 | Active/pressed |
| neutral-800 | #2b2b2b | Borders/shadows |
| neutral-900 | #1a1a1a | Borders/shadows |
| neutral-950 | #0d0d0d | Borders/shadows |

### Night (Dark)
| Token | Hex (approx) | Usage |
|-------|-------------|-------|
| night-0 | #ffffff | — |
| night-50 | #e8e8e8 | Background |
| night-100 | #c0c0c0 | Background |
| night-200 | #969696 | Background |
| night-300 | #6e6e6e | Hover state |
| night-400 | #4a4a4a | Hover state |
| night-500 | #333333 | Primary actions |
| night-600 | #2a2a2a | Active/pressed |
| night-700 | #222222 | Active/pressed |
| night-800 | #1a1a1a | Borders/shadows |
| night-900 | #111111 | Borders/shadows |
| night-950 | #080808 | Borders/shadows |

### Success (Green)
| Token | Hex (approx) | Usage |
|-------|-------------|-------|
| success-50 | #e6f5ed | Background |
| success-100 | #b3e2c8 | Background |
| success-200 | #80d0a3 | Background |
| success-300 | #4dbd7e | Hover state |
| success-400 | #26a85f | Hover state |
| success-500 | #0f8c45 | Primary actions |
| success-600 | #0c7339 | Active/pressed |
| success-700 | #095a2d | Active/pressed |
| success-800 | #064220 | Borders/shadows |
| success-900 | #032b14 | Borders/shadows |

### Warning (Amber/Gold)
| Token | Hex (approx) | Usage |
|-------|-------------|-------|
| warning-50 | #fef6e0 | Background |
| warning-100 | #fce8a8 | Background |
| warning-200 | #f8d46e | Background |
| warning-300 | #f2be3a | Hover state |
| warning-400 | #e0a820 | Hover state |
| warning-500 | #c4900c | Primary actions |
| warning-600 | #a07608 | Active/pressed |
| warning-700 | #7c5c06 | Active/pressed |
| warning-800 | #5a4204 | Borders/shadows |
| warning-900 | #3a2b02 | Borders/shadows |

### Error (Red)
| Token | Hex (approx) | Usage |
|-------|-------------|-------|
| error-50 | #fde8e8 | Background |
| error-100 | #f9c4c4 | Background |
| error-200 | #f09e9e | Background |
| error-300 | #e47878 | Hover state |
| error-400 | #d65555 | Hover state |
| error-500 | #c03535 | Primary actions |
| error-600 | #a02828 | Active/pressed |
| error-700 | #7e1e1e | Active/pressed |
| error-800 | #5e1515 | Borders/shadows |
| error-900 | #400e0e | Borders/shadows |

---

## Color Usage Guide
| Range | Purpose |
|-------|---------|
| 500 | Primary actions, buttons, links |
| 100–200 | Backgrounds, surfaces, fills |
| 300–400 | Hover states |
| 600–700 | Active/pressed states |
| 800–900 | Borders, shadows, dark accents |

---

## Typography

All text uses **Montserrat** from Google Fonts.

| Style | Weight | Size | Line Height | Usage |
|-------|--------|------|-------------|-------|
| Display/Hero | Bold (700) | 48px | 120% | Dashboard landing titles, KPIs |
| Heading 1 | Bold (700) | 32px | 100% | Page titles |
| Heading 2 | SemiBold (600) | 28px | 120% | Section headers |
| Heading 3 | SemiBold (600) | 24px | 125% | Card titles, smaller headers |
| Heading 4 | Medium (500) | 20px | 130% | Subtitles, tab headers |
| Subheading | Medium (500) | 18px | 130% | — |
| Body Large | Regular (400) | 16px | 140% | Main content, paragraphs |
| Body Medium | Regular (400) | 14px | 150% | Secondary text, form labels |
| Body Small | Regular (400) | 12px | 150% | Helper text, notes |
| Captions | Regular (400) | 12px | 130% | Captions, timestamps, tooltips |
| Button/Label | SemiBold (600) | 16px | 100% | Buttons, tabs, badges |
| Numeric/Metrics | SemiBold (600) | 24px | 110% | KPI values, counters |
| Overline/Tagline/Chips | Regular (400) | 10px | 120% | Chips and small tags |

---

## Component Patterns

### Buttons
- **Primary:** `bg-primary-500`, white text, hover `bg-primary-300`, active `bg-primary-700`
- **Secondary:** `bg-neutral-100` border `neutral-300`, hover `neutral-200`, active `neutral-400`
- **Danger:** `bg-error-500`, white text, hover `error-400`, active `error-700`
- **Success:** `bg-success-500`, white text
- Font: Button/Label style (Montserrat SemiBold 16px)

### Cards
- White background, `neutral-200` border or subtle shadow
- Rounded corners (8px)
- Padding 16–24px

### Forms
- Input border: `neutral-300`, focus ring: `primary-500`
- Label: Body Medium style
- Error text: `error-500`, Body Small style

### Tables
- Header: `neutral-100` bg, Body Medium SemiBold
- Row hover: `primary-50`
- Border: `neutral-200`

### Status Badges
- Success: `success-50` bg, `success-700` text
- Warning: `warning-50` bg, `warning-700` text
- Error: `error-50` bg, `error-700` text
- Info: `primary-50` bg, `primary-700` text
- Neutral: `neutral-100` bg, `neutral-700` text
