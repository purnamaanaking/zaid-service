<!DOCTYPE html>
<html lang="en" class="h-full overflow-hidden bg-[#05020a]">
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
<body class="h-[100dvh] w-full overflow-hidden bg-[#06020c] text-[#fbf8ff] flex flex-col justify-between select-none relative">
    
    <!-- Ambient Luminous Aurora Fog -->
    <div class="pointer-events-none absolute -top-24 -right-24 h-[650px] w-[650px] rounded-full bg-gradient-to-bl from-teal-400/20 via-emerald-400/10 to-transparent blur-[150px] anim-aurora-1" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-28 -left-28 h-[650px] w-[650px] rounded-full bg-gradient-to-tr from-violet-600/25 via-fuchsia-600/15 to-transparent blur-[160px] anim-aurora-2" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(#ffffff08_1px,transparent_1px)] [background-size:26px_26px] opacity-40" aria-hidden="true"></div>

    <!-- Twinkling Starfield Sky Layer -->
    <svg class="pointer-events-none absolute inset-0 h-full w-full" aria-hidden="true">
        <!-- Tiny stars group 1 -->
        <g class="anim-star-1 fill-white">
            <circle cx="7%" cy="14%" r="1" />
            <circle cx="21%" cy="26%" r="1.3" />
            <circle cx="34%" cy="12%" r="0.8" />
            <circle cx="48%" cy="20%" r="1" />
            <circle cx="63%" cy="16%" r="1.4" />
            <circle cx="79%" cy="24%" r="0.9" />
            <circle cx="92%" cy="17%" r="1.2" />
            <circle cx="14%" cy="75%" r="1.1" />
            <circle cx="86%" cy="68%" r="0.8" />
        </g>
        <!-- Tiny stars group 2 -->
        <g class="anim-star-2 fill-teal-200">
            <circle cx="11%" cy="38%" r="1.2" />
            <circle cx="27%" cy="17%" r="0.8" />
            <circle cx="41%" cy="30%" r="1.4" />
            <circle cx="57%" cy="14%" r="1" />
            <circle cx="73%" cy="34%" r="1.2" />
            <circle cx="89%" cy="40%" r="0.9" />
            <circle cx="26%" cy="82%" r="1.3" />
            <circle cx="71%" cy="78%" r="1" />
        </g>
        <!-- Tiny stars group 3 -->
        <g class="anim-star-3 fill-violet-200">
            <circle cx="5%" cy="52%" r="1" />
            <circle cx="19%" cy="62%" r="1.3" />
            <circle cx="37%" cy="72%" r="0.9" />
            <circle cx="51%" cy="80%" r="1.2" />
            <circle cx="67%" cy="62%" r="0.8" />
            <circle cx="81%" cy="56%" r="1.4" />
            <circle cx="95%" cy="72%" r="1" />
        </g>
        <!-- Diamond twinkle stars -->
        <g class="anim-star-4 stroke-white/80 fill-none stroke-[0.8]">
            <path d="M 170 85 L 170 93 M 166 89 L 174 89" />
            <path d="M 860 105 L 860 113 M 856 109 L 864 109" />
            <path d="M 310 200 L 310 208 M 306 204 L 314 204" stroke="#a7f3d0" />
            <path d="M 970 220 L 970 228 M 966 224 L 974 224" stroke="#e9d5ff" />
            <path d="M 230 460 L 230 468 M 226 464 L 234 464" stroke="#c4b5fd" />
            <path d="M 890 480 L 890 488 M 886 484 L 894 484" />
        </g>
    </svg>

    <!-- Top Navigation Bar -->
    <header class="relative z-30 flex items-center justify-between px-6 sm:px-10 pt-5 sm:pt-6">
        <!-- Brand Logo -->
        <a href="/" class="flex items-center gap-2.5" aria-label="Zaid Assistant home">
            <img src="/images/brand/zaid-logo.png" alt="Zaid Logo" class="h-8 w-auto">
            <span class="text-sm font-semibold tracking-tight text-white hidden sm:inline">Zaid <span class="text-violet-300">Assistant</span></span>
        </a>

        <!-- Center Pill Navigation -->
        <nav aria-label="Primary navigation" class="hidden md:flex items-center gap-1 rounded-full bg-white/5 px-5 py-1.5 ring-1 ring-white/10 backdrop-blur-xl shadow-inner text-xs font-medium text-[#c8bdd2]">
            <a href="/" class="px-3 py-1 text-white transition-colors">Home</a>
            <button type="button" onclick="openModal('modal-about')" class="px-3 py-1 hover:text-white transition-colors cursor-pointer">About</button>
            <button type="button" onclick="openModal('modal-how')" class="px-3 py-1 hover:text-white transition-colors cursor-pointer">How it works</button>
            <button type="button" onclick="openModal('modal-google')" class="px-3 py-1 hover:text-white transition-colors cursor-pointer">Google Sign-In</button>
            <button type="button" onclick="openModal('modal-faq')" class="px-3 py-1 hover:text-white transition-colors cursor-pointer">FAQ</button>
            
            <div class="ml-2 flex items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-0.5 text-[11px] text-violet-200 border border-white/15">
                <span>OAuth 2.0</span>
            </div>
        </nav>

        <!-- Top Right Action -->
        <div class="flex items-center gap-3">
            <a href="/app" class="text-xs text-[#d0c5df] hover:text-white transition-colors font-medium">
                Create Account
            </a>
        </div>
    </header>

    <!-- Center Hero Section -->
    <main id="main-content" class="relative z-20 my-auto py-2 text-center px-4">

        <!-- Big Main Headline -->
        <h1 class="mx-auto max-w-4xl text-balance text-4xl sm:text-6xl lg:text-[72px] font-semibold tracking-[-0.045em] text-white leading-[1.05]">
            One-click for Entire <br class="hidden sm:inline">Day Planning
        </h1>

        <!-- Subtext -->
        <p class="mx-auto mt-5 max-w-lg text-pretty text-xs sm:text-sm leading-relaxed text-[#b9acc7]">
            Manage schedules, calendars, tasks, reminders, and daily planning with an AI-powered assistant designed for calm focus.
        </p>

        <!-- Dual Buttons without emoji or arrow icons -->
        <div class="mt-8 flex items-center justify-center gap-3.5">
            <a href="/app" class="flex items-center justify-center rounded-full bg-white/5 border border-white/20 px-6 py-2.5 text-xs sm:text-sm font-semibold text-white backdrop-blur-md hover:bg-white/10 transition-colors">
                Open App
            </a>
            <a href="https://drive.google.com/drive/folders/11xW8ol-Zi4qBwSxktrdw9BTjIpTM-KcB?usp=drive_link" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center rounded-full bg-white text-black px-7 py-2.5 text-xs sm:text-sm font-semibold shadow-[0_0_30px_rgba(255,255,255,0.3)] hover:bg-[#eae4f5] transition-all anim-cta-glow">
                Download App
            </a>
        </div>

        <!-- Vertical Light Streaks (4 laser lines under buttons) -->
        <div class="mt-8 flex justify-center items-start gap-6 h-16 pointer-events-none opacity-50">
            <div class="flex flex-col items-center">
                <span class="h-1 w-1 rounded-full bg-white shadow-[0_0_8px_#fff]"></span>
                <span class="w-[1px] h-12 bg-gradient-to-b from-white via-violet-300 to-transparent anim-beam-1"></span>
            </div>
            <div class="flex flex-col items-center">
                <span class="h-1.5 w-1.5 rounded-full bg-white shadow-[0_0_10px_#fff]"></span>
                <span class="w-[1px] h-16 bg-gradient-to-b from-white via-teal-300 to-transparent anim-beam-2"></span>
            </div>
            <div class="flex flex-col items-center">
                <span class="h-1 w-1 rounded-full bg-white shadow-[0_0_8px_#fff]"></span>
                <span class="w-[1px] h-10 bg-gradient-to-b from-white via-violet-300 to-transparent anim-beam-3"></span>
            </div>
            <div class="flex flex-col items-center">
                <span class="h-1 w-1 rounded-full bg-white shadow-[0_0_8px_#fff]"></span>
                <span class="w-[1px] h-14 bg-gradient-to-b from-white via-fuchsia-300 to-transparent anim-beam-4"></span>
            </div>
        </div>
    </main>

    <!-- Integrated Circuit Lines and Floating Nodes (NO ICON CIRCLES) -->
    <svg class="pointer-events-none absolute inset-0 h-full w-full opacity-40 hidden lg:block" fill="none" viewBox="0 0 1200 650" preserveAspectRatio="none">
        <!-- Top-Left circuit line -->
        <path class="anim-circuit" d="M 0 180 L 160 180 C 280 180, 320 270, 440 310" stroke="url(#circuit-line-grad)" stroke-width="1.2" />
        <circle cx="160" cy="180" r="3" fill="#ffffff" />
        
        <!-- Bottom-Left circuit line -->
        <path class="anim-circuit" d="M 0 420 L 130 420 C 260 420, 310 350, 440 330" stroke="url(#circuit-line-grad)" stroke-width="1.2" />
        <circle cx="130" cy="420" r="3" fill="#34d399" />
        
        <!-- Top-Right circuit line -->
        <path class="anim-circuit" d="M 1200 180 L 1040 180 C 920 180, 880 270, 760 310" stroke="url(#circuit-line-grad)" stroke-width="1.2" />
        <circle cx="1040" cy="180" r="3" fill="#2dd4bf" />
        
        <!-- Bottom-Right circuit line -->
        <path class="anim-circuit" d="M 1200 420 L 1070 420 C 940 420, 890 350, 760 330" stroke="url(#circuit-line-grad)" stroke-width="1.2" />
        <circle cx="1070" cy="420" r="3" fill="#c084fc" />

        <defs>
            <linearGradient id="circuit-line-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#ffffff" stop-opacity="0.55" />
                <stop offset="50%" stop-color="#c084fc" stop-opacity="0.4" />
                <stop offset="100%" stop-color="#2dd4bf" stop-opacity="0.15" />
            </linearGradient>
        </defs>
    </svg>

    <!-- Node 1: Top-Left (Voice Note NLP) -->
    <div class="hidden lg:block absolute left-[175px] top-[29%] z-20 pointer-events-none text-left">
        <div class="flex items-center gap-1.5 text-xs font-semibold text-white tracking-wide">
            <span class="relative flex h-2 w-2">
                <span class="anim-node-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-violet-400"></span>
            </span>
            <span>Voice Note NLP</span>
        </div>
        <div class="text-[11px] text-[#8e829e] pl-3.5 font-mono">Audio transcription</div>
    </div>

    <!-- Node 2: Bottom-Left (WhatsApp Reminders) -->
    <div class="hidden lg:block absolute left-[145px] top-[60.5%] z-20 pointer-events-none text-left">
        <div class="flex items-center gap-1.5 text-xs font-semibold text-white tracking-wide">
            <span class="relative flex h-2 w-2">
                <span class="anim-node-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
            </span>
            <span>WhatsApp Reminders</span>
        </div>
        <div class="text-[11px] text-[#8e829e] pl-3.5 font-mono">Instant alert delivery</div>
    </div>

    <!-- Node 3: Top-Right (Google Sign-In) -->
    <div class="hidden lg:block absolute right-[175px] top-[29%] z-20 pointer-events-none text-right">
        <div class="flex items-center justify-end gap-1.5 text-xs font-semibold text-white tracking-wide">
            <span>Google Sign-In</span>
            <span class="relative flex h-2 w-2">
                <span class="anim-node-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-teal-400"></span>
            </span>
        </div>
        <div class="text-[11px] text-[#8e829e] pr-3.5 font-mono">OAuth 2.0 Auth</div>
    </div>

    <!-- Node 4: Bottom-Right (Smart Agenda) -->
    <div class="hidden lg:block absolute right-[145px] top-[60.5%] z-20 pointer-events-none text-right">
        <div class="flex items-center justify-end gap-1.5 text-xs font-semibold text-white tracking-wide">
            <span>Smart Agenda</span>
            <span class="relative flex h-2 w-2">
                <span class="anim-node-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-purple-400"></span>
            </span>
        </div>
        <div class="text-[11px] text-[#8e829e] pr-3.5 font-mono">Daily priority sync</div>
    </div>

    <!-- Bottom Controls & Indicators -->
    <div class="relative z-20 flex items-center justify-between px-6 sm:px-10 text-xs text-[#a99bb7] pb-2">
        <button type="button" onclick="openModal('modal-how')" class="hover:text-white transition-colors cursor-pointer">
            01 / 04 . How it works
        </button>
        <div class="flex items-center gap-3">
            <span class="text-violet-300 font-medium">Zaid Horizons</span>
            <div class="flex items-center gap-1.5">
                <span class="h-1.5 w-6 rounded-full bg-violet-400"></span>
                <span class="h-1.5 w-3.5 rounded-full bg-white/20"></span>
                <span class="h-1.5 w-3.5 rounded-full bg-white/20"></span>
            </div>
        </div>
    </div>

    <!-- Bottom Integrated Ecosystem & Legal Bar -->
    <footer class="relative z-20 flex items-center justify-between px-6 sm:px-10 py-3.5 border-t border-white/5 text-[11px] text-[#6b5d7d] bg-[#040108]/70 backdrop-blur-md">
        <div class="flex items-center gap-4 sm:gap-6 uppercase font-semibold tracking-wider">
            <span>Google OAuth</span>
            <span>WhatsApp Cloud</span>
            <span>Laravel Core</span>
            <span>Expo Mobile</span>
            <span class="hidden md:inline">PostgreSQL</span>
            <span class="hidden md:inline">Tailwind CSS</span>
        </div>

        <nav aria-label="Legal navigation" class="flex items-center gap-4">
            <a class="hover:text-violet-300 transition-colors" href="/privacy">Privacy Policy</a>
            <a class="hover:text-violet-300 transition-colors" href="/terms">Terms of Service</a>
            <a class="hover:text-violet-300 transition-colors" href="mailto:zaidassistant@gmail.com">zaidassistant@gmail.com</a>
            <a class="hidden" href="https://zaidassistant.id">https://zaidassistant.id</a>
        </nav>
    </footer>

    <!-- Interactive Glass Modals for Information -->
    <!-- Modal: About -->
    <dialog id="modal-about" class="fixed inset-0 z-50 m-auto max-w-xl w-[90%] rounded-3xl border border-white/15 bg-[#120726]/95 p-6 sm:p-8 text-white backdrop-blur-2xl shadow-2xl backdrop:bg-black/70">
        <div class="flex items-center justify-between border-b border-white/10 pb-4">
            <h2 class="text-lg font-bold">About Zaid Assistant</h2>
            <button onclick="closeModal('modal-about')" class="text-sm font-mono text-[#a99bb7] hover:text-white cursor-pointer">[close]</button>
        </div>
        <div class="mt-4 space-y-3 text-xs sm:text-sm text-[#c8bdd2] leading-relaxed">
            <p>Zaid Assistant turns everyday requests into organized, user-approved actions across planning tools you already use.</p>
            <div class="grid grid-cols-2 gap-3 pt-2">
                <div class="rounded-xl border border-white/5 bg-white/5 p-3">
                    <strong class="text-white block">01 Schedules</strong>
                    <span>Turn priorities into a clear plan.</span>
                </div>
                <div class="rounded-xl border border-white/5 bg-white/5 p-3">
                    <strong class="text-white block">02 Reminders</strong>
                    <span>Capture time-sensitive commitments.</span>
                </div>
            </div>
        </div>
    </dialog>

    <!-- Modal: How it works -->
    <dialog id="modal-how" class="fixed inset-0 z-50 m-auto max-w-xl w-[90%] rounded-3xl border border-white/15 bg-[#120726]/95 p-6 sm:p-8 text-white backdrop-blur-2xl shadow-2xl backdrop:bg-black/70">
        <div class="flex items-center justify-between border-b border-white/10 pb-4">
            <h2 class="text-lg font-bold">How it works</h2>
            <button onclick="closeModal('modal-how')" class="text-sm font-mono text-[#a99bb7] hover:text-white cursor-pointer">[close]</button>
        </div>
        <ol class="mt-4 space-y-3 text-xs sm:text-sm text-[#c8bdd2]">
            <li class="rounded-xl border border-white/5 bg-white/5 p-3">
                <strong class="text-white block">01 Sign in securely</strong>
                <span>Use your Google account to authenticate.</span>
            </li>
            <li class="rounded-xl border border-white/5 bg-white/5 p-3">
                <strong class="text-white block">02 Verify your phone</strong>
                <span>Confirm your phone number with a one-time code.</span>
            </li>
            <li class="rounded-xl border border-white/5 bg-white/5 p-3">
                <strong class="text-white block">03 Make a request</strong>
                <span>Ask Zaid Assistant to create schedules, reminders, or tasks.</span>
            </li>
            <li class="rounded-xl border border-white/5 bg-white/5 p-3">
                <strong class="text-white block">04 Review your plan</strong>
                <span>Manage your work in Zaid Assistant.</span>
            </li>
        </ol>
    </dialog>

    <!-- Modal: Google Sign-In & Privacy -->
    <dialog id="modal-google" class="fixed inset-0 z-50 m-auto max-w-xl w-[90%] rounded-3xl border border-white/15 bg-[#120726]/95 p-6 sm:p-8 text-white backdrop-blur-2xl shadow-2xl backdrop:bg-black/70">
        <div class="flex items-center justify-between border-b border-white/10 pb-4">
            <h2 class="text-lg font-bold">Why Google Sign-In is required</h2>
            <button onclick="closeModal('modal-google')" class="text-sm font-mono text-[#a99bb7] hover:text-white cursor-pointer">[close]</button>
        </div>
        <div class="mt-4 space-y-3 text-xs sm:text-sm text-[#c8bdd2] leading-relaxed">
            <p>Google Sign-In only to securely authenticate users and identify their Zaid account.</p>
            <div class="rounded-xl border border-white/5 bg-white/5 p-3.5 space-y-1">
                <strong class="text-white block">Does Zaid access my Google Calendar or Tasks?</strong>
                <p>No. Zaid does not request or use Google Calendar or Google Tasks access.</p>
            </div>
            <ul class="space-y-1 text-xs text-violet-200">
                <li>OAuth 2.0 authentication</li>
                <li>Secure HTTPS communication</li>
                <li>User data is never sold</li>
            </ul>
        </div>
    </dialog>

    <!-- Modal: FAQ -->
    <dialog id="modal-faq" class="fixed inset-0 z-50 m-auto max-w-xl w-[90%] rounded-3xl border border-white/15 bg-[#120726]/95 p-6 sm:p-8 text-white backdrop-blur-2xl shadow-2xl backdrop:bg-black/70">
        <div class="flex items-center justify-between border-b border-white/10 pb-4">
            <h2 class="text-lg font-bold">Frequently Asked Questions</h2>
            <button onclick="closeModal('modal-faq')" class="text-sm font-mono text-[#a99bb7] hover:text-white cursor-pointer">[close]</button>
        </div>
        <div class="mt-4 space-y-3 text-xs sm:text-sm text-[#c8bdd2] max-h-[60vh] overflow-y-auto">
            <div class="rounded-xl border border-white/5 bg-white/5 p-3">
                <strong class="text-white block">Why does Zaid Assistant use Google Sign-In?</strong>
                <p class="mt-1">Google Sign-In securely authenticates your Zaid account.</p>
            </div>
            <div class="rounded-xl border border-white/5 bg-white/5 p-3">
                <strong class="text-white block">Does Zaid access my Google Calendar or Tasks?</strong>
                <p class="mt-1">No. Zaid does not request or use Google Calendar or Google Tasks access.</p>
            </div>
            <div class="rounded-xl border border-white/5 bg-white/5 p-3">
                <strong class="text-white block">Can I remove Google Sign-In access?</strong>
                <p class="mt-1">Yes. You can revoke Zaid Assistant access at any time from your Google Account permissions page.</p>
            </div>
            <div class="rounded-xl border border-white/5 bg-white/5 p-3">
                <strong class="text-white block">Is my data secure?</strong>
                <p class="mt-1">Zaid uses OAuth 2.0 and HTTPS. We do not sell user data, and data is used only to provide requested features.</p>
            </div>
        </div>
    </dialog>

    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal && typeof modal.showModal === 'function') {
                modal.showModal();
            }
        }
        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal && typeof modal.close === 'function') {
                modal.close();
            }
        }
    </script>
</body>
</html>
