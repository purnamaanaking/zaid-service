<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zaid Assistant Onboarding</title>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>

        :root {
            --ink: #12071f;
            --ink-2: #170b2a;
            --panel: rgba(18, 7, 31, .72);
            --panel-2: rgba(27, 16, 48, .82);
            --line: rgba(250, 245, 255, .14);
            --text: #fffaff;
            --soft: #faf5ff;
            --muted: #b7a8c8;
            --purple: #a855f7;
            --lavender: #d8b4fe;
            --pink: #f0abfc;
            --ok: #86efac;
            --danger: #fca5a5;
            --shadow: 0 28px 90px rgba(0,0,0,.38);
            --scroll-y: 0px;
            --pointer-x: 0px;
            --pointer-y: 0px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 14% 12%, rgba(168,85,247,.24), transparent 26%),
                radial-gradient(circle at 86% 8%, rgba(240,171,252,.16), transparent 24%),
                linear-gradient(145deg, #080311 0%, var(--ink) 48%, #160821 100%);
            overflow-x: hidden;
        }
        body::before, body::after, .bg-orb {
            content: "";
            position: fixed;
            pointer-events: none;
            z-index: 0;
        }
        body::before {
            inset: 0;
            background-image:
                linear-gradient(rgba(250,245,255,.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(250,245,255,.045) 1px, transparent 1px);
            background-size: 72px 72px;
            transform: translate3d(0, calc(var(--scroll-y) * -.06), 0);
        }
        body::after {
            inset: -12vh -8vw;
            background:
                radial-gradient(circle at 18% 24%, rgba(168,85,247,.25), transparent 25%),
                radial-gradient(circle at 72% 64%, rgba(216,180,254,.14), transparent 34%);
            transform: translate3d(calc(var(--pointer-x) * -1.2), calc(var(--scroll-y) * -.18 + var(--pointer-y) * -1), 0);
        }
        .bg-orb {
            width: 640px; height: 640px; right: -210px; top: 18vh;
            border-radius: 999px;
            border: 1px solid rgba(216,180,254,.16);
            box-shadow: inset 0 0 90px rgba(168,85,247,.08), 0 0 90px rgba(168,85,247,.10);
            transform: translate3d(calc(var(--pointer-x) * 1.1), calc(var(--scroll-y) * -.28 + var(--pointer-y)), 0) rotate(calc(var(--scroll-y) * .018deg));
        }

        .page { position: relative; z-index: 1; min-height: 100vh; padding: clamp(24px, 5vw, 64px) 28px 28px; display: grid; place-items: center; }
        .shell { width: min(1280px, 100%); display: grid; grid-template-columns: 1fr 1fr; gap: clamp(18px, 3vw, 32px); align-items: start; }
        .hero, .panel {
            border: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(255,255,255,.075), rgba(255,255,255,.028));
            box-shadow: var(--shadow), inset 0 1px 0 rgba(255,255,255,.055);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }
        .hero { min-height: 620px; border-radius: 36px; padding: clamp(28px, 4vw, 52px); display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; position: relative; }
        .hero::before {
            content: ""; position: absolute; inset: 90px -80px auto auto; width: 360px; height: 360px;
            border-radius: 999px; border: 1px dashed rgba(216,180,254,.18);
            box-shadow: inset 0 0 70px rgba(168,85,247,.08);
        }
        .brand { display: inline-flex; align-items: center; gap: 10px; margin-bottom: 22px; color: var(--soft); font-size: 20px; font-weight: 550; letter-spacing: -.055em; text-decoration: none; }
        .brand img { width: 34px; height: 34px; object-fit: contain; filter: drop-shadow(0 0 14px rgba(168,85,247,.45)); }
        .brand strong { color: var(--lavender); font-weight: 800; }
        .badge { display: inline-flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 999px; border: 1px solid rgba(216,180,254,.24); background: rgba(216,180,254,.08); color: var(--lavender); font-size: 12px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; margin-bottom: 22px; }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--lavender); box-shadow: 0 0 0 8px rgba(216,180,254,.12), 0 0 22px rgba(216,180,254,.75); }
        h1 { max-width: 9ch; font-family: 'Fraunces', Georgia, serif; font-size: clamp(54px, 6vw, 86px); line-height: .88; letter-spacing: -.065em; margin-bottom: 26px; }
        h1 .solid, h1 .gradient { display: block; }
        h1 .gradient { color: var(--lavender); text-shadow: 0 0 40px rgba(216,180,254,.22); }
        .hero p { max-width: 44ch; color: var(--soft); font-size: 17px; line-height: 1.75; }
        .hero-footer { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 48px; }
        .mini { min-height: 132px; border-radius: 20px; padding: 16px 14px; background: rgba(255,255,255,.045); border: 1px solid var(--line); transition: transform .28s cubic-bezier(.23,1,.32,1), background .28s ease, border-color .28s ease; }
        .mini:hover { transform: translateY(-4px); background: rgba(255,255,255,.075); border-color: rgba(216,180,254,.28); }
        .mini strong { display: block; font-size: 14px; margin-bottom: 7px; color: var(--soft); }
        .mini span { display: block; font-size: 13px; line-height: 1.55; color: var(--muted); }

        .panel { min-height: 620px; border-radius: 32px; padding: clamp(24px, 3vw, 40px); }
        .panel-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
        .panel h2 { font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif; font-size: clamp(27px, 3vw, 38px); font-weight: 700; letter-spacing: -.06em; }
        .panel p.lead { color: var(--muted); font-size: 14px; line-height: 1.75; margin-bottom: 22px; }
        .logout-btn { display: none; width: auto; padding: 10px 14px; border-radius: 999px; border: 1px solid var(--line); background: rgba(255,255,255,.05); color: var(--soft); font-size: 12px; font-weight: 800; cursor: pointer; }
        .logout-btn.show { display: inline-flex; align-items: center; justify-content: center; }
        .steps { display: grid; gap: 14px; min-height: 300px; }
        .step { display: none; border-radius: 24px; padding: 20px; background: rgba(12,4,23,.58); border: 1px solid rgba(216,180,254,.14); box-shadow: inset 0 1px 0 rgba(255,255,255,.04); }
        .step.active { display: block; animation: step-enter .45s cubic-bezier(.23,1,.32,1) both; }
        @keyframes step-enter { from { opacity: 0; transform: translateY(12px) scale(.985); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .step-title { font-size: 17px; font-weight: 800; margin-bottom: 8px; color: var(--soft); }
        .step-desc { color: var(--muted); font-size: 13px; line-height: 1.65; margin-bottom: 16px; }
        .field { display: grid; gap: 8px; margin-bottom: 12px; }
        .label { font-size: 12px; color: var(--soft); font-weight: 700; letter-spacing: .4px; }
        .input { width: 100%; border: 1px solid rgba(216,180,254,.18); background: rgba(255,255,255,.055); color: white; border-radius: 17px; padding: 14px 16px; outline: none; font-size: 14px; transition: border-color .2s ease, background .2s ease, box-shadow .2s ease; }
        .input:focus { border-color: rgba(216,180,254,.48); background: rgba(255,255,255,.075); box-shadow: 0 0 0 4px rgba(168,85,247,.12); }
        .btn { width: 100%; border: 0; cursor: pointer; border-radius: 999px; padding: 14px 18px; font-size: 14px; font-weight: 800; transition: transform .22s ease, box-shadow .22s ease, opacity .22s ease; }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { color: #12071f; background: linear-gradient(135deg, var(--lavender), var(--purple)); box-shadow: 0 16px 42px rgba(168,85,247,.30); }
        .btn-secondary { color: var(--soft); background: rgba(255,255,255,.055); border: 1px solid var(--line); }
        .inline-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .status-box { margin-top: 16px; border-radius: 20px; padding: 15px 16px; background: rgba(255,255,255,.04); border: 1px solid rgba(216,180,254,.14); color: var(--soft); font-size: 13px; line-height: 1.65; white-space: pre-wrap; }
        .status-box.success { border-color: rgba(134,239,172,.28); color: #dcfce7; background: rgba(34,197,94,.08); }
        .status-box.error { border-color: rgba(252,165,165,.28); color: var(--danger); background: rgba(239,68,68,.08); }
        .user-meta { display: grid; gap: 8px; margin-bottom: 14px; color: var(--soft); font-size: 13px; }
        .tiny { margin-top: 12px; font-size: 12px; color: var(--muted); }
        #google-signin-button { min-height: 44px; }
        .legal-links { width: min(1280px, 100%); display: flex; gap: 18px; padding: 18px 6px 0; color: var(--muted); font-size: 13px; }
        .legal-links a { color: inherit; text-decoration: none; }
        .legal-links a:hover { color: var(--lavender); }

        /* Workspace redesign */
        body {
            min-height: 100dvh;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            background:
                radial-gradient(circle at 16% 8%, rgba(126, 34, 206, .15), transparent 32rem),
                radial-gradient(circle at 88% 88%, rgba(168, 85, 247, .08), transparent 36rem),
                #0c0614;
            -webkit-font-smoothing: antialiased;
        }
        body::before {
            opacity: .22;
            background-size: 96px 96px;
            mask-image: linear-gradient(to bottom, rgba(0,0,0,.65), transparent 82%);
            transform: none;
        }
        body::after, .bg-orb { display: none; }
        .page {
            align-content: center;
            padding: clamp(28px, 5vw, 72px) 24px 30px;
        }
        .shell {
            width: min(1180px, 100%);
            grid-template-columns: minmax(300px, .72fr) minmax(0, 1.28fr);
            align-items: stretch;
            gap: 1px;
            overflow: hidden;
            border-radius: 34px;
            padding: 1px;
            background: rgba(255,255,255,.10);
            box-shadow: 0 40px 120px rgba(42, 10, 63, .38), inset 0 1px 0 rgba(255,255,255,.08);
        }
        .hero, .panel {
            min-height: 610px;
            border: 0;
            border-radius: 0;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.04);
        }
        .hero {
            padding: clamp(30px, 4vw, 46px);
            background:
                radial-gradient(circle at 10% 10%, rgba(216,180,254,.10), transparent 20rem),
                #140b1e;
        }
        .hero::before {
            inset: auto -90px -130px auto;
            width: 320px;
            height: 320px;
            border: 1px solid rgba(216,180,254,.10);
            box-shadow: none;
        }
        .brand {
            margin-bottom: 70px;
            font-size: 18px;
        }
        .badge {
            margin-bottom: 18px;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            font-size: 10px;
            letter-spacing: .16em;
        }
        .dot {
            width: 6px;
            height: 6px;
            box-shadow: 0 0 16px rgba(216,180,254,.55);
        }
        h1 {
            max-width: 10ch;
            margin-bottom: 22px;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            font-size: clamp(42px, 4.4vw, 64px);
            font-weight: 650;
            line-height: .94;
            letter-spacing: -.065em;
        }
        h1 .gradient {
            color: #d8b4fe;
            text-shadow: none;
        }
        .hero p {
            max-width: 38ch;
            color: #b9adbf;
            font-size: 15px;
            line-height: 1.72;
        }
        .hero-footer {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0;
            margin-top: 48px;
            border-top: 1px solid rgba(255,255,255,.10);
        }
        .mini {
            min-height: auto;
            display: grid;
            grid-template-columns: 6.5rem 1fr;
            gap: 12px;
            border: 0;
            border-bottom: 1px solid rgba(255,255,255,.08);
            border-radius: 0;
            padding: 15px 2px;
            background: transparent;
        }
        .mini:hover {
            transform: translateX(4px);
            border-color: rgba(216,180,254,.22);
            background: transparent;
        }
        .mini strong { margin: 0; font-size: 12px; color: #e2d7e9; }
        .mini span { font-size: 12px; line-height: 1.5; }
        .panel {
            padding: clamp(30px, 4vw, 50px);
            background:
                radial-gradient(circle at 100% 0%, rgba(168,85,247,.08), transparent 24rem),
                #181022;
        }
        .panel-top { margin-bottom: 8px; }
        .panel h2 {
            max-width: 16ch;
            font-size: clamp(30px, 3.2vw, 46px);
            font-weight: 650;
            line-height: 1;
        }
        .panel p.lead {
            max-width: 56ch;
            margin-bottom: 30px;
            color: #9f92aa;
        }
        .logout-btn {
            min-height: 40px;
            border: 0;
            background: rgba(255,255,255,.06);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.09);
            transition: transform .14s cubic-bezier(.23,1,.32,1), background .24s ease;
        }
        .logout-btn:active { transform: scale(.96); }
        .progress-rail {
            display: grid;
            grid-template-columns: auto 1fr auto 1fr auto 1fr auto;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }
        .progress-item {
            display: grid;
            gap: 4px;
            color: #776b82;
            transition: color .28s cubic-bezier(.23,1,.32,1), transform .28s cubic-bezier(.23,1,.32,1);
        }
        .progress-item span {
            display: grid;
            width: 32px;
            height: 32px;
            place-items: center;
            border-radius: 10px;
            background: rgba(255,255,255,.045);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.07);
            font-size: 10px;
            font-variant-numeric: tabular-nums;
            transition: background .28s ease, box-shadow .28s ease;
        }
        .progress-item strong { font-size: 10px; font-weight: 600; }
        .progress-item.active { color: #f4ecf9; transform: translateY(-2px); }
        .progress-item.active span {
            color: #180923;
            background: #d8b4fe;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.65), 0 8px 22px rgba(126,34,206,.22);
        }
        .progress-item.complete { color: #b9adbf; }
        .progress-item.complete span { color: #d8b4fe; background: rgba(168,85,247,.15); }
        .progress-line { height: 1px; background: rgba(255,255,255,.09); }
        .steps { min-height: 278px; }
        .step {
            min-height: 278px;
            border: 0;
            border-radius: 22px;
            padding: clamp(22px, 3vw, 30px);
            background: #100817;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.055), inset 0 0 0 1px rgba(255,255,255,.07);
        }
        .step-title { font-size: 18px; letter-spacing: -.025em; }
        .step-desc { max-width: 58ch; color: #9f92aa; font-size: 13px; }
        .field { margin-top: 22px; margin-bottom: 16px; }
        .label { color: #d7cde2; }
        .input {
            min-height: 50px;
            border: 0;
            border-radius: 14px;
            background: rgba(255,255,255,.055);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.10), inset 0 1px 0 rgba(255,255,255,.04);
            transition: box-shadow .22s cubic-bezier(.23,1,.32,1), background .22s ease;
        }
        .input:focus {
            border: 0;
            background: rgba(255,255,255,.075);
            box-shadow: inset 0 0 0 1px rgba(216,180,254,.42), 0 0 0 4px rgba(168,85,247,.10);
        }
        .code-input { font-variant-numeric: tabular-nums; letter-spacing: .28em; }
        .btn {
            min-height: 48px;
            transition: transform .14s cubic-bezier(.23,1,.32,1), box-shadow .24s ease, background .24s ease, opacity .2s ease;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn:active { transform: scale(.96); }
        .btn-primary {
            color: #180923;
            background: #d8b4fe;
            box-shadow: 0 14px 34px rgba(126,34,206,.22), inset 0 1px 0 rgba(255,255,255,.65);
        }
        .btn-secondary {
            border: 0;
            background: rgba(255,255,255,.055);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.10);
        }
        #google-signin-button { min-height: 44px; max-width: 320px; }
        .tiny { color: #786d82; }
        .user-meta {
            gap: 0;
            overflow: hidden;
            border-radius: 14px;
            background: rgba(255,255,255,.035);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.07);
        }
        .user-meta div { padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,.06); }
        .status-box {
            position: relative;
            margin-top: 14px;
            border: 0;
            border-radius: 14px;
            padding: 14px 16px 14px 42px;
            color: #a99db7;
            background: rgba(255,255,255,.035);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.07);
        }
        .status-box::before {
            content: "";
            position: absolute;
            left: 17px;
            top: 50%;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #786d82;
            transform: translateY(-50%);
            transition: background .2s ease, box-shadow .2s ease;
        }
        .status-box.success { border: 0; color: #c9f7dd; background: rgba(34,197,94,.07); box-shadow: inset 0 0 0 1px rgba(74,222,128,.20); }
        .status-box.success::before { background: #4ade80; box-shadow: 0 0 12px rgba(74,222,128,.55); }
        .status-box.error { border: 0; background: rgba(239,68,68,.07); box-shadow: inset 0 0 0 1px rgba(252,165,165,.22); }
        .status-box.error::before { background: #fca5a5; }
        .legal-links { width: min(1180px, 100%); padding-top: 18px; }

        @media (max-width: 980px) {
            .page { place-items: start center; padding-top: 28px; }
            .shell { grid-template-columns: 1fr; gap: 1px; }
            .hero, .panel { min-height: auto; }
            .hero { padding-bottom: 32px; }
            .brand { margin-bottom: 48px; }
            .hero-footer { grid-template-columns: repeat(3, 1fr); }
            .panel { padding-top: 36px; }
        }
        @media (max-width: 640px) {
            .page { padding: 14px; }
            .shell { border-radius: 26px; }
            .hero, .panel { padding: 26px 22px; }
            .brand { margin-bottom: 42px; }
            h1 { max-width: 9ch; font-size: clamp(40px, 13vw, 56px); }
            .hero-footer { grid-template-columns: 1fr; margin-top: 34px; }
            .mini { grid-template-columns: 5.6rem 1fr; }
            .panel-top { align-items: flex-start; }
            .panel h2 { font-size: 30px; }
            .progress-rail { gap: 6px; overflow-x: auto; padding-bottom: 4px; }
            .progress-item strong { display: none; }
            .steps, .step { min-height: auto; }
            .step { padding: 22px 18px; }
            .inline-actions { grid-template-columns: 1fr; }
            .legal-links { padding-inline: 4px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; }
            body::before, body::after, .bg-orb { transform: none !important; }
            .step.active { animation: none; }
        }
    </style>
</head>
<body>
    <div class="bg-orb" aria-hidden="true"></div>

    <main class="page">
        <section class="shell">
            <div class="hero">
                <div>
                    <a class="brand logo-only" href="/" aria-label="Zaid Assistant home"><img src="/images/brand/zaid-logo.png" alt=""></a>
                    <div class="badge"><span class="dot"></span> Web Onboarding</div>
                    <h1>
                        <span class="solid">Plan clearly.</span>
                        <span class="gradient">Move with intent.</span>
                    </h1>
                    <p>
Sign in with Google, then verify your phone to start planning with Zaid Assistant.
                    </p>
                </div>

                <div class="hero-footer">
                    <div class="mini"><strong>01 · Sign in</strong><span>Use your Google account to authenticate securely.</span></div>
                    <div class="mini"><strong>02 · Verify</strong><span>Confirm your phone number with a one-time code.</span></div>
                    <div class="mini"><strong>03 · Start</strong><span>Manage tasks, schedules, and reminders in Zaid.</span></div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-top">
                    <h2>Set up your workspace</h2>
                    <button class="logout-btn" id="btn-logout">Logout</button>
                </div>
                <p class="lead">Complete three short steps to set up Zaid Assistant.</p>

                <div class="progress-rail" aria-label="Onboarding progress">
                    <div class="progress-item active" data-progress="step-auth" aria-current="step"><span>01</span><strong>Sign in</strong></div>
                    <div class="progress-line" aria-hidden="true"></div>
                    <div class="progress-item" data-progress="step-phone"><span>02</span><strong>Verify</strong></div>
                    <div class="progress-line" aria-hidden="true"></div>
                    <div class="progress-item" data-progress="step-otp"><span>03</span><strong>Confirm</strong></div>
                </div>

                <div class="steps">
                    <div class="step active" id="step-auth">
                        <div class="step-title">Continue with Google</div>
                        <div class="step-desc">Sign in securely with your Google account. After sign-in, Zaid Assistant checks whether phone verification is still required.</div>
                        <div id="google-signin-button"></div>
                        <div class="tiny">Google Client ID aktif akan dipakai dari konfigurasi backend.</div>
                    </div>

                    <div class="step" id="step-phone">
                        <div class="step-title">Verify your phone</div>
                        <div class="step-desc">Add an active phone number. We will send a one-time verification code.</div>
                        <div class="field">
                            <label class="label" for="phone_number">Phone number</label>
                            <input class="input" id="phone_number" type="tel" inputmode="tel" autocomplete="tel" placeholder="0812xxxxxxx">
                        </div>
                        <button class="btn btn-primary" id="btn-submit-phone">Send verification code</button>
                    </div>

                    <div class="step" id="step-otp">
                        <div class="step-title">Verify OTP</div>
                        <div class="step-desc">Enter the six-digit code sent to your verified phone or fallback email.</div>
                        <div class="field">
                            <label class="label" for="otp_code">Verification code</label>
                            <input class="input code-input" id="otp_code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="123456">
                        </div>
                        <div class="inline-actions">
                            <button class="btn btn-primary" id="btn-verify-otp">Verify code</button>
                            <button class="btn btn-secondary" id="btn-resend-otp">Resend code</button>
                        </div>
                    </div>

                </div>

                <div id="status-box" class="status-box" role="status" aria-live="polite">Waiting for your first step.</div>
            </div>
        </section>
        <nav class="legal-links" aria-label="Legal navigation">
            <a href="/privacy">Privacy Policy</a>
            <a href="/terms">Terms of Service</a>
        </nav>
    </main>

    <script>
        const googleClientId = @json(config('services.google.client_id'));
        const apiBase = '/api/v1';
        const tokenKey = 'zaid_web_access_token';
        const verificationKey = 'zaid_verification_id';
        const phoneKey = 'zaid_phone_number';

        const statusBox = document.getElementById('status-box');
        const stepAuth = document.getElementById('step-auth');
        const stepPhone = document.getElementById('step-phone');
        const stepOtp = document.getElementById('step-otp');
        const phoneInput = document.getElementById('phone_number');
        const otpInput = document.getElementById('otp_code');
        const logoutButton = document.getElementById('btn-logout');

        function showStep(step) {
            const allSteps = [stepAuth, stepPhone, stepOtp];
            const activeIndex = allSteps.indexOf(step);

            allSteps.forEach(el => el.classList.remove('active'));
            step.classList.add('active');

            document.querySelectorAll('[data-progress]').forEach((item, index) => {
                item.classList.toggle('active', index === activeIndex);
                item.classList.toggle('complete', index < activeIndex);

                if (index === activeIndex) {
                    item.setAttribute('aria-current', 'step');
                } else {
                    item.removeAttribute('aria-current');
                }
            });
        }

        function setStatus(message, type = '') {
            statusBox.className = 'status-box' + (type ? ' ' + type : '');
            statusBox.textContent = message;
        }

        function getToken() {
            return localStorage.getItem(tokenKey);
        }

        function setToken(token) {
            localStorage.setItem(tokenKey, token);
            logoutButton.classList.add('show');
        }

        function clearSession() {
            localStorage.removeItem(tokenKey);
            localStorage.removeItem(verificationKey);
            localStorage.removeItem(phoneKey);
            logoutButton.classList.remove('show');
        }

        function authHeaders() {
            return {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getToken(),
            };
        }

        async function api(path, options = {}) {
            const finalOptions = {
                ...options,
                headers: {
                    Accept: 'application/json',
                    ...(options.headers || {}),
                },
            };

            const response = await fetch(apiBase + path, finalOptions);
            const text = await response.text();
            let data = null;
            try {
                data = JSON.parse(text);
            } catch {
                data = {
                    success: false,
                    message: response.status === 429
                        ? 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.'
                        : (text || 'Unknown response'),
                };
            }

            if (!response.ok) {
                if (response.status === 429 && !data.message) {
                    data.message = 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.';
                }
                throw data;
            }

            return data;
        }

        async function afterLoginFlow(authData) {
            const onboarding = authData.data.onboarding;

            if (onboarding.next_step === 'phone_input') {
                showStep(stepPhone);
                setStatus('Login berhasil. Lanjut isi nomor HP untuk kirim OTP.', 'success');
                return;
            }

            if (onboarding.next_step === 'verify_otp') {
                showStep(stepOtp);
                setStatus('Nomor HP sudah tercatat. Masukkan OTP untuk lanjut.', 'success');
                return;
            }

            showStep(stepOtp);
            setStatus('Setup selesai. Zaid siap dipakai.', 'success');
        }

        async function refreshState() {
            const token = getToken();
            if (!token) {
                logoutButton.classList.remove('show');
                showStep(stepAuth);
                setStatus('Sign in with Google to begin setup.', '');
                return;
            }

            logoutButton.classList.add('show');

            try {
                const status = await api('/onboarding/status', {
                    method: 'GET',
                    headers: authHeaders(),
                });
                if (status.data.next_step === 'phone_input') {
                    showStep(stepPhone);
                    setStatus('Add your phone number to continue.', '');
                    return;
                }

                if (status.data.next_step === 'verify_otp') {
                    showStep(stepOtp);
                    setStatus('Enter your verification code to continue.', '');
                    return;
                }

                showStep(stepOtp);
                setStatus('Setup complete. Zaid is ready to use.', 'success');
            } catch (error) {
                clearSession();
                showStep(stepAuth);
                setStatus(error.message || 'Sesi tidak valid, silakan login ulang.', 'error');
            }
        }

        window.handleGoogleCredential = async function (response) {
            try {
                setStatus('Memproses login Google...', '');
                const authData = await api('/auth/google', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id_token: response.credential,
                        device: {
                            platform: 'web',
                            device_name: 'Web Browser',
                        },
                    }),
                });

                setToken(authData.data.access_token);
                await afterLoginFlow(authData);
            } catch (error) {
                setStatus(error.message || 'Login Google gagal.', 'error');
            }
        }

        function initGoogleButton() {
            if (!window.google || !googleClientId) {
                setStatus('Google Sign-In is not configured yet. Please contact support.', 'error');
                return;
            }

            google.accounts.id.initialize({
                client_id: googleClientId,
                callback: handleGoogleCredential,
            });

            google.accounts.id.renderButton(
                document.getElementById('google-signin-button'),
                {
                    type: 'standard',
                    theme: 'filled_black',
                    text: 'continue_with',
                    shape: 'pill',
                    size: 'large',
                    width: 320,
                }
            );
        }

        document.getElementById('btn-submit-phone').addEventListener('click', async () => {
            try {
                const phone = phoneInput.value.trim();
                localStorage.setItem(phoneKey, phone);
                const result = await api('/onboarding/phone', {
                    method: 'POST',
                    headers: authHeaders(),
                    body: JSON.stringify({ phone_number: phone, country_code: 'ID' }),
                });

                localStorage.setItem(verificationKey, result.data.verification_id);
                showStep(stepOtp);
                setStatus(`OTP dikirim. Channel: ${result.data.otp_channel || 'unknown'}`, 'success');
            } catch (error) {
                setStatus(error.message || 'Gagal kirim OTP.', 'error');
            }
        });

        document.getElementById('btn-verify-otp').addEventListener('click', async () => {
            try {
                const verificationId = localStorage.getItem(verificationKey);
                const result = await api('/onboarding/phone/verify', {
                    method: 'POST',
                    headers: authHeaders(),
                    body: JSON.stringify({
                        verification_id: verificationId,
                        otp_code: otpInput.value.trim(),
                    }),
                });

                showStep(stepOtp);
                setStatus(result.message || 'OTP berhasil diverifikasi. Zaid siap dipakai.', 'success');
                await refreshState();
            } catch (error) {
                setStatus(error.message || 'OTP tidak valid.', 'error');
            }
        });

        document.getElementById('btn-resend-otp').addEventListener('click', async () => {
            try {
                const phone = localStorage.getItem(phoneKey) || phoneInput.value.trim();
                const result = await api('/onboarding/phone/resend-otp', {
                    method: 'POST',
                    headers: authHeaders(),
                    body: JSON.stringify({ phone_number: phone }),
                });
                localStorage.setItem(verificationKey, result.data.verification_id);
                setStatus('OTP dikirim ulang.', 'success');
            } catch (error) {
                setStatus(error.message || 'Gagal kirim ulang OTP.', 'error');
            }
        });

        logoutButton.addEventListener('click', async () => {
            try {
                if (getToken()) {
                    await api('/auth/logout', {
                        method: 'POST',
                        headers: authHeaders(),
                    });
                }
            } catch (error) {
                // ignore server logout failure and still clear local session
            } finally {
                clearSession();
                phoneInput.value = '';
                otpInput.value = '';

                showStep(stepAuth);
                setStatus('Berhasil logout. Silakan login lagi kalau mau lanjut test.', 'success');
            }
        });

        window.addEventListener('load', () => {
            initGoogleButton();
            refreshState();

            let ticking = false;
            const updateParallax = () => {
                document.documentElement.style.setProperty('--scroll-y', `${window.scrollY}px`);
                ticking = false;
            };
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(updateParallax);
                    ticking = true;
                }
            }, { passive: true });
            window.addEventListener('pointermove', (event) => {
                const x = (event.clientX / window.innerWidth - .5) * 24;
                const y = (event.clientY / window.innerHeight - .5) * 24;
                document.documentElement.style.setProperty('--pointer-x', `${x}px`);
                document.documentElement.style.setProperty('--pointer-y', `${y}px`);
            }, { passive: true });
        });
    </script>
</body>
</html>
