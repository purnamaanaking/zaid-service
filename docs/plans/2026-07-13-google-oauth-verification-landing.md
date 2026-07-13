# Zaid Assistant Google OAuth Verification Landing Implementation Plan

> **REQUIRED SUB-SKILL:** Use executing-plans skill to implement this plan task-by-task.

**Goal:** Build public, responsive, professional Zaid Assistant homepage that clearly explains product functionality, Google Sign-In, requested permissions, privacy practices, and developer identity for Google OAuth Verification.

**Architecture:** Keep existing Laravel Blade + Tailwind v4 stack. Replace homepage-specific inline CSS and scroll-loop JavaScript with semantic Blade markup, Tailwind utilities, small shared CSS motion layer, and one dependency-free `IntersectionObserver` enhancement. Preserve `/`, `/app`, `/privacy`, and `/terms` routes.

**Tech Stack:** Laravel Blade, Tailwind CSS v4, Vite 8, vanilla JavaScript, PHPUnit feature tests.

---

## Applied design disciplines

- **Brainstorming:** Existing dark-purple brand stays. Page purpose changes from decorative product showcase to trust-first OAuth verification surface.
- **Design Taste Frontend:** `DESIGN_VARIANCE 5`, `MOTION_INTENSITY 4`, `VISUAL_DENSITY 4`. Asymmetric hero, varied section layouts, no repeated equal-card template, no AI-purple glow overload.
- **High-End Visual Design:** Ethereal dark glass used only where hierarchy needs it. Concentric radii, tinted shadows, one purple accent family, large whitespace, refined button internals.
- **Redesign Existing Projects:** Preserve routes, app flow, legal links, existing dark identity, and current local font pipeline. Retire inline font imports, fake metrics, heavy parallax loop, decorative theme labels, and unclear data copy.
- **Motion Design:** Corporate-premium personality. Signature easing `cubic-bezier(0.23, 1, 0.32, 1)`. Durations: quick `140ms`, standard `260ms`, reveal `560ms`. Motion communicates hierarchy, flow, or feedback only.
- **Emil Design Engineering:** CSS transitions for interruptible interaction, `active:scale-[0.96]`, no `transition: all`, no `scale(0)`, hover gated to fine pointers, reduced-motion support.
- **Make Interfaces Feel Better:** Text balancing, antialiasing, minimum 40px hit areas, optical icon alignment, concentric radii, exact transition properties, restrained `will-change`.
- **Lazy/YAGNI rule:** No React, Motion, GSAP, icon package, carousel, theme toggle, analytics, or new dependency. Existing stack covers requirement.

## Visual system

### Brand tokens

- Page base: deep aubergine-black, never pure black.
- Surfaces: two dark-purple elevations with low-opacity white highlights.
- Accent: one lavender-purple family retained from current brand.
- Text: warm off-white primary, pale lavender secondary, muted cool-lilac tertiary.
- Radius rule: cards `24px`, nested visual cores `18px`, controls full-pill.
- Shadow rule: purple-tinted ambient shadows, no generic black `shadow-md`.
- Typography: existing self-hosted Instrument Sans from Vite font plugin. No remote Google Font request.

### Layout sequence

1. Floating single-line navigation.
2. Asymmetric split hero with OAuth flow visual.
3. About section using editorial statement plus five capability rows.
4. Google Integration split panel.
5. Requested Permissions asymmetric 2x2 grid.
6. How It Works connected four-stage flow.
7. Privacy & Security split checklist and trust statement.
8. Feature mosaic with six exact cells and varied spans.
9. FAQ accessible native disclosures.
10. Contact block with developer email and website.
11. Compact legal footer.

### Motion identity

- Hero copy: opacity + `translateY(18px)`, staged at 70ms intervals, total under 420ms stagger budget.
- OAuth flow: connectors reveal left-to-right once, visually teaching data path.
- Sections: opacity + `translateY(20px)` once when 15% visible.
- Cards: fine-pointer hover only, `translateY(-4px)` and tinted shadow shift.
- Buttons: press scale `0.96` in `140ms`; trailing arrow moves 2px on hover.
- FAQ: native `<details>` state, icon rotates with CSS; no height animation.
- Reduced motion: remove translation, connector travel, ambient movement, and stagger delay. Keep short opacity/color feedback.
- No scroll listener, RAF loop, parallax pointer tracking, layout-property animation, or infinite marquee.

