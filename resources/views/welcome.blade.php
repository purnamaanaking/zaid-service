<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zaid — AI Productivity Core</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --purple-1: #c084fc;
            --purple-2: #a855f7;
            --purple-3: #7c3aed;
            --purple-4: #581c87;
            --bg-1: #050510;
            --bg-2: #0a0820;
            --card: rgba(255, 255, 255, 0.05);
            --border: rgba(192, 132, 252, 0.14);
            --text-soft: #94a3b8;
            --text-muted: #64748b;
        }

        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: white;
            background:
                radial-gradient(circle at 15% 20%, rgba(168, 85, 247, 0.35), transparent 25%),
                radial-gradient(circle at 85% 18%, rgba(124, 58, 237, 0.28), transparent 24%),
                radial-gradient(circle at 50% 80%, rgba(88, 28, 135, 0.45), transparent 30%),
                linear-gradient(135deg, var(--bg-1) 0%, var(--bg-2) 100%);
            position: relative;
        }

        .aurora,
        .aurora::before,
        .aurora::after {
            position: absolute;
            inset: -20%;
            content: "";
            filter: blur(90px);
            opacity: 0.7;
            pointer-events: none;
        }

        .aurora::before {
            background: radial-gradient(circle, rgba(168, 85, 247, 0.23) 0%, transparent 55%);
            animation: drift 12s ease-in-out infinite alternate;
        }

        .aurora::after {
            background: radial-gradient(circle, rgba(192, 132, 252, 0.14) 0%, transparent 60%);
            animation: drift 18s ease-in-out infinite alternate-reverse;
        }

        .grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(192, 132, 252, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(192, 132, 252, 0.05) 1px, transparent 1px);
            background-size: 64px 64px;
            mask-image: radial-gradient(circle at center, black 35%, transparent 80%);
            -webkit-mask-image: radial-gradient(circle at center, black 35%, transparent 80%);
            pointer-events: none;
        }

        .particles {
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--purple-1), var(--purple-3));
            box-shadow: 0 0 16px rgba(192, 132, 252, 0.8);
            opacity: 0;
            animation: floatUp linear infinite;
        }

        .page {
            position: relative;
            z-index: 2;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px;
        }

        .shell {
            width: min(1280px, 100%);
            height: min(820px, 100%);
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 24px;
            align-items: stretch;
        }

        .hero-panel,
        .side-panel {
            background: linear-gradient(180deg, rgba(255,255,255,0.05), rgba(255,255,255,0.03));
            border: 1px solid var(--border);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.08),
                0 20px 80px rgba(88, 28, 135, 0.25);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-radius: 32px;
            overflow: hidden;
        }

        .hero-panel {
            padding: 32px 34px 28px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .logo {
            font-size: 32px;
            font-weight: 900;
            letter-spacing: -1.8px;
            background: linear-gradient(135deg, #f5d0fe 0%, var(--purple-1) 30%, var(--purple-2) 60%, #ddd6fe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 6s linear infinite;
            background-size: 200% auto;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(124, 58, 237, 0.16);
            border: 1px solid rgba(192, 132, 252, 0.2);
            color: #e9d5ff;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        .pulse {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #c084fc;
            box-shadow: 0 0 0 0 rgba(192,132,252,.7);
            animation: pulse 2s infinite;
        }

        .hero-copy {
            max-width: 720px;
            margin-top: 10px;
        }

        .eyebrow {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.8px;
            color: #d8b4fe;
            margin-bottom: 16px;
            opacity: 0.9;
        }

        h1 {
            font-size: clamp(52px, 6vw, 86px);
            line-height: 0.95;
            letter-spacing: -3.8px;
            font-weight: 900;
            margin-bottom: 18px;
        }

        h1 .solid {
            display: block;
            color: white;
        }

        h1 .gradient {
            display: block;
            background: linear-gradient(135deg, #ffffff 0%, #e9d5ff 22%, var(--purple-1) 46%, var(--purple-2) 66%, #c4b5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 7s linear infinite;
            background-size: 220% auto;
        }

        .subtitle {
            max-width: 640px;
            font-size: 17px;
            line-height: 1.75;
            color: var(--text-soft);
            margin-bottom: 26px;
        }

        .hero-actions {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 34px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border-radius: 18px;
            padding: 15px 22px;
            font-size: 14px;
            font-weight: 700;
            transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease, background .25s ease;
        }

        .btn-primary {
            color: white;
            background: linear-gradient(135deg, var(--purple-2), var(--purple-3));
            box-shadow: 0 12px 36px rgba(124, 58, 237, 0.35);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 44px rgba(124, 58, 237, 0.45);
        }

        .btn-secondary {
            color: #eadcff;
            border: 1px solid rgba(192, 132, 252, 0.22);
            background: rgba(255,255,255,0.04);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            border-color: rgba(192, 132, 252, 0.4);
            background: rgba(168, 85, 247, 0.10);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .stat {
            border-radius: 22px;
            padding: 20px 18px;
            background: linear-gradient(180deg, rgba(255,255,255,0.045), rgba(255,255,255,0.025));
            border: 1px solid rgba(192, 132, 252, 0.10);
            transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease;
            animation: fadeUp .8s ease both;
        }

        .stat:hover {
            transform: translateY(-4px);
            border-color: rgba(192, 132, 252, 0.24);
            box-shadow: 0 16px 34px rgba(124, 58, 237, 0.14);
        }

        .stat strong {
            display: block;
            font-size: 34px;
            line-height: 1;
            letter-spacing: -1.4px;
            font-weight: 900;
            color: white;
            margin-bottom: 8px;
        }

        .stat span {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .side-panel {
            padding: 24px;
            display: grid;
            grid-template-rows: auto auto 1fr;
            gap: 18px;
        }

        .info-card {
            border-radius: 24px;
            background: linear-gradient(180deg, rgba(124, 58, 237, 0.14), rgba(124, 58, 237, 0.04));
            border: 1px solid rgba(192, 132, 252, 0.14);
            padding: 20px;
            animation: fadeUp .9s ease both;
        }

        .info-card h3 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1.6px;
            color: #e9d5ff;
            margin-bottom: 14px;
        }

        .info-card p {
            color: var(--text-soft);
            font-size: 14px;
            line-height: 1.7;
        }

        .feature-list {
            display: grid;
            gap: 12px;
        }

        .feature-item {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            border-radius: 18px;
            padding: 16px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(192, 132, 252, 0.09);
            transition: transform .25s ease, background .25s ease, border-color .25s ease;
            animation: fadeUp 1s ease both;
        }

        .feature-item:hover {
            transform: translateX(4px);
            background: rgba(168, 85, 247, 0.08);
            border-color: rgba(192, 132, 252, 0.18);
        }

        .feature-icon {
            min-width: 42px;
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            background: linear-gradient(135deg, rgba(192, 132, 252, 0.16), rgba(124, 58, 237, 0.12));
            border: 1px solid rgba(192, 132, 252, 0.12);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.08);
        }

        .feature-text strong {
            display: block;
            font-size: 14px;
            color: white;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .feature-text span {
            display: block;
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.65;
        }

        .stack-card {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            align-content: start;
        }

        .chip {
            border-radius: 16px;
            padding: 13px 14px;
            font-size: 13px;
            font-weight: 700;
            color: #e2d4ff;
            background: rgba(255,255,255,0.035);
            border: 1px solid rgba(192, 132, 252, 0.10);
            text-align: center;
            transition: transform .22s ease, border-color .22s ease, background .22s ease;
            animation: fadeUp 1.1s ease both;
        }

        .chip:hover {
            transform: translateY(-3px);
            border-color: rgba(192, 132, 252, 0.25);
            background: rgba(168, 85, 247, 0.09);
        }

        .footer-note {
            margin-top: auto;
            text-align: center;
            font-size: 12px;
            color: #475569;
            letter-spacing: 0.4px;
            padding-top: 4px;
        }

        @keyframes shimmer {
            from { background-position: 0% center; }
            to { background-position: 200% center; }
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(192,132,252,.6); }
            70% { box-shadow: 0 0 0 12px rgba(192,132,252,0); }
            100% { box-shadow: 0 0 0 0 rgba(192,132,252,0); }
        }

        @keyframes drift {
            from { transform: translate3d(-2%, -1%, 0) scale(1); }
            to { transform: translate3d(2%, 2%, 0) scale(1.08); }
        }

        @keyframes floatUp {
            0% { transform: translateY(100vh) scale(0.2); opacity: 0; }
            10% { opacity: 0.6; }
            90% { opacity: 0.15; }
            100% { transform: translateY(-20vh) scale(1); opacity: 0; }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 1100px) {
            html, body { overflow: auto; }
            .page { min-height: 100vh; }
            .shell {
                height: auto;
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .page { padding: 18px; }
            .hero-panel, .side-panel { border-radius: 24px; }
            .hero-panel { padding: 24px; }
            .topbar { flex-direction: column; align-items: flex-start; gap: 14px; }
            .hero-copy { max-width: 100%; }
            h1 { letter-spacing: -2.2px; }
            .subtitle { font-size: 15px; }
            .hero-actions { flex-direction: column; }
            .btn { width: 100%; }
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .stack-card { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
    <div class="aurora"></div>
    <div class="grid"></div>
    <div class="particles" id="particles"></div>

    <main class="page">
        <section class="shell">
            <div class="hero-panel">
                <div>
                    <div class="topbar">
                        <div class="logo">Zaid</div>
                        <div class="status-badge">
                            <span class="pulse"></span>
                            Backend Active
                        </div>
                    </div>

                    <div class="hero-copy">
                        <div class="eyebrow">AI Productivity Core</div>
                        <h1>
                            <span class="solid">Premium backend.</span>
                            <span class="gradient">Built for intelligent work.</span>
                        </h1>
                        <p class="subtitle">
                            Zaid adalah core engine untuk task, calendar, prompt AI, dan integrasi WhatsApp.
                            Satu service, satu source of truth, siap jadi fondasi pengalaman produktivitas yang cepat,
                            rapi, dan terasa modern.
                        </p>
                        <div class="hero-actions">
                            <a href="/api/v1/health" class="btn btn-primary">Check Service Status ↗</a>
                            <a href="#overview" class="btn btn-secondary">Overview</a>
                        </div>
                    </div>
                </div>

                <div class="stats" id="overview">
                    <div class="stat"><strong>28</strong><span>API Endpoints</span></div>
                    <div class="stat"><strong>15</strong><span>DB Tables</span></div>
                    <div class="stat"><strong>39</strong><span>Tests Passing</span></div>
                    <div class="stat"><strong>2</strong><span>AI Routes</span></div>
                </div>
            </div>

            <aside class="side-panel">
                <div class="info-card">
                    <h3>One-screen Overview</h3>
                    <p>
                        Fokus halaman ini cuma buat show off identitas produk dan fondasi teknisnya.
                        Gak ada endpoint sensitif, gak ada dokumentasi API terbuka, dan gak ada detail kredensial yang tampil.
                    </p>
                </div>

                <div class="feature-list">
                    <div class="feature-item">
                        <div class="feature-icon">🔐</div>
                        <div class="feature-text">
                            <strong>Google Auth + OTP Email</strong>
                            <span>Flow login simpel, aman, dan onboarding tetap terkontrol dari aplikasi.</span>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">🧠</div>
                        <div class="feature-text">
                            <strong>Prompt AI Bahasa Indonesia</strong>
                            <span>Perintah natural language diproses jadi aksi task, agenda, update, dan delete.</span>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">💬</div>
                        <div class="feature-text">
                            <strong>WhatsApp + Mobile Sync</strong>
                            <span>Satu data source untuk pengalaman app dan chat yang tetap sinkron.</span>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">⚡</div>
                        <div class="feature-text">
                            <strong>Production-minded Foundation</strong>
                            <span>Rate limiting, validation, audit trail, upload flow, dan testing yang rapi.</span>
                        </div>
                    </div>
                </div>

                <div class="stack-card">
                    <div class="chip">Laravel 13</div>
                    <div class="chip">PostgreSQL</div>
                    <div class="chip">Sanctum</div>
                    <div class="chip">Socialite</div>
                    <div class="chip">Gemini</div>
                    <div class="chip">MiniMax</div>
                </div>

                <div class="footer-note">Zaid Service v1.0 — premium core for intelligent productivity</div>
            </aside>
        </section>
    </main>

    <script>
        const particles = document.getElementById('particles');
        for (let i = 0; i < 26; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            p.style.left = (Math.random() * 100) + '%';
            p.style.animationDuration = (10 + Math.random() * 12) + 's';
            p.style.animationDelay = (Math.random() * 10) + 's';
            const size = 2 + Math.random() * 3;
            p.style.width = size + 'px';
            p.style.height = size + 'px';
            particles.appendChild(p);
        }
    </script>
</body>
</html>
