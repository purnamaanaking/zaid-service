<!DOCTYPE html>
<html lang="en" class="h-full overflow-hidden bg-black">
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
<body class="h-[100dvh] w-full overflow-hidden bg-black text-[#fbf8ff] p-3 sm:p-4 flex flex-col justify-between select-none">
    
    <!-- Main Framed Canvas -->
    <div class="relative flex-1 w-full rounded-[30px] border border-white/10 bg-gradient-to-b from-[#0e071e] via-[#070310] to-[#040108] p-5 sm:p-7 flex flex-col justify-between overflow-hidden shadow-[0_0_100px_rgba(20,5,40,0.8),inset_0_1px_0_rgba(255,255,255,0.12)]">
        
        <!-- Ambient Luminous Aurora Fog -->
        <div class="pointer-events-none absolute -top-16 -right-16 h-[580px] w-[580px] rounded-full bg-gradient-to-bl from-teal-400/20 via-emerald-400/10 to-transparent blur-[140px]" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-20 h-[580px] w-[580px] rounded-full bg-gradient-to-tr from-violet-600/25 via-fuchsia-600/15 to-transparent blur-[150px]" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(#ffffff08_1px,transparent_1px)] [background-size:26px_26px] opacity-40" aria-hidden="true"></div>

        <!-- Top Navigation Bar -->
        <header class="relative z-30 flex items-center justify-between gap-4">
            <!-- Brand Logo -->
            <a href="/" class="flex items-center gap-2.5" aria-label="Zaid Assistant home">
                <img src="/images/brand/zaid-logo.png" alt="Zaid Logo" class="h-7 sm:h-8 w-auto">
                <span class="text-sm font-semibold tracking-tight text-white hidden sm:inline">Zaid <span class="text-violet-300">Assistant</span></span>
            </a>

            <!-- Center Pill Navigation -->
            <nav aria-label="Primary navigation" class="hidden md:flex items-center gap-1 rounded-full bg-white/5 px-4 py-1.5 ring-1 ring-white/10 backdrop-blur-xl shadow-inner text-xs font-medium text-[#c8bdd2]">
                <a href="/" class="px-3 py-1 text-white transition-colors">Home</a>
                <button type="button" onclick="openModal('modal-about')" class="px-3 py-1 hover:text-white transition-colors cursor-pointer">About</button>
                <button type="button" onclick="openModal('modal-how')" class="px-3 py-1 hover:text-white transition-colors cursor-pointer">How it works</button>
                <button type="button" onclick="openModal('modal-google')" class="px-3 py-1 hover:text-white transition-colors cursor-pointer">Google Sign-In</button>
                <button type="button" onclick="openModal('modal-faq')" class="px-3 py-1 hover:text-white transition-colors cursor-pointer">FAQ</button>
                
                <div class="ml-2 flex items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-0.5 text-[11px] text-violet-200 border border-white/15">
                    <span>OAuth 2.0</span>
                    <svg class="h-3 w-3 text-violet-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </div>
            </nav>

            <!-- Top Right Action -->
            <div class="flex items-center gap-3">
                <a href="/app" class="flex items-center gap-1.5 text-xs text-[#d0c5df] hover:text-white transition-colors font-medium">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>Create Account</span>
                </a>
            </div>
        </header>

        <!-- Center Pitch & Controls -->
        <div class="relative z-20 my-auto py-2 text-center">
            
            <!-- Play Button -->
            <div class="mb-3 flex justify-center">
                <button type="button" onclick="openModal('modal-about')" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 border border-white/20 text-white shadow-[0_0_25px_rgba(255,255,255,0.15)] hover:scale-105 transition-transform backdrop-blur-md cursor-pointer" aria-label="Play video introduction">
                    <svg class="h-3.5 w-3.5 fill-current ml-0.5 text-white" viewBox="0 0 24 24"><polygon points="6 3 20 12 6 21 6 3"/></svg>
                </button>
            </div>

            <!-- Spark Pill Badge -->
            <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-xs font-medium text-violet-200 shadow-inner backdrop-blur-md mb-5">
                <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Unlock Your Day Spark!</span>
                <svg class="h-3.5 w-3.5 text-violet-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </div>

            <!-- Big Main Headline -->
            <h1 class="mx-auto max-w-4xl text-balance text-4xl sm:text-6xl lg:text-[68px] font-semibold tracking-[-0.045em] text-white leading-[1.05]">
                One-click for Entire <br class="hidden sm:inline">Day Planning
            </h1>

            <!-- Subtext -->
            <p class="mx-auto mt-4 max-w-lg text-pretty text-xs sm:text-sm leading-relaxed text-[#b9acc7]">
                Manage schedules, calendars, tasks, reminders, and daily planning with an AI-powered assistant designed for calm focus.
            </p>

            <!-- Dual Buttons -->
            <div class="mt-7 flex items-center justify-center gap-3">
                <a href="/app" class="flex items-center justify-center rounded-full bg-white/5 border border-white/20 px-6 py-2.5 text-xs sm:text-sm font-semibold text-white backdrop-blur-md hover:bg-white/10 transition-colors">
                    Open App ↗
                </a>
                <a href="https://drive.google.com/drive/folders/11xW8ol-Zi4qBwSxktrdw9BTjIpTM-KcB?usp=drive_link" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center rounded-full bg-white text-black px-7 py-2.5 text-xs sm:text-sm font-semibold shadow-[0_0_30px_rgba(255,255,255,0.3)] hover:bg-[#eae4f5] transition-all">
                    Download App
                </a>
            </div>

            <!-- Vertical Light Streaks (4 laser lines under buttons) -->
            <div class="mt-7 flex justify-center items-start gap-5 h-16 pointer-events-none opacity-50">
                <div class="flex flex-col items-center">
                    <span class="h-1 w-1 rounded-full bg-white shadow-[0_0_8px_#fff]"></span>
                    <span class="w-[1px] h-12 bg-gradient-to-b from-white via-violet-300 to-transparent"></span>
                </div>
                <div class="flex flex-col items-center">
                    <span class="h-1.5 w-1.5 rounded-full bg-white shadow-[0_0_10px_#fff]"></span>
                    <span class="w-[1px] h-16 bg-gradient-to-b from-white via-teal-300 to-transparent"></span>
                </div>
                <div class="flex flex-col items-center">
                    <span class="h-1 w-1 rounded-full bg-white shadow-[0_0_8px_#fff]"></span>
                    <span class="w-[1px] h-10 bg-gradient-to-b from-white via-violet-300 to-transparent"></span>
                </div>
                <div class="flex flex-col items-center">
                    <span class="h-1 w-1 rounded-full bg-white shadow-[0_0_8px_#fff]"></span>
                    <span class="w-[1px] h-14 bg-gradient-to-b from-white via-fuchsia-300 to-transparent"></span>
                </div>
            </div>
        </div>

        <!-- Integrated Circuit Lines and Floating Nodes -->
        <svg class="pointer-events-none absolute inset-0 h-full w-full opacity-35 hidden lg:block" fill="none" viewBox="0 0 1200 650" preserveAspectRatio="none">
            <!-- Top-Left circuit line -->
            <path d="M 0 170 L 140 170 C 260 170, 320 270, 440 310" stroke="url(#circuit-line-grad)" stroke-width="1.2" />
            
            <!-- Bottom-Left circuit line -->
            <path d="M 0 400 L 100 400 C 240 400, 300 340, 440 330" stroke="url(#circuit-line-grad)" stroke-width="1.2" />
            
            <!-- Top-Right circuit line -->
            <path d="M 1200 170 L 1060 170 C 940 170, 880 270, 760 310" stroke="url(#circuit-line-grad)" stroke-width="1.2" />
            
            <!-- Bottom-Right circuit line -->
            <path d="M 1200 400 L 1100 400 C 960 400, 900 340, 760 330" stroke="url(#circuit-line-grad)" stroke-width="1.2" />

            <defs>
                <linearGradient id="circuit-line-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#ffffff" stop-opacity="0.5" />
                    <stop offset="50%" stop-color="#c084fc" stop-opacity="0.35" />
                    <stop offset="100%" stop-color="#2dd4bf" stop-opacity="0.1" />
                </linearGradient>
            </defs>
        </svg>

        <!-- Node 1: Top-Left (Voice Note NLP) -->
        <div class="hidden lg:block absolute left-0 top-[26%] z-20 pointer-events-none">
            <!-- Icon right on the line at x=140px -->
            <div class="absolute left-[130px] -top-3.5 h-7 w-7 rounded-full bg-[#160a28] border border-white/20 flex items-center justify-center text-white shadow-md">
                <svg class="h-3.5 w-3.5 text-violet-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><polygon points="12 2 2 22 22 22 12 2" stroke-width="2" stroke-linejoin="round"/></svg>
            </div>
            <!-- Text right under the line -->
            <div class="absolute left-[150px] top-5 text-left">
                <div class="flex items-center gap-1.5 text-xs font-semibold text-white">
                    <span class="h-1.5 w-1.5 rounded-full bg-violet-400"></span>
                    <span>Voice Note NLP</span>
                </div>
                <div class="text-[11px] text-[#8e829e] pl-3 font-mono">Audio transcription</div>
            </div>
        </div>

        <!-- Node 2: Bottom-Left (WhatsApp Reminders) -->
        <div class="hidden lg:block absolute left-0 top-[61.5%] z-20 pointer-events-none">
            <!-- Icon right on the line at x=100px -->
            <div class="absolute left-[90px] -top-3.5 h-7 w-7 rounded-full bg-[#160a28] border border-white/20 flex items-center justify-center text-emerald-300 shadow-md">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="3" stroke-width="2"/><circle cx="19" cy="12" r="2" stroke-width="2"/><circle cx="5" cy="12" r="2" stroke-width="2"/><circle cx="12" cy="19" r="2" stroke-width="2"/><circle cx="12" cy="5" r="2" stroke-width="2"/></svg>
            </div>
            <!-- Text right above the line -->
            <div class="absolute left-[110px] -top-10 text-left">
                <div class="flex items-center gap-1.5 text-xs font-semibold text-white">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                    <span>WhatsApp Reminders</span>
                </div>
                <div class="text-[11px] text-[#8e829e] pl-3 font-mono">Instant alert delivery</div>
            </div>
        </div>

        <!-- Node 3: Top-Right (Google Sign-In) -->
        <div class="hidden lg:block absolute right-0 top-[26%] z-20 pointer-events-none">
            <!-- Icon right on the line at right=140px -->
            <div class="absolute right-[130px] -top-3.5 h-7 w-7 rounded-full bg-[#160a28] border border-white/20 flex items-center justify-center text-violet-300 shadow-md">
                <svg class="h-3.5 w-3.5 text-teal-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            </div>
            <!-- Text right under the line -->
            <div class="absolute right-[170px] top-5 text-right">
                <div class="flex items-center justify-end gap-1.5 text-xs font-semibold text-white">
                    <span>Google Sign-In</span>
                    <span class="h-1.5 w-1.5 rounded-full bg-teal-400"></span>
                </div>
                <div class="text-[11px] text-[#8e829e] pr-3 font-mono">OAuth 2.0 Auth</div>
            </div>
        </div>

        <!-- Node 4: Bottom-Right (Smart Agenda) -->
        <div class="hidden lg:block absolute right-0 top-[61.5%] z-20 pointer-events-none">
            <!-- Icon right on the line at right=100px -->
            <div class="absolute right-[90px] -top-3.5 h-7 w-7 rounded-full bg-[#160a28] border border-white/20 flex items-center justify-center text-purple-300 shadow-md">
                <svg class="h-3.5 w-3.5 text-purple-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><polygon points="12 2 22 8.5 22 15.5 12 22 2 15.5 2 8.5 12 2" stroke-width="2" stroke-linejoin="round"/></svg>
            </div>
            <!-- Text right above the line -->
            <div class="absolute right-[130px] -top-10 text-right">
                <div class="flex items-center justify-end gap-1.5 text-xs font-semibold text-white">
                    <span>Smart Agenda</span>
                    <span class="h-1.5 w-1.5 rounded-full bg-purple-400"></span>
                </div>
                <div class="text-[11px] text-[#8e829e] pr-3 font-mono">Daily priority sync</div>
            </div>
        </div>

        <!-- Bottom Controls Inside Canvas Frame -->
        <div class="relative z-20 flex items-center justify-between border-t border-white/5 pt-3 text-xs text-[#a99bb7]">
            <button type="button" onclick="openModal('modal-how')" class="flex items-center gap-2 hover:text-white transition-colors cursor-pointer">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white/10 text-white font-mono text-[10px]">&darr;</span>
                <span>01 / 04 . How it works</span>
            </button>
            <div class="flex items-center gap-3">
                <span class="text-violet-300 font-medium">Zaid Horizons</span>
                <div class="flex items-center gap-1.5">
                    <span class="h-1.5 w-6 rounded-full bg-violet-400"></span>
                    <span class="h-1.5 w-4 rounded-full bg-white/20"></span>
                    <span class="h-1.5 w-4 rounded-full bg-white/20"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Monochromatic Ecosystem & Legal Bar -->
    <footer class="mt-2.5 flex items-center justify-between px-3 text-[11px] text-[#6b5d7d]">
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
                    <strong class="text-white block">01 · Schedules</strong>
                    <span>Turn priorities into a clear plan.</span>
                </div>
                <div class="rounded-xl border border-white/5 bg-white/5 p-3">
                    <strong class="text-white block">02 · Reminders</strong>
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
                <strong class="text-white block">01 · Sign in securely</strong>
                <span>Use your Google account to authenticate.</span>
            </li>
            <li class="rounded-xl border border-white/5 bg-white/5 p-3">
                <strong class="text-white block">02 · Verify your phone</strong>
                <span>Confirm your phone number with a one-time code.</span>
            </li>
            <li class="rounded-xl border border-white/5 bg-white/5 p-3">
                <strong class="text-white block">03 · Make a request</strong>
                <span>Ask Zaid Assistant to create schedules, reminders, or tasks.</span>
            </li>
            <li class="rounded-xl border border-white/5 bg-white/5 p-3">
                <strong class="text-white block">04 · Review your plan</strong>
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
                <li>✓ OAuth 2.0 authentication</li>
                <li>✓ Secure HTTPS communication</li>
                <li>✓ User data is never sold</li>
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