## Content rules

- Visible page language: English, matching Google reviewer brief.
- Product name: `Zaid Assistant` everywhere.
- Public contact: `zaidassistant@gmail.com` everywhere.
- Website: `https://zaidassistant.id`.
- Primary CTA: `Get Started`, route `/app`.
- Secondary CTA: `Privacy Policy`, route `/privacy`.
- Exact trust statement: `We never access, modify, or share user data without user interaction or permission.`
- Permissions must describe direct user-requested actions, not background or autonomous access.
- Google API policy link: `https://developers.google.com/terms/api-services-user-data-policy`.
- No unsupported claims, fake metrics, customer logos, testimonials, or certification badges.

---

### Task 1: Add homepage compliance feature test

**Files:**
- Create: `tests/Feature/LandingPageTest.php`

**Step 1: Write failing public-page test**

Create tests covering:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_homepage_is_public_and_contains_oauth_verification_content(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Zaid Assistant')
            ->assertSee('Manage Google Calendar events')
            ->assertSee('Manage Google Tasks')
            ->assertSee('Why Google Sign-In is required')
            ->assertSee('Requested permissions')
            ->assertSee('Google Calendar')
            ->assertSee('Google Tasks')
            ->assertSee('User Profile')
            ->assertSee('Email Address')
            ->assertSee('We never access, modify, or share user data without user interaction or permission.')
            ->assertSee('OAuth 2.0 authentication')
            ->assertSee('How it works')
            ->assertSee('zaidassistant@gmail.com')
            ->assertSee('https://zaidassistant.id')
            ->assertSee('Google API Services User Data Policy');
    }

    public function test_homepage_exposes_required_public_links(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('href="/app"', false)
            ->assertSee('href="/privacy"', false)
            ->assertSee('href="/terms"', false)
            ->assertSee('href="mailto:zaidassistant@gmail.com"', false)
            ->assertSee('href="https://developers.google.com/terms/api-services-user-data-policy"', false);
    }
}
```

**Step 2: Run test and confirm failure**

Run:

```bash
php artisan test tests/Feature/LandingPageTest.php
```

Expected: test fails because current homepage lacks required OAuth sections, exact contact email, and Google API policy link.

**Step 3: Keep test narrow**

Do not snapshot full HTML. Assert public access, required copy, and real destinations only. Visual structure belongs to browser checks.

**Step 4: Commit test**

```bash
git add tests/Feature/LandingPageTest.php
git commit -m "test: cover OAuth verification homepage"
```

---

### Task 2: Build semantic homepage shell and metadata

**Files:**
- Modify: `resources/views/welcome.blade.php`

**Step 1: Replace document head**

Add:

- `<html lang="en">`
- UTF-8 and responsive viewport.
- Title: `Zaid Assistant | AI Productivity Assistant`.
- Meta description matching product functionality.
- Canonical URL `https://zaidassistant.id/`.
- `robots` value `index, follow`.
- Open Graph title, description, URL, type, site name, and image.
- Twitter card metadata.
- Existing `@vite(['resources/css/app.css', 'resources/js/app.js'])`.
- Favicon `/favicon.ico`.

Use this metadata copy:

```html
<title>Zaid Assistant | AI Productivity Assistant</title>
<meta name="description" content="Zaid Assistant helps users manage Google Calendar events, Google Tasks, reminders, schedules, and daily planning with AI.">
<link rel="canonical" href="https://zaidassistant.id/">
<meta name="robots" content="index, follow">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Zaid Assistant">
<meta property="og:title" content="Zaid Assistant | AI Productivity Assistant">
<meta property="og:description" content="Manage schedules, calendar events, tasks, reminders, and daily planning with an AI-powered productivity assistant.">
<meta property="og:url" content="https://zaidassistant.id/">
<meta property="og:image" content="https://zaidassistant.id/images/landing/og-zaid-assistant.png">
<meta name="twitter:card" content="summary_large_image">
```

