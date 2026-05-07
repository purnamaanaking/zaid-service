<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zaid</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        * { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-1: #070511;
            --bg-2: #120a24;
            --purple-1: #c084fc;
            --purple-2: #a855f7;
            --purple-3: #7c3aed;
            --purple-4: #581c87;
            --text-soft: #cbd5e1;
            --text-muted: #94a3b8;
            --border: rgba(192, 132, 252, 0.14);
            --card: rgba(255, 255, 255, 0.05);
        }

        html, body {
            width: 100%;
            min-height: 100%;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: white;
            overflow: hidden;
            background:
                radial-gradient(circle at 20% 20%, rgba(168, 85, 247, 0.28), transparent 28%),
                radial-gradient(circle at 80% 18%, rgba(124, 58, 237, 0.22), transparent 24%),
                radial-gradient(circle at 50% 85%, rgba(88, 28, 135, 0.45), transparent 36%),
                linear-gradient(135deg, var(--bg-1) 0%, var(--bg-2) 100%);
            position: relative;
        }

        .aurora,
        .aurora::before,
        .aurora::after {
            position: absolute;
            inset: -15%;
            content: "";
            pointer-events: none;
            filter: blur(90px);
        }

        .aurora::before {
            background: radial-gradient(circle, rgba(192, 132, 252, 0.16) 0%, transparent 60%);
            animation: drift 14s ease-in-out infinite alternate;
        }

        .aurora::after {
            background: radial-gradient(circle, rgba(124, 58, 237, 0.12) 0%, transparent 60%);
            animation: drift 18s ease-in-out infinite alternate-reverse;
        }

        .grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(192,132,252,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(192,132,252,0.04) 1px, transparent 1px);
            background-size: 64px 64px;
            mask-image: radial-gradient(circle at center, black 35%, transparent 78%);
            -webkit-mask-image: radial-gradient(circle at center, black 35%, transparent 78%);
            pointer-events: none;
        }

        .particles {
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--purple-1), var(--purple-3));
            box-shadow: 0 0 14px rgba(192,132,252,.65);
            opacity: 0;
            animation: floatUp linear infinite;
        }

        .page {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px;
        }

        .shell {
            width: min(1160px, 100%);
            min-height: 720px;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 22px;
        }

        .panel {
            border-radius: 32px;
            border: 1px solid var(--border);
            background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.03));
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.08),
                0 24px 80px rgba(88, 28, 135, 0.22);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .hero {
            padding: 34px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .logo {
            font-size: 34px;
            font-weight: 900;
            letter-spacing: -1.8px;
            background: linear-gradient(135deg, #fff 0%, var(--purple-1) 35%, var(--purple-2) 70%, #ddd6fe 100%);
            background-size: 220% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 7s linear infinite;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(124, 58, 237, 0.14);
            border: 1px solid rgba(192, 132, 252, 0.18);
            color: #e9d5ff;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .4px;
        }

        .pulse {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--purple-1);
            box-shadow: 0 0 0 0 rgba(192,132,252,.7);
            animation: pulse 2s infinite;
        }

        .eyebrow {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.8px;
            color: #d8b4fe;
            margin-bottom: 18px;
        }

        h1 {
            font-size: clamp(56px, 6.5vw, 88px);
            line-height: .94;
            letter-spacing: -4px;
            font-weight: 900;
            margin-bottom: 22px;
        }

        h1 .solid {
            display: block;
            color: white;
        }

        h1 .gradient {
            display: block;
            background: linear-gradient(135deg, #ffffff 0%, #f5d0fe 24%, var(--purple-1) 46%, var(--purple-2) 70%, #c4b5fd 100%);
            background-size: 220% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 8s linear infinite;
        }

        .subtitle {
            max-width: 620px;
            font-size: 17px;
            line-height: 1.8;
            color: var(--text-soft);
        }

        .hero-bottom {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-top: 36px;
        }

        .mini-card {
            border-radius: 22px;
            padding: 20px 18px;
            background: rgba(255,255,255,0.035);
            border: 1px solid rgba(192, 132, 252, 0.10);
            transition: transform .25s ease, border-color .25s ease, background .25s ease;
        }

        .mini-card:hover {
            transform: translateY(-4px);
            border-color: rgba(192, 132, 252, 0.22);
            background: rgba(168, 85, 247, 0.08);
        }

        .mini-card strong {
            display: block;
            color: white;
            font-size: 14px;
            margin-bottom: 6px;
            font-weight: 700;
        }

        .mini-card span {
            display: block;
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.65;
        }

        .side {
            padding: 24px;
            display: grid;
            grid-template-rows: auto 1fr auto;
            gap: 18px;
        }

        .showcase {
            border-radius: 26px;
            padding: 24px;
            background: linear-gradient(180deg, rgba(124,58,237,0.12), rgba(124,58,237,0.04));
            border: 1px solid rgba(192,132,252,0.14);
        }

        .showcase h2 {
            font-size: 22px;
            line-height: 1.2;
            margin-bottom: 10px;
            letter-spacing: -1px;
        }

        .showcase p {
            color: var(--text-soft);
            font-size: 14px;
            line-height: 1.75;
        }

        .feature-list {
            display: grid;
            gap: 12px;
        }

        .feature-item {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            padding: 16px;
            border-radius: 18px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(192,132,252,0.09);
            transition: transform .25s ease, border-color .25s ease, background .25s ease;
        }

        .feature-item:hover {
            transform: translateX(4px);
            border-color: rgba(192,132,252,0.18);
            background: rgba(168,85,247,0.08);
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
            background: linear-gradient(135deg, rgba(192,132,252,0.16), rgba(124,58,237,0.12));
            border: 1px solid rgba(192,132,252,0.12);
        }

        .feature-text strong {
            display: block;
            font-size: 14px;
            margin-bottom: 5px;
            color: white;
        }

        .feature-text span {
            display: block;
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.65;
        }

        .signature {
            text-align: center;
            color: #475569;
            font-size: 12px;
            letter-spacing: .4px;
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
            0% { transform: translateY(105vh) scale(.2); opacity: 0; }
            10% { opacity: .65; }
            90% { opacity: .12; }
            100% { transform: translateY(-20vh) scale(1); opacity: 0; }
        }

        @media (max-width: 1080px) {
            body { overflow: auto; }
            .page { min-height: 100vh; }
            .shell { grid-template-columns: 1fr; min-height: auto; }
        }

        @media (max-width: 720px) {
            .page { padding: 18px; }
            .hero, .side { padding: 22px; }
            .topbar { flex-direction: column; align-items: flex-start; gap: 14px; }
            h1 { letter-spacing: -2.4px; }
            .subtitle { font-size: 15px; }
            .hero-bottom { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="aurora"></div>
    <div class="grid"></div>
    <div class="particles" id="particles"></div>

    <main class="page">
        <section class="shell">
            <div class="panel hero">
                <div>
                    <div class="topbar">
                        <div class="logo">Zaid</div>
                        <div class="badge"><span class="pulse"></span>Private Core</div>
                    </div>

                    <div class="eyebrow">AI Productivity Engine</div>
                    <h1>
                        <span class="solid">Simple outside.</span>
                        <span class="gradient">Powerful inside.</span>
                    </h1>
                    <p class="subtitle">
                        Zaid adalah fondasi backend untuk pengalaman produktivitas yang modern —
                        menghubungkan task, calendar, prompt AI, dan interaksi lintas channel ke dalam satu inti yang rapi dan cepat.
                    </p>
                </div>

                <div class="hero-bottom">
                    <div class="mini-card">
                        <strong>Structured</strong>
                        <span>Arsitektur backend dibangun rapi untuk flow onboarding, tasking, dan prompt processing.</span>
                    </div>
                    <div class="mini-card">
                        <strong>Responsive</strong>
                        <span>Dirancang untuk pengalaman cepat, stabil, dan nyaman dipakai sebagai core service aplikasi.</span>
                    </div>
                    <div class="mini-card">
                        <strong>Integrated</strong>
                        <span>Satu source of truth untuk pengalaman app, AI, dan komunikasi berbasis percakapan.</span>
                    </div>
                </div>
            </div>

            <aside class="panel side">
                <div class="showcase">
                    <h2>Built to feel premium, not noisy.</h2>
                    <p>
                        Halaman ini sengaja dibuat sederhana, elegan, dan informatif.
                        Fokusnya cuma menunjukkan identitas Zaid sebagai backend core yang modern,
                        tanpa menampilkan detail internal, endpoint, credential, atau informasi sensitif lainnya.
                    </p>
                </div>

                <div class="feature-list">
                    <div class="feature-item">
                        <div class="feature-icon">🔐</div>
                        <div class="feature-text">
                            <strong>Secure onboarding flow</strong>
                            <span>Masuk dengan pengalaman yang terasa clean, aman, dan tetap terkontrol.</span>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">📅</div>
                        <div class="feature-text">
                            <strong>Task & calendar intelligence</strong>
                            <span>Mengelola agenda, pengingat, dan aktivitas dengan alur yang konsisten.</span>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">🧠</div>
                        <div class="feature-text">
                            <strong>Natural language ready</strong>
                            <span>Dirancang untuk menerima instruksi yang terasa natural dan mudah dipahami user.</span>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">✨</div>
                        <div class="feature-text">
                            <strong>Polished foundation</strong>
                            <span>Lebih dari sekadar backend — ini adalah inti produk yang siap berkembang.</span>
                        </div>
                    </div>
                </div>

                <div class="signature">Zaid Service — quiet luxury for intelligent productivity</div>
            </aside>
        </section>
    </main>

    <script>
        const particles = document.getElementById('particles');
        for (let i = 0; i < 24; i++) {
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