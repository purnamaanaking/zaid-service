<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zaid Assistant | AI Productivity Assistant</title>
    <meta name="description" content="Zaid Assistant helps users manage Google Calendar events, Google Tasks, reminders, schedules, and daily planning with AI.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://zaidassistant.id/">
    <link rel="icon" href="/favicon.ico">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Zaid Assistant">
    <meta property="og:title" content="Zaid Assistant | AI Productivity Assistant">
    <meta property="og:description" content="Manage schedules, calendar events, tasks, reminders, and daily planning with an AI-powered productivity assistant.">
    <meta property="og:url" content="https://zaidassistant.id/">
    <meta property="og:image" content="https://zaidassistant.id/images/landing/og-zaid-assistant.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Zaid Assistant, AI-powered planning with Google Calendar and Google Tasks">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Zaid Assistant | AI Productivity Assistant">
    <meta name="twitter:description" content="Manage schedules, calendar events, tasks, reminders, and daily planning with AI.">
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
            <a href="/" class="wordmark focus-ring min-h-11 rounded-full" aria-label="Zaid Assistant home">
                <img src="/images/brand/zaid-logo.png" alt="">
                <span>Zaid</span><strong>Assistant</strong>
            </a>

            <div class="hidden items-center gap-1 lg:flex">
                <a class="nav-link" href="#about">About</a>
                <a class="nav-link" href="#google-integration">Google Integration</a>
                <a class="nav-link" href="#permissions">Permissions</a>
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

            <div class="flow-shell hero-visual-enter" role="img" aria-label="User signs in through Google OAuth, then Zaid Assistant performs requested actions in Google Calendar or Google Tasks">
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

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <div class="destination-card">
                            <span class="destination-mark" aria-hidden="true">31</span>
                            <div><strong>Google Calendar</strong><small>Events and schedules</small></div>
                        </div>
                        <div class="destination-card">
                            <span class="destination-mark" aria-hidden="true">✓</span>
                            <div><strong>Google Tasks</strong><small>Tasks and reminders</small></div>
                        </div>
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
                        ['01', 'Manage Google Calendar events', 'Create, update, and organize events from direct requests.'],
                        ['02', 'Manage Google Tasks', 'Keep tasks organized, current, and easy to complete.'],
                        ['03', 'Organize daily schedules', 'Turn priorities into a clear plan for the day.'],
                        ['04', 'Create reminders', 'Capture time-sensitive commitments before they are missed.'],
                        ['05', 'Increase productivity using AI', 'Translate natural requests into useful planning actions.'],
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
                        <p class="section-copy">We use Google Sign-In to securely authenticate users and connect their Google account to provide personalized productivity features.</p>
                    </div>
                    <div class="grid gap-3 md:grid-cols-3">
                        <article class="integration-point"><span>01</span><h3>Authenticate</h3><p>Google Sign-In confirms your identity securely.</p></article>
                        <article class="integration-point"><span>02</span><h3>Authorize</h3><p>You choose which Google permissions to grant.</p></article>
                        <article class="integration-point"><span>03</span><h3>Act</h3><p>Zaid completes only actions you request.</p></article>
                    </div>
                </div>
            </div>
        </section>

        <section id="permissions" class="section-space scroll-mt-24">
            <div class="section-container">
                <div data-reveal class="max-w-3xl">
                    <p class="section-kicker">Transparent access</p>
                    <h2 class="section-title">Requested permissions</h2>
                    <p class="section-copy">Every permission has a specific purpose. Access supports features you choose to use.</p>
                </div>

                <div class="mt-12 grid gap-4 md:grid-cols-2">
                    @foreach ([
                        ['Calendar', 'Google Calendar', 'Used to create, edit, update, and delete calendar events requested directly by the user.', 'md:translate-y-6'],
                        ['Tasks', 'Google Tasks', 'Used to create, edit, organize, complete, and delete Google Tasks requested directly by the user.', ''],
                        ['Profile', 'User Profile', "Used only for authentication and displaying the user's basic profile information.", 'md:translate-y-6'],
                        ['Email', 'Email Address', "Used to identify the user's account and provide personalized services.", ''],
                    ] as [$mark, $title, $copy, $offset])
                        <article data-reveal class="permission-shell {{ $offset }}">
                            <div class="permission-card">
                                <span class="permission-mark" aria-hidden="true">{{ $mark }}</span>
                                <h3>{{ $title }}</h3>
                                <p><strong>Purpose:</strong> {{ $copy }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>

                <p data-reveal class="trust-statement">We never access, modify, or share user data without user interaction or permission.</p>
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
                        ['Grant selected access', 'Approve Google Calendar and Google Tasks permissions.'],
                        ['Make a request', 'Ask Zaid Assistant to create schedules, reminders, or tasks.'],
                        ['Complete the action', 'Zaid Assistant performs only the action you request.'],
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
                    <p class="section-copy">Zaid uses Google data only to deliver features you directly request.</p>
                    <a href="https://myaccount.google.com/permissions" target="_blank" rel="noopener noreferrer" class="mt-6 inline-flex min-h-11 items-center text-sm font-semibold text-violet-200 underline decoration-violet-300/40 underline-offset-4 hover:text-violet-100">Manage Google access</a>
                </div>
                <ul data-reveal class="security-list">
                    @foreach ([
                        'OAuth 2.0 authentication',
                        'Secure HTTPS communication',
                        'User data is never sold',
                        'Data is only used to provide requested features',
                        'Users can revoke Google access at any time',
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
                    <article data-reveal class="feature-cell"><span>31</span><h3>Google Calendar Sync</h3><p>Keep approved event changes connected.</p></article>
                    <article data-reveal class="feature-cell"><span>✓</span><h3>Google Tasks Sync</h3><p>Organize and complete tasks you request.</p></article>
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
                        ['Why does Zaid Assistant need Google Calendar access?', 'Calendar access lets Zaid create, edit, update, or delete events only when you directly request those actions.'],
                        ['Why does it need Google Tasks access?', 'Tasks access lets Zaid create, edit, organize, complete, or delete tasks based on your direct requests.'],
                        ['Can I revoke access later?', 'Yes. You can revoke Zaid Assistant access at any time from your Google Account permissions page.'],
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
                        <p class="section-copy">Contact the developer for product, privacy, or Google integration questions.</p>
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
                <span class="footer-brand-row"><img src="/images/brand/zaid-logo.png" alt=""><strong>Zaid Assistant</strong></span>
                <span>Copyright © 2026</span>
            </div>
            <nav aria-label="Legal navigation" class="flex flex-wrap gap-x-5 gap-y-3 text-sm text-[#c8bdd2]">
                <a class="footer-link" href="/privacy">Privacy Policy</a>
                <a class="footer-link" href="/terms">Terms of Service</a>
                <a class="footer-link" href="https://developers.google.com/terms/api-services-user-data-policy" target="_blank" rel="noopener noreferrer">Google API Services User Data Policy</a>
                <a class="footer-link" href="mailto:zaidassistant@gmail.com">Contact</a>
            </nav>
        </div>
    </footer>
</body>
</html>