**Step 2: Add accessibility shell**

- Skip link targets `#main-content`.
- `<header>`, `<nav aria-label="Primary navigation">`, `<main id="main-content">`, `<section>`, `<article>`, `<footer>`.
- One H1 only.
- H2 for each major section.
- H3 for permission, feature, and FAQ labels.
- Visible focus treatment for every link and disclosure.
- No content hidden behind login.

**Step 3: Build floating navigation**

Navigation links:

- About: `#about`
- Google Integration: `#google-integration`
- Permissions: `#permissions`
- Security: `#security`
- FAQ: `#faq`
- Get Started: `/app`

Desktop stays one line under 80px. Mobile hides secondary anchors and keeps brand plus CTA, avoiding JavaScript menu complexity.

**Step 4: Run focused test**

```bash
php artisan test tests/Feature/LandingPageTest.php
```

Expected: still fails until all body sections exist, but `/` remains HTTP 200.

**Step 5: Commit shell**

```bash
git add resources/views/welcome.blade.php
 git commit -m "feat: add OAuth landing shell and metadata"
```

---

### Task 3: Build hero and OAuth data-flow illustration

**Files:**
- Modify: `resources/views/welcome.blade.php`

**Step 1: Build hero copy**

Hero content:

- Brand logo mark and `Zaid Assistant` wordmark in nav.
- H1: `Zaid Assistant`.
- Subtitle: `An AI-powered productivity assistant that helps users manage schedules, calendars, tasks, reminders, and daily planning.`
- Primary button: `Get Started` to `/app`.
- Secondary button: `Privacy Policy` to `/privacy`.

Keep hero text left-aligned. Initial viewport includes H1, subtitle, and both CTAs.

**Step 2: Build real explanatory illustration**

Use semantic HTML and CSS, not decorative fake dashboard:

```text
User
  Google Sign-In
Google OAuth
  permission grant
Zaid Assistant
  requested action only
Google Calendar / Google Tasks
```

Requirements:

- Four nodes in desktop horizontal/branched flow.
- Mobile vertical flow.
- Each node has text label, short functional caption, and CSS geometric mark.
- Final node splits into Calendar and Tasks destinations.
- Connectors remain understandable without animation.
- `role="img"` with concise `aria-label` on wrapper, decorative connector elements `aria-hidden="true"`.

**Step 3: Add hero motion hooks**

- `data-reveal="hero"` for semantic chunks.
- `data-flow-line` for connector reveal.
- No pointer tracking.
- No constant orbit.

**Step 4: Verify hero constraints manually**

At 1440x900 and 1280x720:

- H1 maximum two lines.
- Subtitle maximum four lines.
- Both CTAs visible without scroll.
- Nav one line.

At 390x844:

- Hero becomes one column.
- CTA labels do not wrap.
- Flow becomes vertical.
- No horizontal overflow.

**Step 5: Commit hero**

```bash
git add resources/views/welcome.blade.php
git commit -m "feat: explain Zaid and Google OAuth flow"
```

---

### Task 4: Add product, integration, and permission content

**Files:**
- Modify: `resources/views/welcome.blade.php`

**Step 1: Add About section**

Section ID: `about`.

Explain Zaid Assistant helps users:

- Manage Google Calendar events.
- Manage Google Tasks.
- Organize daily schedules.
- Create reminders.
- Increase productivity using AI.

Use one editorial statement plus five capability rows, not five identical cards.

**Step 2: Add Google Integration section**

Section ID: `google-integration`.

Required statement:

`We use Google Sign-In to securely authenticate users and connect their Google account to provide personalized productivity features.`

Add distinction:

- Sign-In confirms identity.
- Calendar and Tasks permissions enable requested productivity actions.
- Users control when Zaid performs an action.

**Step 3: Add Requested Permissions section**

Section ID: `permissions`.

Use asymmetric two-column grid with four exact permission cards:

1. **Google Calendar**
   `Used to create, edit, update, and delete calendar events requested directly by the user.`
2. **Google Tasks**
   `Used to create, edit, organize, complete, and delete Google Tasks requested directly by the user.`
