# Zaid Landing Page Parallax Redesign Plan

## Context

Current landing page lives in `resources/views/welcome.blade.php` and is a single Blade file with inline CSS. The page already has a dark glassmorphism look, but it still feels static and monotone because the composition is mostly two cards, purple gradients, and minimal motion.

Goal: redesign the landing page into a more memorable, premium, parallax-driven experience while keeping the page lightweight and suitable for a Laravel public landing page.

## Proposed Theme: "Chrono Command Center"

A futuristic productivity cockpit inspired by time, orbit, calendar grids, and AI command flows.

Visual direction:
- Deep midnight navy / graphite base, not plain black.
- Electric lime, warm amber, and cyan accents instead of purple-dominant gradients.
- Layered orbital rings, timeline rails, calendar tiles, and floating task cards.
- A central "AI schedule engine" visual with parallax depth.
- Editorial typography with stronger personality: display serif for hero emphasis plus clean sans for body.

Theme keywords:
- Time orchestration
- AI cockpit
- Calendar orbit
- Calm but powerful
- Premium SaaS, not generic dashboard

## Experience Goals

- Make first fold feel alive through layered parallax elements.
- Communicate Zaid clearly: AI assistant for tasks, schedules, reminders, Google auth, phone verification, optional calendar sync.
- Preserve legal/access links: `/privacy`, `/terms`, mail contact, and app flow link.
- Keep implementation simple: Blade + CSS + small vanilla JavaScript, no extra packages required.
- Support desktop and mobile; reduce motion for users with `prefers-reduced-motion`.

## Page Structure

1. Navigation / Header
   - Zaid wordmark.
   - Small status pill: "AI productivity assistant".
   - Links: Privacy, Terms, Contact.
   - CTA: Open App Flow.

2. Hero Section
   - Large headline in Indonesian/English hybrid, for example:
     "Atur hari yang ramai jadi alur kerja yang tenang."
   - Supporting copy explaining task, agenda, reminder, Google login, phone verification, and optional calendar sync.
   - Primary CTA: Open App Flow.
   - Secondary CTA: Read Privacy.
   - Visual cockpit with floating task cards, orbital calendar ring, and AI command prompt.

3. Parallax Proof Section
   - Three floating cards:
     - Capture tasks naturally.
     - Sync schedules optionally.
     - Verify identity securely.
   - Cards move at different speeds on scroll.

4. Workflow Section
   - Step timeline:
     - Login with Google.
     - Verify phone.
     - Create/manage tasks.
     - Connect calendar if needed.
   - Each step represented by a custom CSS icon / badge.

5. Trust / Privacy Section
   - Clear data-use messaging:
     - Google account access for auth.
     - Phone number for verification.
     - Calendar access only when user opts in.
   - Keep compliance messaging visible and non-ambiguous.

6. Final CTA
   - Short final statement and buttons for app, privacy, terms.

## Parallax Plan

CSS-only layers:
- Background grid with slow animated drift.
- Orbital rings using pseudo-elements.
- Floating cards with `transform: translate3d()`.
- Decorative time markers and small glowing dots.

JavaScript-enhanced parallax:
- Add a tiny script listening to scroll and pointer movement.
- Use CSS variables like `--scroll-y`, `--pointer-x`, `--pointer-y`.
- Apply different multipliers per element via `data-depth`.
- Disable or soften movement under `prefers-reduced-motion`.

Mobile behavior:
- Keep parallax subtle.
- Stack visual cards below hero text.
- Avoid fixed elements that cause scroll jank.

## Asset Preparation

No external image assets required for phase 1. Assets will be generated with CSS/HTML to keep page fast and easy to maintain.

CSS-generated assets:
- Orbital calendar ring.
- AI command card.
- Floating task chips.
- Timeline rail.
- Noise/mesh gradient background.
- Mini calendar grid.
- Security verification badge.

Optional static assets for later:
- `public/images/landing/zaid-command-center.svg` for reusable hero illustration.
- `public/images/landing/noise.png` if CSS noise is not visually enough.
- Open Graph image: `public/images/landing/og-zaid-landing.png`.

## Implementation Phases

### Phase 1 - Foundation and Theme

Deliverables:
- Replace current monotone glass layout with new visual system.
- Define CSS variables for colors, fonts, spacing, shadows, and motion.
- Add page sections and responsive layout.
- Keep all copy accurate for current product behavior.

Acceptance criteria:
- Landing page loads without build changes.
- No new npm dependencies.
- Links to `/app`, `/privacy`, `/terms`, and email remain accessible.
- Mobile layout is readable and polished.

### Phase 2 - Parallax and Motion

Deliverables:
- Add layered parallax elements in hero and proof cards.
- Add pointer-reactive visual cockpit on desktop.
- Add reveal animations for sections.
- Add reduced-motion fallback.

Acceptance criteria:
- Motion feels intentional and not distracting.
- No horizontal overflow.
- Page remains usable if JavaScript is disabled.

### Phase 3 - Content Polish and Trust Messaging

Deliverables:
- Refine Indonesian copy to sound premium and concise.
- Make privacy/security messaging clear for Google/phone/calendar access.
- Add mini FAQ or trust statements if needed.

Acceptance criteria:
- User understands what Zaid does in first 5 seconds.
- Data access explanation is visible and accurate.
- CTA hierarchy is clear.

### Phase 4 - QA and Optimization

Deliverables:
- Test page in desktop and mobile viewport.
- Run basic build/check if project setup supports it.
- Verify HTML semantics and accessibility basics.
- Check performance risks from animations.

Acceptance criteria:
- `npm run build` passes if dependencies are installed.
- Keyboard focus states are visible.
- Reduced motion mode works.
- Page has no obvious layout breakpoints.

## Files Expected to Change

Primary:
- `resources/views/welcome.blade.php`

Optional:
- `public/images/landing/*` only if static SVG/OG assets are approved.
- `resources/css/app.css` only if global landing styles are preferred over inline CSS.

## Risks / Notes

- Current page uses inline CSS; fastest implementation is to keep landing-specific CSS inline in `welcome.blade.php`.
- External font imports can slow initial load. If strict performance is required, use local/system fallback or self-host fonts later.
- Heavy scroll effects can feel janky on low-end mobile, so parallax should be restrained and disabled when appropriate.

## Recommended Next Step

Approve Phase 1 and Phase 2 together for a complete first redesign pass, then review copy and visual tone before Phase 3 polish.
