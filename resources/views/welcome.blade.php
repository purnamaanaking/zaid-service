<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zaid — AI Productivity Assistant</title>
    <meta name="description" content="Zaid membantu mengatur task, agenda, reminder, dan integrasi kalender opsional dengan alur yang aman dan natural.">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,700;9..144,800&family=Space+Grotesk:wght@400;500;600;700&display=swap');

        :root {
            --ink: #12071f;
            --ink-2: #170b2a;
            --ink-3: #24123f;
            --panel: rgba(13, 27, 45, .72);
            --panel-strong: rgba(16, 36, 58, .86);
            --line: rgba(250, 245, 255, .14);
            --text: #f4fff9;
            --soft: #faf5ff;
            --muted: #89a7ad;
            --cyan: #a855f7;
            --lime: #d8b4fe;
            --amber: #f0abfc;
            --cream: #fce7f3;
            --shadow: 0 28px 90px rgba(0, 0, 0, .38);
            --radius-xl: 34px;
            --radius-lg: 24px;
            --scroll-y: 0px;
            --pointer-x: 0px;
            --pointer-y: 0px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            min-height: 100vh;
            font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 14% 12%, rgba(168, 85, 247, .24), transparent 26%),
                radial-gradient(circle at 86% 6%, rgba(240, 171, 252, .18), transparent 24%),
                radial-gradient(circle at 50% 80%, rgba(216, 180, 254, .12), transparent 34%),
                linear-gradient(145deg, #080311 0%, var(--ink) 45%, #160821 100%);
            overflow-x: hidden;
        }

        body::before, body::after,
        .bg-parallax, .bg-orb, .bg-orb::before, .bg-orb::after {
            content: "";
            position: fixed;
            pointer-events: none;
            z-index: 0;
        }
        body::before, body::after { inset: 0; }
        body::before {
            background-image:
                linear-gradient(rgba(250, 245, 255, .045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(250, 245, 255, .045) 1px, transparent 1px);
            background-size: 72px 72px;
            transform: translate3d(0, calc(var(--scroll-y) * -.06), 0);
            mask-image: radial-gradient(circle at 50% 28%, black 0%, transparent 78%);
            -webkit-mask-image: radial-gradient(circle at 50% 28%, black 0%, transparent 78%);
        }
        body::after {
            opacity: .28;
            background-image: radial-gradient(rgba(255,255,255,.38) .7px, transparent .8px);
            background-size: 22px 22px;
            mix-blend-mode: screen;
            transform: translate3d(0, calc(var(--scroll-y) * -.16), 0);
        }
        .bg-parallax {
            inset: -12vh -8vw;
            background:
                radial-gradient(circle at 18% 22%, rgba(168,85,247,.28), transparent 24%),
                radial-gradient(circle at 78% 16%, rgba(240,171,252,.20), transparent 22%),
                radial-gradient(circle at 62% 76%, rgba(216,180,254,.15), transparent 30%);
            filter: blur(2px);
            transform: translate3d(calc(var(--pointer-x) * -1.5), calc(var(--scroll-y) * -.22 + var(--pointer-y) * -1.2), 0) scale(1.05);
        }
        .bg-orb {
            width: 720px;
            height: 720px;
            right: -210px;
            top: 20vh;
            border-radius: 999px;
            border: 1px solid rgba(216,180,254,.16);
            box-shadow: inset 0 0 90px rgba(168,85,247,.08), 0 0 90px rgba(168,85,247,.10);
            transform: translate3d(calc(var(--pointer-x) * 1.2), calc(var(--scroll-y) * -.36 + var(--pointer-y)), 0) rotate(calc(var(--scroll-y) * .018deg));
        }
        .bg-orb::before {
            inset: 90px;
            border-radius: inherit;
            border: 1px dashed rgba(240,171,252,.18);
        }
        .bg-orb::after {
            width: 14px;
            height: 14px;
            right: 130px;
            top: 120px;
            border-radius: 50%;
            background: var(--amber);
            box-shadow: 0 0 28px rgba(240,171,252,.75);
        }

        a { color: inherit; }
        .page { position: relative; z-index: 1; }
        .wrap { width: min(1180px, calc(100% - 40px)); margin: 0 auto; }

        .site-header {
            position: sticky;
            top: 16px;
            z-index: 10;
            width: min(1180px, calc(100% - 40px));
            margin: 16px auto 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 12px 14px 12px 18px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: rgba(7, 17, 31, .72);
            box-shadow: 0 14px 50px rgba(0,0,0,.22);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; font-weight: 800; letter-spacing: -.04em; }
        .brand-mark {
            width: 36px; height: 36px; border-radius: 14px;
            display: grid; place-items: center;
            color: #12071f;
            background: linear-gradient(135deg, var(--lime), var(--cyan));
            box-shadow: 0 0 34px rgba(216,180,254,.28);
        }
        .nav-links { display: flex; align-items: center; gap: 8px; }
        .nav-links a { text-decoration: none; color: var(--muted); font-size: 14px; padding: 10px 12px; border-radius: 999px; transition: .2s ease; }
        .nav-links a:hover { color: var(--text); background: rgba(255,255,255,.06); }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 10px;
            min-height: 48px; padding: 14px 18px; border-radius: 999px;
            text-decoration: none; font-weight: 700; border: 1px solid transparent;
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary { color: #12071f; background: linear-gradient(135deg, var(--lime), var(--cyan)); box-shadow: 0 18px 46px rgba(168, 85, 247, .26); }
        .btn-ghost { color: var(--soft); background: rgba(255,255,255,.05); border-color: var(--line); }
        .btn-ghost:hover { border-color: rgba(250,245,255,.32); }

        .hero { min-height: 88vh; display: grid; align-items: center; padding: 74px 0 56px; }
        .hero-grid { display: grid; grid-template-columns: minmax(0, 1.02fr) minmax(360px, .98fr); gap: 46px; align-items: center; }
        .eyebrow {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 10px 14px; margin-bottom: 22px;
            border-radius: 999px; border: 1px solid rgba(216,180,254,.24);
            background: rgba(216,180,254,.08); color: var(--lime);
            text-transform: uppercase; letter-spacing: .12em; font-size: 12px; font-weight: 800;
        }
        .pulse { width: 8px; height: 8px; border-radius: 50%; background: var(--lime); box-shadow: 0 0 0 8px rgba(216,180,254,.12), 0 0 22px rgba(216,180,254,.75); }
        h1 {
            max-width: 760px;
            font-family: 'Fraunces', Georgia, serif;
            font-size: clamp(48px, 7.5vw, 104px);
            line-height: .88;
            letter-spacing: -.065em;
            margin-bottom: 24px;
        }
        h1 em { color: var(--lime); font-style: normal; text-shadow: 0 0 38px rgba(216,180,254,.25); }
        .lead { max-width: 650px; color: var(--soft); font-size: clamp(17px, 1.8vw, 21px); line-height: 1.78; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 34px; }
        .hero-metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 42px; }
        .metric { padding: 18px; border-radius: 22px; background: rgba(255,255,255,.045); border: 1px solid var(--line); }
        .metric strong { display: block; font-size: 24px; color: var(--cream); letter-spacing: -.04em; }
        .metric span { display: block; color: var(--muted); font-size: 13px; margin-top: 5px; line-height: 1.5; }

        .visual-stage {
            position: relative;
            min-height: 660px;
            border-radius: 44px;
            isolation: isolate;
            transform-style: preserve-3d;
            perspective: 1200px;
            overflow: visible;
        }
        .parallax-layer {
            will-change: transform;
            transition: transform .08s linear;
        }
        .visual-stage::before {
            content: "";
            position: absolute;
            inset: 74px 34px 108px 42px;
            border-radius: 42px;
            background:
                radial-gradient(circle at 50% 45%, rgba(168,85,247,.18), transparent 34%),
                linear-gradient(145deg, rgba(20,6,34,.96), rgba(12,4,23,.98));
            border: 1px solid rgba(250,245,255,.1);
            box-shadow: 0 34px 90px rgba(0,0,0,.42), inset 0 1px 0 rgba(255,255,255,.05);
            transform: translate3d(calc(var(--pointer-x) * -.25), calc(var(--pointer-y) * -.25), 0) rotate(-1.5deg);
        }
        .visual-stage::after {
            content: "";
            position: absolute;
            inset: 12px -10px 18px -20px;
            border-radius: 50%;
            border: 1px solid rgba(216,180,254,.16);
            border-left-color: transparent;
            border-bottom-color: rgba(240,171,252,.18);
            transform: translate3d(calc(var(--pointer-x) * .18), calc(var(--scroll-y) * -.035), 0) rotate(-18deg);
            z-index: -1;
        }
        .hero-art {
            position: absolute;
            inset: 168px 0 auto 0;
            width: min(50%, 330px);
            margin: auto;
            border-radius: 28px;
            box-shadow: 0 0 60px rgba(168,85,247,.28);
            transform: translate3d(calc(var(--pointer-x) * .55), calc((var(--scroll-y) * -.08) + (var(--pointer-y) * .55)), 0) rotate(-1deg);
            z-index: 2;
        }
        .orbit-ghost, .time-rail {
            position: absolute;
            pointer-events: none;
            border: 1px solid rgba(250,245,255,.14);
        }
        .orbit-ghost {
            width: 340px; height: 340px; right: 136px; top: 132px;
            border-radius: 999px;
            border-style: dashed;
            border-color: rgba(168,85,247,.34);
            box-shadow: inset 0 0 70px rgba(216,180,254,.06), 0 0 50px rgba(168,85,247,.08);
            z-index: 1;
        }
        .orbit-ghost::before, .orbit-ghost::after {
            content: ""; position: absolute; border-radius: 50%;
            background: var(--lime); box-shadow: 0 0 22px rgba(216,180,254,.75);
        }
        .orbit-ghost::before { width: 12px; height: 12px; left: 54px; top: 54px; }
        .orbit-ghost::after { width: 9px; height: 9px; right: 42px; bottom: 78px; background: var(--cyan); }
        .time-rail {
            width: 230px; height: 2px; left: 235px; top: 314px;
            border: 0;
            background: linear-gradient(90deg, transparent, rgba(250,245,255,.9), rgba(168,85,247,.45), transparent);
            filter: drop-shadow(0 0 16px rgba(216,180,254,.65));
            z-index: 3;
        }
        .time-rail::before, .time-rail::after {
            content: ""; position: absolute; top: -5px; width: 12px; height: 12px; border-radius: 50%;
            background: var(--amber); box-shadow: 0 0 24px rgba(240,171,252,.65);
        }
        .time-rail::before { left: -4px; }
        .time-rail::after { right: -4px; background: var(--cyan); }
        .float-card {
            position: absolute;
            width: 260px;
            max-width: 260px;
            padding: 18px 19px;
            border: 1px solid rgba(168,85,247,.24);
            border-radius: 22px;
            background: linear-gradient(160deg, rgba(17,29,47,.92), rgba(10,18,31,.94));
            box-shadow: 0 20px 70px rgba(0,0,0,.38), inset 0 1px 0 rgba(255,255,255,.05);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            z-index: 4;
        }
        .float-card strong { display: block; color: var(--text); margin-bottom: 6px; }
        .float-card span { color: var(--muted); font-size: 13px; line-height: 1.55; }
        .float-card strong { font-size: 16px; line-height: 1.3; }
        .float-card.one { top: 72px; left: 0; transform: translate3d(calc(var(--pointer-x) * -.45), calc(var(--scroll-y) * -.025), 0) rotate(-3deg); }
        .float-card.two { right: -92px; bottom: 238px; transform: translate3d(calc(var(--pointer-x) * .42), calc(var(--scroll-y) * -.045), 0) rotate(3deg); }
        .float-card.three { left: 58px; bottom: 72px; transform: translate3d(calc(var(--pointer-x) * -.34), calc(var(--scroll-y) * -.035), 0) rotate(1deg); }
        .card-kicker { display: inline-block; margin-bottom: 10px; color: var(--lime); font-size: 12px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }

        .section { padding: 86px 0; position: relative; }
        .section-head { display: flex; align-items: end; justify-content: space-between; gap: 24px; margin-bottom: 28px; }
        .section h2 { font-family: 'Fraunces', Georgia, serif; font-size: clamp(36px, 5vw, 68px); line-height: .94; letter-spacing: -.055em; max-width: 720px; }
        .section-head p { max-width: 430px; color: var(--muted); line-height: 1.75; }

        .proof-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; align-items: stretch; }
        .proof-card, .step, .trust-card, .final-cta {
            border: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(255,255,255,.07), rgba(255,255,255,.025));
            box-shadow: var(--shadow);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .proof-card {
            min-height: 520px;
            height: 100%;
            border-radius: var(--radius-xl);
            overflow: hidden;
            padding: 22px;
            display: flex;
            flex-direction: column;
        }
        .proof-card img {
            width: 100%;
            aspect-ratio: 1.08;
            object-fit: cover;
            border-radius: 24px;
            margin-bottom: 24px;
            border: 1px solid rgba(255,255,255,.08);
            background: var(--ink-2);
        }
        .proof-card h3, .step h3, .trust-card h3 { font-size: 22px; letter-spacing: -.04em; margin-bottom: 10px; }
        .proof-card p, .step p, .trust-card p { color: var(--muted); line-height: 1.72; }
        .parallax-band {
            position: absolute;
            inset: -70px auto auto 50%;
            width: min(760px, 90vw);
            height: 220px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(168,85,247,.16), rgba(216,180,254,.08) 42%, transparent 70%);
            filter: blur(8px);
            pointer-events: none;
            z-index: -1;
        }

        .workflow { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; position: relative; }
        .step { border-radius: var(--radius-lg); padding: 24px; position: relative; overflow: hidden; }
        .step::before { content: attr(data-step); display: grid; place-items: center; width: 42px; height: 42px; margin-bottom: 34px; border-radius: 16px; color: #12071f; background: linear-gradient(135deg, var(--amber), var(--lime)); font-weight: 800; }
        .step::after { content: ""; position: absolute; top: 45px; left: 82px; width: 140px; height: 1px; background: linear-gradient(90deg, rgba(216,180,254,.7), transparent); }

        .trust-layout { display: grid; grid-template-columns: .9fr 1.1fr; gap: 22px; align-items: stretch; }
        .trust-card { border-radius: var(--radius-xl); padding: 28px; }
        .trust-card.featured { background: linear-gradient(135deg, rgba(216,180,254,.13), rgba(168,85,247,.06)); }
        .trust-card img { width: 100%; max-width: 300px; display: block; margin: 0 auto 18px; border-radius: 26px; }
        .trust-list { display: grid; gap: 14px; }
        .trust-row { padding: 18px; border-radius: 22px; background: rgba(255,255,255,.045); border: 1px solid var(--line); }
        .trust-row strong { display: block; margin-bottom: 6px; color: var(--cream); }
        .trust-row span { color: var(--muted); line-height: 1.65; }

        .final-cta { border-radius: 42px; padding: clamp(30px, 6vw, 68px); text-align: center; position: relative; overflow: hidden; }
        .final-cta::before { content: ""; position: absolute; inset: -40% 10% auto; height: 330px; background: radial-gradient(circle, rgba(216,180,254,.22), transparent 64%); pointer-events: none; }
        .final-cta > * { position: relative; }
        .final-cta h2 { margin: 0 auto 18px; }
        .final-cta p { color: var(--soft); line-height: 1.75; max-width: 660px; margin: 0 auto 26px; }
        .footer-links { display: flex; justify-content: center; flex-wrap: wrap; gap: 16px; margin-top: 24px; color: var(--muted); font-size: 14px; }
        .footer-links a { text-decoration: none; }
        .footer-links a:hover { color: var(--lime); }

        [data-reveal] { opacity: 0; transform: translateY(26px); transition: opacity .7s ease, transform .7s ease; }
        [data-reveal].is-visible { opacity: 1; transform: translateY(0); }

        @media (max-width: 980px) {
            .site-header { position: relative; top: auto; border-radius: 24px; align-items: center; }
            .nav-links { display: none; }
            .hero { padding-top: 54px; }
            .hero-grid, .trust-layout { grid-template-columns: 1fr; }
            .visual-stage { min-height: 620px; max-width: 680px; width: 100%; margin: 22px auto 0; }
            .hero-art { width: min(48%, 320px); }
            .float-card.one { left: 8px; top: 76px; }
            .float-card.two { right: -34px; bottom: 228px; }
            .float-card.three { left: 48px; bottom: 92px; }
            .proof-grid, .workflow { grid-template-columns: 1fr 1fr; }
            .proof-card { min-height: 500px; }
            .section-head { display: block; }
            .section-head p { margin-top: 14px; }
        }
        @media (max-width: 640px) {
            .wrap, .site-header { width: min(100% - 28px, 1180px); }
            .site-header { padding: 10px 10px 10px 12px; gap: 10px; }
            .brand { gap: 8px; }
            .brand-mark { width: 32px; height: 32px; border-radius: 12px; }
            .site-header .btn { min-height: 42px; padding: 10px 12px; font-size: 13px; white-space: nowrap; }
            .hero { min-height: auto; padding: 48px 0 34px; }
            h1 { letter-spacing: -.05em; }
            .lead { font-size: 16px; }
            .hero-actions, .footer-links { flex-direction: column; }
            .hero-actions .btn { width: 100%; }
            .hero-metrics, .proof-grid, .workflow { grid-template-columns: 1fr; }
            .visual-stage { min-height: auto; margin-top: 16px; display: grid; gap: 12px; }
            .visual-stage::before, .visual-stage::after, .orbit-ghost, .time-rail { display: none; }
            .bg-orb { width: 420px; height: 420px; right: -240px; top: 18vh; opacity: .6; }
            .hero-art { position: relative; inset: auto; width: 100%; transform: none !important; }
            .float-card { position: relative; inset: auto !important; max-width: none; margin: 0; transform: none !important; }
            .float-card.two, .float-card.three { display: block; }
            .section { padding: 56px 0; }
            .proof-card { min-height: auto; transform: none !important; }
            .proof-card img { aspect-ratio: 1.25; }
            .step::after { display: none; }
            .final-cta { border-radius: 30px; }
        }
        @media (max-width: 420px) {
            .brand span:last-child { display: none; }
            .site-header .btn { padding-inline: 10px; }
            .eyebrow { font-size: 11px; letter-spacing: .08em; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; scroll-behavior: auto !important; transition-duration: .01ms !important; }
            body::before, body::after, .bg-parallax, .bg-orb, .hero-art, .float-card, .parallax-band, .visual-stage::before { transform: none !important; }
            [data-reveal] { opacity: 1; transform: none; }
        }
    </style>
</head>
<body>
    <div class="bg-parallax" aria-hidden="true"></div>
    <div class="bg-orb" aria-hidden="true"></div>
    <div class="page">
        <header class="site-header" data-reveal>
            <a class="brand" href="/" aria-label="Zaid home">
                <span class="brand-mark">Z</span>
                <span>Zaid</span>
            </a>
            <nav class="nav-links" aria-label="Main navigation">
                <a href="#workflow">Workflow</a>
                <a href="#trust">Trust</a>
                <a href="/privacy">Privacy</a>
                <a href="/terms">Terms</a>
            </nav>
            <a class="btn btn-primary" href="/app">Open App Flow</a>
        </header>

        <main>
            <section class="hero wrap">
                <div class="hero-grid">
                    <div data-reveal>
                        <div class="eyebrow"><span class="pulse"></span> AI Productivity Command Center</div>
                        <h1>Atur hari yang ramai jadi <em>alur kerja tenang.</em></h1>
                        <p class="lead">
                            Zaid membantu mengubah task, agenda, dan reminder menjadi sistem harian yang rapi. Login dengan Google, verifikasi nomor HP untuk keamanan, lalu hubungkan kalender hanya kalau kamu butuh sinkronisasi tambahan.
                        </p>
                        <div class="hero-actions">
                            <a class="btn btn-primary" href="/app">Mulai alur aplikasi</a>
                            <a class="btn btn-ghost" href="/privacy">Lihat privacy policy</a>
                        </div>
                        <div class="hero-metrics" aria-label="Zaid highlights">
                            <div class="metric"><strong>AI</strong><span>Bantu baca konteks task dan jadwal.</span></div>
                            <div class="metric"><strong>OTP</strong><span>Onboarding dengan phone verification.</span></div>
                            <div class="metric"><strong>Sync</strong><span>Kalender opsional, user-controlled.</span></div>
                        </div>
                    </div>

                    <div class="visual-stage" aria-label="Ilustrasi Zaid command center" data-reveal>
                        <div class="orbit-ghost parallax-layer" data-depth="0.34" data-rotate="18"></div>
                        <div class="time-rail parallax-layer" data-depth="-0.22"></div>
                        <img class="hero-art parallax-layer" data-depth="0.16" data-rotate="-2" src="/images/landing/zaid-command-center.svg" alt="Zaid AI command center with calendar orbit and task cards">
                        <div class="float-card one parallax-layer" data-depth="-0.28" data-rotate="-4">
                            <span class="card-kicker">Natural capture</span>
                            <strong>Besok jam 9 standup</strong>
                            <span>Zaid membaca intent, waktu, dan reminder supaya task langsung bisa ditindaklanjuti.</span>
                        </div>
                        <div class="float-card two parallax-layer" data-depth="0.38" data-rotate="3">
                            <span class="card-kicker">Calendar orbit</span>
                            <strong>Sync kalau dibutuhkan</strong>
                            <span>Akses kalender tetap opsional dan dipakai hanya saat pengguna mengaktifkan integrasi.</span>
                        </div>
                        <div class="float-card three parallax-layer" data-depth="-0.18" data-rotate="2">
                            <span class="card-kicker">Secure flow</span>
                            <strong>Google + phone verified</strong>
                            <span>Identitas akun dan nomor HP membantu menjaga onboarding lebih aman.</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section wrap" id="proof">
                <div class="section-head" data-reveal>
                    <h2>Bukan dashboard biasa. Ini cockpit buat hari produktif.</h2>
                    <p>Setiap layer visual dibuat untuk menjelaskan fungsi utama Zaid: tangkap pekerjaan, susun agenda, dan jaga akses data tetap transparan.</p>
                </div>
                <div class="proof-grid">
                    <div class="parallax-band parallax-layer" data-depth="0.18" data-rotate="0" aria-hidden="true"></div>
                    <article class="proof-card" data-reveal>
                        <img src="/images/landing/task-card-stack.svg" alt="Stack of task cards">
                        <h3>Task yang tidak tercecer</h3>
                        <p>Catatan dan permintaan harian diarahkan ke alur task yang mudah dipantau, bukan cuma disimpan lalu hilang.</p>
                    </article>
                    <article class="proof-card" data-reveal>
                        <img src="/images/landing/orbit-calendar.svg" alt="Calendar orbit illustration">
                        <h3>Agenda dalam orbit</h3>
                        <p>Jadwal, reminder, dan sinkronisasi kalender disusun sebagai sistem yang jelas tanpa memaksa integrasi dari awal.</p>
                    </article>
                    <article class="proof-card" data-reveal>
                        <img src="/images/landing/security-badge.svg" alt="Security verification badge">
                        <h3>Akses yang bisa dipercaya</h3>
                        <p>Google account untuk login, nomor HP untuk verifikasi, dan kalender hanya saat pengguna memberi izin.</p>
                    </article>
                </div>
            </section>

            <section class="section wrap" id="workflow">
                <div class="section-head" data-reveal>
                    <h2>Dari login sampai jadwal siap jalan.</h2>
                    <p>Flow Zaid dibuat pendek, aman, dan mudah dipahami pengguna baru.</p>
                </div>
                <div class="workflow">
                    <article class="step" data-step="01" data-reveal>
                        <h3>Login Google</h3>
                        <p>Dipakai untuk autentikasi dan identitas akun dasar pengguna.</p>
                    </article>
                    <article class="step" data-step="02" data-reveal>
                        <h3>Verifikasi HP</h3>
                        <p>Nomor HP dihubungkan ke akun untuk onboarding yang lebih aman.</p>
                    </article>
                    <article class="step" data-step="03" data-reveal>
                        <h3>Kelola task</h3>
                        <p>Task, agenda, dan reminder masuk ke alur kerja yang konsisten.</p>
                    </article>
                    <article class="step" data-step="04" data-reveal>
                        <h3>Connect calendar</h3>
                        <p>Integrasi kalender bersifat opsional untuk sinkronisasi tambahan.</p>
                    </article>
                </div>
            </section>

            <section class="section wrap" id="trust">
                <div class="trust-layout">
                    <article class="trust-card featured" data-reveal>
                        <img src="/images/landing/security-badge.svg" alt="Secure onboarding badge">
                        <h2>Transparan soal data, jelas dari awal.</h2>
                        <p>Zaid tidak menyamarkan kebutuhan akses. Setiap izin punya fungsi yang spesifik dan bisa dijelaskan langsung ke pengguna.</p>
                    </article>
                    <div class="trust-list" data-reveal>
                        <div class="trust-row"><strong>Google account access</strong><span>Digunakan untuk login, identitas akun, dan menjaga sesi pengguna tetap aman.</span></div>
                        <div class="trust-row"><strong>Phone verification</strong><span>Digunakan untuk menghubungkan nomor pengguna ke layanan dan mengurangi risiko penyalahgunaan onboarding.</span></div>
                        <div class="trust-row"><strong>Calendar connection</strong><span>Opsional. Hanya aktif jika pengguna memilih menghubungkan kalender untuk sinkronisasi jadwal.</span></div>
                    </div>
                </div>
            </section>

            <section class="section wrap">
                <div class="final-cta" data-reveal>
                    <h2>Siap bikin hari kerja lebih teratur?</h2>
                    <p>Masuk ke app flow Zaid atau baca dulu kebijakan privasi dan terms untuk melihat bagaimana akses data digunakan.</p>
                    <div class="hero-actions" style="justify-content:center">
                        <a class="btn btn-primary" href="/app">Open App Flow</a>
                        <a class="btn btn-ghost" href="/privacy">Privacy Policy</a>
                        <a class="btn btn-ghost" href="/terms">Terms of Service</a>
                    </div>
                    <div class="footer-links">
                        <a href="mailto:zaidassist@gmail.com">zaidassist@gmail.com</a>
                        <span>Chrono Command Center theme</span>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        (() => {
            const root = document.documentElement;
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (!reduceMotion) {
                let ticking = false;
                const updateScroll = () => {
                    root.style.setProperty('--scroll-y', `${window.scrollY}px`);
                    ticking = false;
                };

                window.addEventListener('scroll', () => {
                    if (!ticking) {
                        window.requestAnimationFrame(updateScroll);
                        ticking = true;
                    }
                }, { passive: true });

                window.addEventListener('pointermove', (event) => {
                    const x = (event.clientX / window.innerWidth - .5) * 24;
                    const y = (event.clientY / window.innerHeight - .5) * 24;
                    root.style.setProperty('--pointer-x', `${x}px`);
                    root.style.setProperty('--pointer-y', `${y}px`);
                }, { passive: true });

                const layers = [...document.querySelectorAll('.parallax-layer')];
                const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
                const moveLayers = () => {
                    const viewportCenter = window.innerHeight / 2;
                    const pointerX = Number.parseFloat(getComputedStyle(root).getPropertyValue('--pointer-x')) || 0;

                    layers.forEach((layer) => {
                        if (window.innerWidth < 641) {
                            layer.style.transform = '';
                            return;
                        }

                        const host = layer.closest('.visual-stage, .section') || layer;
                        const rect = host.getBoundingClientRect();
                        const depth = Number(layer.dataset.depth || 0);
                        const rotate = Number(layer.dataset.rotate || 0);
                        const distance = (rect.top + rect.height / 2) - viewportCenter;
                        const y = clamp(distance * depth, -34, 34);
                        const x = clamp(pointerX * depth * 1.2, -18, 18);

                        layer.style.transform = `translate3d(${x}px, ${y}px, 0) rotate(${rotate}deg)`;
                    });

                    window.requestAnimationFrame(moveLayers);
                };

                window.requestAnimationFrame(moveLayers);
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: .14 });

            document.querySelectorAll('[data-reveal]').forEach((element) => observer.observe(element));
        })();
    </script>
</body>
</html>