3. **User Profile**
   `Used only for authentication and displaying the user's basic profile information.`
4. **Email Address**
   `Used to identify the user's account and provide personalized services.`

End with prominent exact statement:

`We never access, modify, or share user data without user interaction or permission.`

**Step 4: Check copy accuracy**

Search banned or unsupported claims:

```bash
rg -n "always secure|100% secure|certified|autonomous|background access|without permission|Chrono Command Center|phone verification" resources/views/welcome.blade.php
```

Expected: no unsupported marketing claim, old theme label, or unrelated phone-verification emphasis.

**Step 5: Commit content**

```bash
git add resources/views/welcome.blade.php
git commit -m "feat: document Google permissions and data use"
```

---

### Task 5: Add workflow, security, features, FAQ, and contact

**Files:**
- Modify: `resources/views/welcome.blade.php`

**Step 1: Add How It Works section**

Section ID: `how-it-works`.

Use direct verb labels instead of generic numbered headings:

- `Sign in securely` with supporting text: `Use your Google account to authenticate.`
- `Grant selected access` with supporting text: `Approve Google Calendar and Google Tasks permissions.`
- `Make a request` with supporting text: `Ask Zaid Assistant to create schedules, reminders, or tasks.`
- `Complete the action` with supporting text: `Zaid Assistant performs only the action you request.`

Show small `1` to `4` progression numerals as visual support, not headings.

**Step 2: Add Privacy & Security section**

Section ID: `security`.

Required points:

- OAuth 2.0 authentication.
- Secure HTTPS communication.
- User data is never sold.
- Data is only used to provide requested features.
- Users can revoke Google access at any time.

Link revocation text to `https://myaccount.google.com/permissions` in new tab with `rel="noopener noreferrer"`.

**Step 3: Add six-feature mosaic**

Section ID: `features`.

Exact items:

- AI Scheduling.
- Google Calendar Sync.
- Google Tasks Sync.
- Smart Reminders.
- Daily Planning.
- Productivity Dashboard.

Use six cells with mixed spans at desktop and one column on mobile. Vary surface tint in two or three cells. Do not use six equal white-on-white cards.

**Step 4: Add FAQ with native disclosures**

Section ID: `faq`.

Use `<details><summary>` for keyboard and no-JS accessibility.

Questions:

- Why does Zaid Assistant need Google Calendar access?
- Why does it need Google Tasks access?
- Can I revoke access later?
- Is my data secure?

Answers remain specific, short, and consistent with permissions copy.

**Step 5: Add Contact section and footer**

Contact:

- Developer Email: `zaidassistant@gmail.com` using `mailto:`.
- Website: `https://zaidassistant.id` using absolute HTTPS URL.

Footer links:

- Privacy Policy: `/privacy`.
- Terms of Service: `/terms`.
- Google API Services User Data Policy: official Google URL.
- Contact: `mailto:zaidassistant@gmail.com`.
- `Copyright © 2026 Zaid Assistant`.

**Step 6: Run feature test**

```bash
php artisan test tests/Feature/LandingPageTest.php
```

Expected: PASS.

**Step 7: Commit sections**

```bash
git add resources/views/welcome.blade.php
git commit -m "feat: complete OAuth verification landing content"
```

---

### Task 6: Implement Tailwind visual system and motion layer

**Files:**
- Modify: `resources/css/app.css`
- Modify: `resources/js/app.js`
- Modify: `resources/views/welcome.blade.php`

**Step 1: Extend global CSS with semantic utilities**

Keep existing Tailwind imports and font theme. Add only rules Tailwind markup cannot express cleanly:

```css
:root {
    --zaid-ease-out: cubic-bezier(0.23, 1, 0.32, 1);
    --zaid-quick: 140ms;
    --zaid-standard: 260ms;
    --zaid-reveal: 560ms;
}

html {
    scroll-behavior: smooth;
}

body {
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

h1,
h2,
h3 {
    text-wrap: balance;
}

p,
li {
    text-wrap: pretty;
}

[data-reveal] {
    opacity: 0;
    transform: translateY(20px);
    transition:
        opacity var(--zaid-reveal) var(--zaid-ease-out),
        transform var(--zaid-reveal) var(--zaid-ease-out);
}

[data-reveal].is-visible {
    opacity: 1;
    transform: translateY(0);
}

@media (prefers-reduced-motion: reduce) {
    html { scroll-behavior: auto; }
    [data-reveal] {
        opacity: 1;
        transform: none;
        transition: opacity 160ms ease;
    }
}
```

