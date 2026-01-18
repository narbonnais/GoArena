# GoArena UI/UX Diagnostic and Frontend Brief

## Purpose and scope
This document consolidates the UI/UX diagnostic and the recommended design direction for the GoArena web app (chess.com-inspired experience). It covers global issues, page-specific notes, and concrete specs for the visual system, the Play hub, responsive board behavior, and the Analysis workspace.

## Global diagnostic (cross-app)
- Visual system is fragmented across pages. Landing uses a different type system than the app, Analysis and Replay introduce blue accents, and there is an unused legacy design system file. Unify fonts, colors, and component density.
- Navigation is inconsistent. The sidebar shows duplicative or mismatched labels, while Play and Analysis remove the global navigation entirely and rely on a single Exit button. Users lose global context.
- Board sizing is fixed-pixel and does not scale to viewport. Small screens lose content (Play sidebar disappears) without alternative access.
- Typography skews too small and overly uppercase in many panels, lowering scanability and perceived hierarchy.
- Settings are shallow. Toggles do not visibly alter board behavior or the UI.

## Page-specific diagnostic highlights
- Landing
  - Strong hero and visual but disconnected from in-app typography and visual system. No direct links to Learn or community.
- Play hub
  - Strong Play CTA but the setup flow is long and the bot selection does not communicate difficulty or ratings. Right sidebar vanishes on smaller screens without replacement.
- Match (Play)
  - Key context (time control, komi, difficulty) is not visible. Layout risks overflow at smaller widths.
- Learn hub
  - Clear structure but mid-sized layouts feel disjointed due to sidebars reflowing out of order.
- Lesson detail
  - Good feedback loop but instruction panel is narrow and navigation is linear only (no quick overview map).
- Analysis
  - Functional but dense. Too many panels in one column, no resizing, and accent color diverges from brand.
- History / Studies / Replay
  - Useful but lacks filtering/search and consistent action hierarchy. Replay uses a different accent color.

## Unified visual system (spec)
### Brand attributes
- Calm, strategic, tactile, and modern.
- A quiet study hall feel, not a generic dark UI.

### Typography
- Display (headlines): Cormorant Garamond or equivalent.
- UI text: DM Sans or equivalent.
- Data/analysis: JetBrains Mono (or similar) for tables and ratings.

### Color and tokens (dark-first)
Use these tokens in app.css (or align existing tokens to this intent):

:root
- --bg-0: #14110d
- --bg-1: #1d1914
- --bg-2: #262018
- --surface: #2c251c
- --text-1: #f3ede4
- --text-2: #c5b9aa
- --text-3: #8f8579
- --accent-play: #2fc36b
- --accent-learn: #e0a84b
- --accent-analyze: #4aa3a3
- --danger: #e05b5b
- --board-wood: #d7a868
- --board-wood-dark: #b98c52

### Surfaces and elevation
- Only 3 elevation levels: base, card, floating.
- Prefer subtle shadows over heavy borders. Use borders only for focus, selection, or structure.

### Motion
- Reserve stronger motion for key moments (matchmaking, win overlay).
- Keep micro-interactions fast and subtle.
- Respect prefers-reduced-motion.

### Component density
- Standardize button sizes (sm, md, lg) and chip styles.
- Reduce tiny uppercase labels; use a clearer type scale for hierarchy.

### Action
Either:
- Integrate the legacy design system in styles.css into the app, or
- Remove it to avoid drift. Do not leave parallel systems.

## Play hub wireframe (chess.com-style)
### Desktop layout
- Two-column grid: dominant left column + right rail.
- Right rail persists with Daily Puzzle, stats, and recent activity.

Desktop structure:
- Quick Play (large card with board size, time, opponent)
- Resume game
- Recent games
- Bot ladder (difficulty ladder with ratings)
- Right rail: Daily Puzzle, Stats, Learn Next

### Mobile layout
- Stack all modules in one column.
- Keep a sticky Play CTA.
- Collapse the right-rail content into expandable cards.

### Module behavior
- Board size and time control should be grouped as a single preset.
- Bot selection should show difficulty, style, and rating.
- Resume and Daily Puzzle should be visible within 1-2 scrolls.

## Responsive board + settings spec
### Board sizing
- The board must scale to container size, not fixed pixels.
- Suggested size formula:
  - boardPx = clamp(280px, min(92vw, 70vh), 720px)
- SVG should be width: 100%, height: auto, with viewBox preserved.
- Container should use aspect-ratio: 1 / 1.

### Input behavior
- Hover ghost only for pointer devices.
- For touch: single tap previews a ghost stone, second tap confirms.

### Settings wiring
- showCoordinates must hide/show coordinate labels.
- soundEffects must map to move/capture sounds.
- boardTheme should toggle at least two wood palettes.

### Accessibility
- Do not leave 361 focusable intersections. Use roving focus and aria-activedescendant.
- Timers should use tabular numerals.

## Analysis workspace layout
### Layout
- Fixed top bar (Back, Title, Save, Layout, Engine status).
- Left: board and navigation controls.
- Right: analysis rail with core panels.
- Bottom drawer: Move Table and Move Tree in tabs.

### Panel hierarchy (right rail)
- Engine status
- Top moves
- Win rate graph
- Move tree
- Annotations

### Interactions
- Add a Focus Board toggle to collapse the rail.
- Add a resize handle between board and rail.
- Use a subtle progress strip for analysis activity.

### Visual
- Use a teal or muted jade accent consistent with the system (avoid bright blue).

## Implementation plan (phased)
1) Visual system unification
   - Align typography and color tokens across landing and app.
2) Play hub restructure
   - Rebuild Play landing with new hierarchy and mobile support.
3) Responsive board + real settings
   - Make board scale and wire toggles.
4) Analysis layout
   - Implement board + rail + drawer structure and normalize accents.

## Success criteria
- One unified typographic system across all pages.
- Play hub usable on mobile without losing sidebar content.
- Board responsive from 320px to large desktop.
- Settings visibly affect the board and UI.
- Analysis workspace uses consistent color language and panel density.

## Open questions
- Which font pairing should we adopt: new pairing (Cormorant + DM Sans) or keep Instrument Sans?
- Do we want light mode support or remain dark-first only?
- Should bot ratings map to Elo or a simpler difficulty scale?

