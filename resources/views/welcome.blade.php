<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zaid Assistant | AI Productivity Assistant</title>
    <meta name="description" content="Zaid Assistant helps users manage tasks, reminders, schedules, and daily planning with AI.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://zaidassistant.id/">
    <link rel="icon" href="/favicon.ico">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Zaid Assistant">
    <meta property="og:title" content="Zaid Assistant | AI Productivity Assistant">
    <meta property="og:description" content="Manage schedules, tasks, reminders, and daily planning with an AI-powered productivity assistant.">
    <meta property="og:url" content="https://zaidassistant.id/">
    <meta property="og:image" content="https://zaidassistant.id/images/landing/og-zaid-assistant.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Zaid Assistant, AI-powered planning">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Zaid Assistant | AI Productivity Assistant">
    <meta name="twitter:description" content="Manage schedules, tasks, reminders, and daily planning with AI.">
    <meta name="twitter:image" content="https://zaidassistant.id/images/landing/og-zaid-assistant.png">

    <script>document.documentElement.classList.add('js')</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-[100dvh] overflow-x-hidden bg-[#0c0614] text-[#fbf8ff] selection:bg-violet-300 selection:text-[#160825]">
    <a href="#main-content" class="fixed left-4 top-4 z-50 -translate-y-24 rounded-full bg-violet-200 px-5 py-3 font-semibold text-[#160825] transition-transform duration-200 focus:translate-y-0">
        Skip to content
    </a>

    <div class="site-glow" aria-hidden="true"></div>
    <div class="site-grid" aria-hidden="true"></div>

    <header class="relative z-20 mx-auto w-full max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
        <nav aria-label="Primary navigation" class="flex min-h-16 items-center justify-between gap-4 rounded-full bg-[#160d22]/90 px-3 py-2 shadow-[0_16px_60px_rgba(61,25,94,0.28),inset_0_1px_0_rgba(255,255,255,0.09)] ring-1 ring-white/10 backdrop-blur-xl sm:px-4">
            <a href="/" class="logo-only focus-ring" aria-label="Zaid Assistant home">
                <img src="/images/brand/zaid-logo.png" alt="">
            </a>

            <div class="hidden items-center gap-1 lg:flex">
                <a class="nav-link" href="#about">About</a>
                <a class="nav-link" href="#google-integration">Google Sign-In</a>
                <a class="nav-link" href="#security">Security</a>
                <a class="nav-link" href="#faq">FAQ</a>
            </div>

            <a href="/app" class="button-primary min-h-11 whitespace-nowrap px-5 sm:px-6">Get Started</a>
        </nav>
    </header>

    <main id="main-content" class="relative z-10">
        <section class="mx-auto grid min-h-[calc(100dvh-5rem)] w-full max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 md:py-20 lg:grid-cols-[0.9fr_1.1fr] lg:px-8 lg:py-16">
            <div class="hero-copy max-w-2xl">
                <p class="hero-enter mb-5 text-sm font-semibold text-violet-200">Your day, directed with intent.</p>
                <h1 class="hero-enter text-balance text-5xl font-semibold leading-[0.94] tracking-[-0.065em] sm:text-6xl lg:text-7xl">
                    Zaid <span class="text-violet-200">Assistant</span>
                </h1>
                <p class="hero-enter mt-6 max-w-[58ch] text-pretty text-lg leading-8 text-[#d7cde2]">
                    An AI-powered productivity assistant that helps users manage schedules, calendars, tasks, reminders, and daily planning.
                </p>
                <div class="hero-enter mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="/app" class="button-primary min-h-12 px-7">Get Started</a>
                    <a href="/privacy" class="button-secondary min-h-12 px-6">Privacy Policy</a>
                </div>
            </div>

            <div class="flow-shell hero-visual-enter" role="img" aria-label="User signs in through Google OAuth, then manages work in Zaid Assistant">
                <div class="flow-core">
                    <div class="mb-8 flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-violet-200">Secure connection flow</p>
                            <p class="mt-1 text-sm text-[#a99bb7]">Permission-based. User-directed.</p>
                        </div>
                        <span class="rounded-full bg-violet-300/10 px-3 py-1.5 text-xs font-medium text-violet-100 ring-1 ring-violet-200/15">OAuth 2.0</span>
                    </div>

                    <div class="oauth-flow">
                        <div class="flow-node">
                            <span class="flow-symbol" aria-hidden="true">U</span>
                            <strong>User</strong>
                            <small>Starts a request</small>
                        </div>
                        <div class="flow-connector" aria-hidden="true"><span></span></div>
                        <div class="flow-node flow-node-accent">
                            <span class="flow-symbol" aria-hidden="true">G</span>
                            <strong>Google OAuth</strong>
                            <small>Confirms access</small>
                        </div>
                        <div class="flow-connector" aria-hidden="true"><span></span></div>
                        <div class="flow-node">
                            <span class="flow-symbol" aria-hidden="true">Z</span>
                            <strong>Zaid Assistant</strong>
                            <small>Processes only your request</small>
                        </div>
                    </div>

                    <div class="mt-6 destination-card">
                        <span class="destination-mark" aria-hidden="true">Z</span>
                        <div><strong>Your Zaid workspace</strong><small>Tasks, schedules, and reminders</small></div>
                    </div>
                </div>
            </div>
        </section>

        <section id="about" class="section-space scroll-mt-24">
            <div class="section-container grid gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:gap-20">
                <div data-reveal>
                    <p class="section-kicker">About Zaid Assistant</p>
                    <h2 class="section-title">One assistant for the work around your work.</h2>
                    <p class="section-copy">Zaid Assistant turns everyday requests into organized, user-approved actions across planning tools you already use.</p>
                </div>
                <div class="capability-list">
                    @foreach ([
                        ['01', 'Organize daily schedules', 'Turn priorities into a clear plan for the day.'],
                        ['02', 'Manage tasks', 'Keep work organized, current, and easy to complete.'],
                        ['03', 'Create reminders', 'Capture time-sensitive commitments before they are missed.'],
                        ['04', 'Increase productivity using AI', 'Translate natural requests into useful planning actions.'],
                    ] as [$number, $title, $copy])
                        <article data-reveal class="capability-row">
                            <span class="tabular-nums text-sm text-violet-200">{{ $number }}</span>
                            <div>
                                <h3 class="text-lg font-semibold tracking-[-0.025em]">{{ $title }}</h3>
                                <p class="mt-1 text-sm leading-6 text-[#aa9db7]">{{ $copy }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="google-integration" class="section-space scroll-mt-24">
            <div class="section-container">
                <div data-reveal class="integration-panel">
                    <div class="max-w-2xl">
                        <p class="section-kicker">Google Integration</p>
                        <h2 class="section-title">Why Google Sign-In is required</h2>
                        <p class="section-copy">We use Google Sign-In only to securely authenticate users and identify their Zaid account.</p>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <article class="integration-point"><span>01</span><h3>Authenticate</h3><p>Google Sign-In confirms your identity securely.</p></article>
                        <article class="integration-point"><span>02</span><h3>Start planning</h3><p>Manage tasks, schedules, and reminders in Zaid.</p></article>
                    </div>
                </div>
            </div>
        </section>

        <section id="how-it-works" class="section-space scroll-mt-24">
            <div class="section-container">
                <div data-reveal class="max-w-3xl">
                    <h2 class="section-title">How it works</h2>
                    <p class="section-copy">From authentication to completion, you remain in control of each action.</p>
                </div>
                <ol class="workflow-grid">
                    @foreach ([
                        ['Sign in securely', 'Use your Google account to authenticate.'],
                        ['Verify your phone', 'Confirm your phone number with a one-time code.'],
                        ['Make a request', 'Ask Zaid Assistant to create schedules, reminders, or tasks.'],
                        ['Review your plan', 'Manage your work in Zaid Assistant.'],
                    ] as $index => [$title, $copy])
                        <li data-reveal class="workflow-step">
                            <span class="workflow-number">{{ $index + 1 }}</span>
                            <h3>{{ $title }}</h3>
                            <p>{{ $copy }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        <section id="security" class="section-space scroll-mt-24">
            <div class="section-container grid items-start gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
                <div data-reveal class="security-message">
                    <span class="security-lock" aria-hidden="true">Z</span>
                    <p class="section-kicker">Privacy & Security</p>
                    <h2 class="section-title">Your account stays under your control.</h2>
                    <p class="section-copy">Zaid uses Google Sign-In only to authenticate your account.</p>
                </div>
                <ul data-reveal class="security-list">
                    @foreach ([
                        'OAuth 2.0 authentication',
                        'Secure HTTPS communication',
                        'User data is never sold',
                        'Google Sign-In is used only for authentication',
                        'Tasks and schedules stay in Zaid',
                    ] as $item)
                        <li><span aria-hidden="true">✓</span>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section id="features" class="section-space scroll-mt-24">
            <div class="section-container">
                <div data-reveal class="max-w-3xl">
                    <p class="section-kicker">Core capabilities</p>
                    <h2 class="section-title">Built for a more organized day.</h2>
                </div>
                <div class="feature-grid">
                    <article data-reveal class="feature-cell feature-wide feature-tint"><span>AI</span><h3>AI Scheduling</h3><p>Turn natural language into structured plans.</p></article>
                    <article data-reveal class="feature-cell"><span>✓</span><h3>Task Management</h3><p>Organize and complete tasks in Zaid.</p></article>
                    <article data-reveal class="feature-cell"><span>31</span><h3>Schedule Planning</h3><p>Keep daily priorities clear and practical.</p></article>
                    <article data-reveal class="feature-cell feature-tall feature-tint"><span>⌁</span><h3>Smart Reminders</h3><p>Keep important commitments visible at the right time.</p></article>
                    <article data-reveal class="feature-cell"><span>24</span><h3>Daily Planning</h3><p>Shape priorities into a practical schedule.</p></article>
                    <article data-reveal class="feature-cell feature-wide"><span>▦</span><h3>Productivity Dashboard</h3><p>See plans, tasks, and upcoming work in one place.</p></article>
                </div>
            </div>
        </section>

        <section id="faq" class="section-space scroll-mt-24">
            <div class="section-container grid gap-12 lg:grid-cols-[0.72fr_1.28fr] lg:gap-20">
                <div data-reveal>
                    <p class="section-kicker">Frequently Asked Questions</p>
                    <h2 class="section-title">Clear answers about access.</h2>
                </div>
                <div data-reveal class="faq-list">
                    @foreach ([
                        ['Why does Zaid Assistant use Google Sign-In?', 'Google Sign-In securely authenticates your Zaid account.'],
                        ['Does Zaid access my Google Calendar or Tasks?', 'No. Zaid does not request or use Google Calendar or Google Tasks access.'],
                        ['Can I remove Google Sign-In access?', 'Yes. You can revoke Zaid Assistant access at any time from your Google Account permissions page.'],
                        ['Is my data secure?', 'Zaid uses OAuth 2.0 and HTTPS. We do not sell user data, and data is used only to provide requested features.'],
                    ] as [$question, $answer])
                        <details class="faq-item">
                            <summary><span>{{ $question }}</span><span class="faq-icon" aria-hidden="true">+</span></summary>
                            <p>{{ $answer }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="contact" class="section-space scroll-mt-24">
            <div class="section-container">
                <div data-reveal class="contact-panel">
                    <div>
                        <p class="section-kicker">Contact</p>
                        <h2 class="section-title">Questions about Zaid Assistant?</h2>
                        <p class="section-copy">Contact the developer for product, privacy, or account questions.</p>
                    </div>
                    <address class="not-italic">
                        <a href="mailto:zaidassistant@gmail.com" class="contact-link"><span>Developer Email</span><strong>zaidassistant@gmail.com</strong></a>
                        <a href="https://zaidassistant.id" class="contact-link"><span>Website</span><strong>https://zaidassistant.id</strong></a>
                    </address>
                </div>
            </div>
        </section>
    </main>

    <footer class="relative z-10 px-4 pb-8 sm:px-6 lg:px-8">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 rounded-3xl bg-[#150c20] px-6 py-7 ring-1 ring-white/8 md:flex-row md:items-center md:justify-between">
            <div class="wordmark-footer">
                <span class="footer-brand-row"><img src="/images/brand/zaid-logo.png" alt=""></span>
                <span>Copyright © 2026 Zaid Assistant</span>
            </div>
            <nav aria-label="Legal navigation" class="flex flex-wrap gap-x-5 gap-y-3 text-sm text-[#c8bdd2]">
                <a class="footer-link" href="/privacy">Privacy Policy</a>
                <a class="footer-link" href="/terms">Terms of Service</a>
                <a class="footer-link" href="mailto:zaidassistant@gmail.com">Contact</a>
            </nav>
        </div>
    </footer>
</body>
</html>