Do not add `transition: all`, moving grain layer, large scrolling backdrop blur, or permanent `will-change`.

**Step 2: Use Tailwind for all layout and surfaces**

- `max-w-7xl mx-auto` container.
- Explicit single-column mobile fallback for every grid.
- `min-h-[100dvh]`, never `h-screen`.
- One dark theme throughout.
- Cards `rounded-3xl`, nested content `rounded-2xl`, buttons `rounded-full`.
- Fine hairlines via low-opacity ring/highlight, not harsh gray border.
- Focus ring uses visible lavender and offset matching dark background.
- CTA hit areas at least 44px high.
- Exact transition utilities or custom classes only.

**Step 3: Replace JavaScript with one observer**

`resources/js/app.js`:

```js
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const revealTargets = document.querySelectorAll('[data-reveal]');

if (reducedMotion || !('IntersectionObserver' in window)) {
    revealTargets.forEach((target) => target.classList.add('is-visible'));
} else {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.15 });

    revealTargets.forEach((target) => observer.observe(target));
}
```

No `window.addEventListener('scroll')`, pointer loop, RAF loop, or dependency.

**Step 4: Add no-JS visibility safeguard**

Default content must remain visible if scripts fail. Add `js` class in tiny head bootstrap before stylesheet or invert enhancement logic so hidden reveal state only applies under `.js [data-reveal]`.

Preferred:

```html
<script>document.documentElement.classList.add('js')</script>
```

Then scope CSS:

```css
.js [data-reveal] { ... }
.js [data-reveal].is-visible { ... }
```

**Step 5: Build assets**

```bash
npm run build
```

Expected: Vite exits 0 and writes compiled CSS/JS under `public/build`.

**Step 6: Commit styling and motion**

```bash
git add resources/css/app.css resources/js/app.js resources/views/welcome.blade.php public/build
git commit -m "style: polish OAuth landing motion and surfaces"
```

---

### Task 7: Align legal contact details

**Files:**
- Modify: `resources/views/privacy.blade.php`
- Modify: `resources/views/terms.blade.php`

**Step 1: Replace old email**

Change every occurrence:

```text
zaidassist@gmail.com
```

to:

```text
zaidassistant@gmail.com
```

**Step 2: Preserve legal content**

Do not rewrite policy terms or alter legal meaning during landing task. Only align public developer identity and email.

**Step 3: Verify no old email remains**

```bash
rg -n "zaidassist@gmail.com" resources public routes tests
```

Expected: no matches.

```bash
rg -n "zaidassistant@gmail.com" resources/views
```

Expected: matches homepage, privacy, and terms.

**Step 4: Commit contact alignment**

```bash
git add resources/views/privacy.blade.php resources/views/terms.blade.php
git commit -m "fix: align public developer contact"
```

---

### Task 8: Add and verify Open Graph asset

**Files:**
- Create: `public/images/landing/og-zaid-assistant.png`
- Modify: `resources/views/welcome.blade.php` only if final asset path differs.

**Step 1: Create static 1200x630 image**

Use existing brand mark and landing visual direction:

- Dark aubergine base.
- Zaid Assistant wordmark.
- Short line: `AI-powered planning with Google Calendar and Tasks`.
- Simplified OAuth flow nodes.
- High contrast at social-preview size.
- No permissions claim omitted or distorted.

If image generation tool unavailable during implementation, create asset using local image tooling only if available. Otherwise keep task blocked and do not ship a broken `og:image` URL.

**Step 2: Verify dimensions and existence**

```bash
file public/images/landing/og-zaid-assistant.png
```

Expected: PNG image data, 1200 x 630.

**Step 3: Verify public URL locally**

```bash
php artisan serve --host=127.0.0.1 --port=8000
curl -I http://127.0.0.1:8000/images/landing/og-zaid-assistant.png
```

Expected: HTTP 200 and image content type.

**Step 4: Commit asset**

```bash
git add public/images/landing/og-zaid-assistant.png resources/views/welcome.blade.php
git commit -m "feat: add Zaid social preview"
```

---

### Task 9: Run final verification and pre-flight audit

**Files:**
- Test: `tests/Feature/LandingPageTest.php`
- Inspect: `resources/views/welcome.blade.php`
- Inspect: `resources/css/app.css`
- Inspect: `resources/js/app.js`

**Step 1: Run focused feature tests**

```bash
php artisan test tests/Feature/LandingPageTest.php
```

Expected: PASS.

**Step 2: Run full application test suite**

```bash
php artisan test
```

Expected: all existing tests PASS. Any DB or environment blocker must be reported exactly, not described as passing.

**Step 3: Run production build**

```bash
npm run build
```

Expected: Vite build exits 0.

**Step 4: Check required text and forbidden patterns**

```bash
rg -n "Zaid Assistant|Google Calendar|Google Tasks|User Profile|Email Address|OAuth 2.0|zaidassistant@gmail.com|Google API Services User Data Policy" resources/views/welcome.blade.php
rg -n "transition:\s*all|addEventListener\(['\"]scroll|requestAnimationFrame|h-screen|href=[\"']#[\"']|—|–|Chrono Command Center|zaidassist@gmail.com" resources/views/welcome.blade.php resources/css/app.css resources/js/app.js
```

Expected:

- Required content search returns all categories.
- Forbidden pattern search returns no matches.
- Hash anchors with real IDs are allowed; empty `href="#"` is not.

**Step 5: Browser accessibility and responsive audit**

Check at minimum:

- 390x844 mobile.
- 768x1024 tablet.
- 1280x720 small laptop.
- 1440x900 desktop.
- 200% browser zoom.
- Keyboard-only navigation.
- `prefers-reduced-motion: reduce`.
- JavaScript disabled.

Acceptance checklist:

- Homepage loads without authentication.
- No horizontal scroll.
- Nav stays under 80px and one line on desktop.
- Hero CTA visible in initial laptop viewport.
- H1 appears once; heading order stays logical.
- Focus state visible on every interactive control.
- Body contrast reaches WCAG AA.
- FAQ works by keyboard and without JavaScript.
- Motion never blocks reading or clicking.
- Reduced motion removes spatial movement.
- All footer destinations resolve.
- No remote font request.
- No content flash remains permanently hidden.

**Step 6: Run Lighthouse**

Use Chrome Lighthouse against production-mode local page. Targets:

- Performance: 90 or higher.
- Accessibility: 95 or higher.
- Best Practices: 95 or higher.
- SEO: 95 or higher.
- LCP under 2.5s.
- CLS under 0.1.

Record real scores in completion report. Do not invent scores if Lighthouse cannot run.

**Step 7: Final commit**

```bash
git add resources/views/welcome.blade.php resources/css/app.css resources/js/app.js resources/views/privacy.blade.php resources/views/terms.blade.php tests/Feature/LandingPageTest.php public/build public/images/landing/og-zaid-assistant.png
git commit -m "feat: ship Google OAuth verification landing page"
```

---

## Deliberate omissions

- No new animation library: CSS + `IntersectionObserver` covers fixed choreography with less JavaScript and lower bundle cost.
- No dark/light toggle: existing brand decision fixes dark-purple theme.
- No login widget on homepage: public verification page must remain viewable without login.
- No cookie banner: add only when analytics, non-essential cookies, or jurisdiction requirements make it necessary.
- No schema markup: add `SoftwareApplication` JSON-LD only after public product details, pricing, and verified app metadata are stable.
- No custom mobile menu: current anchor count can collapse safely without interaction complexity.

## Completion definition

Page is complete only when required feature test, full Laravel test suite, and Vite build pass; public links resolve; responsive and keyboard checks pass; no-JS and reduced-motion fallbacks work; and Google OAuth scope explanations exactly match product behavior.
