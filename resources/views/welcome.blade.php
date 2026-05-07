<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zaid</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        :root {
            --bg: #060312;
            --bg-2: #0b0619;
            --panel: rgba(255,255,255,0.04);
            --panel-border: rgba(180, 117, 255, 0.12);
            --text: #f8f7ff;
            --muted: #b8b1d1;
            --muted-2: #8e86aa;
            --purple: #7c3aed;
            --purple-2: #a855f7;
            --purple-3: #c084fc;
            --green: #20f07a;
            --shadow: 0 20px 80px rgba(90, 24, 170, 0.28);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            width: 100%;
            min-height: 100%;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 50% 52%, rgba(124, 58, 237, 0.20), transparent 20%),
                radial-gradient(circle at 18% 30%, rgba(168, 85, 247, 0.09), transparent 22%),
                radial-gradient(circle at 82% 18%, rgba(124, 58, 237, 0.08), transparent 18%),
                linear-gradient(180deg, var(--bg) 0%, var(--bg-2) 100%);
            overflow: hidden;
            position: relative;
        }

        .noise,
        .grid,
        .glow,
        .particles {
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        .grid {
            background-image:
                linear-gradient(rgba(192, 132, 252, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(192, 132, 252, 0.035) 1px, transparent 1px);
            background-size: 64px 64px;
            mask-image: radial-gradient(circle at center, black 38%, transparent 85%);
            -webkit-mask-image: radial-gradient(circle at center, black 38%, transparent 85%);
            opacity: 0.8;
        }

        .noise {
            opacity: 0.018;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        }

        .glow::before,
        .glow::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            filter: blur(100px);
        }

        .glow::before {
            width: 520px;
            height: 520px;
            left: 50%;
            top: 44%;
            transform: translate(-50%, -50%);
            background: radial-gradient(circle, rgba(124, 58, 237, 0.24), transparent 68%);
            animation: pulseGlow 7s ease-in-out infinite;
        }

        .glow::after {
            width: 320px;
            height: 320px;
            left: 50%;
            top: 57%;
            transform: translate(-50%, -50%);
            background: radial-gradient(circle, rgba(192, 132, 252, 0.12), transparent 72%);
            animation: pulseGlow 9s ease-in-out infinite reverse;
        }

        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--purple-3), var(--purple-2));
            box-shadow: 0 0 18px rgba(192, 132, 252, 0.7);
            opacity: 0;
            animation: floatUp linear infinite;
        }

        .page {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            width: 100%;
            display: flex;
            flex-direction: column;
            padding: 34px 42px 28px;
        }

        .nav {
            width: 100%;
            max-width: 1320px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            font-size: 34px;
            font-weight: 900;
            letter-spacing: -1.8px;
            background: linear-gradient(135deg, #ffffff 0%, var(--purple-3) 38%, var(--purple-2) 70%, #ddd6fe 100%);
            background-size: 220% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 8s linear infinite;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .nav-link {
            color: rgba(248, 247, 255, 0.88);
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            transition: opacity .25s ease, transform .25s ease;
        }

        .nav-link:hover { opacity: 0.8; transform: translateY(-1px); }

        .nav-btn {
            text-decoration: none;
            color: white;
            font-size: 15px;
            font-weight: 700;
            padding: 16px 28px;
            border-radius: 18px;
            background: linear-gradient(180deg, #7f35ff 0%, #6024d9 100%);
            border: 1px solid rgba(220, 190, 255, 0.14);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.20),
                0 12px 34px rgba(110, 42, 230, 0.42);
            transition: transform .25s ease, box-shadow .25s ease, filter .25s ease;
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.24),
                0 18px 44px rgba(110, 42, 230, 0.52);
            filter: brightness(1.04);
        }

        .hero {
            flex: 1;
            width: 100%;
            max-width: 1320px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .hero-inner {
            max-width: 900px;
            display: flex;
            flex-direction: column;
            align-items: center;
            animation: fadeUp .9s cubic-bezier(.16, 1, .3, 1);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 12px 22px;
            border-radius: 999px;
            background: rgba(124, 58, 237, 0.08);
            border: 1px solid rgba(192, 132, 252, 0.13);
            color: #d8b4fe;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-bottom: 28px;
            backdrop-filter: blur(10px);
        }

        .spark {
            font-size: 16px;
            filter: drop-shadow(0 0 10px rgba(192, 132, 252, 0.55));
            animation: twinkle 2.8s ease-in-out infinite;
        }

        h1 {
            font-size: clamp(60px, 8vw, 104px);
            line-height: 0.95;
            letter-spacing: -5px;
            font-weight: 900;
            margin-bottom: 26px;
        }

        h1 .line {
            display: block;
        }

        h1 .solid {
            color: #f9f8ff;
        }

        h1 .gradient {
            background: linear-gradient(135deg, #f5d0fe 0%, #d8b4fe 22%, var(--purple-3) 48%, var(--purple-2) 76%, #c4b5fd 100%);
            background-size: 220% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 10s linear infinite;
        }

        .subtitle {
            max-width: 760px;
            font-size: 18px;
            line-height: 1.8;
            color: var(--muted);
            margin-bottom: 36px;
        }

        .actions {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 42px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            min-width: 244px;
            padding: 18px 28px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 700;
            transition: transform .25s ease, box-shadow .25s ease, background .25s ease, border-color .25s ease;
        }

        .btn-primary {
            color: white;
            background: linear-gradient(180deg, #7f35ff 0%, #6024d9 100%);
            border: 1px solid rgba(220, 190, 255, 0.16);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.22),
                0 18px 44px rgba(99, 40, 210, 0.42);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.22),
                0 24px 54px rgba(99, 40, 210, 0.52);
        }

        .btn-secondary {
            color: #f3edff;
            background: rgba(255,255,255,0.025);
            border: 1px solid rgba(192, 132, 252, 0.14);
            backdrop-filter: blur(12px);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            background: rgba(168, 85, 247, 0.08);
            border-color: rgba(192, 132, 252, 0.24);
        }

        .status-row {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            color: var(--muted-2);
            font-size: 15px;
            font-weight: 500;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: var(--green);
            box-shadow: 0 0 12px rgba(32, 240, 122, 0.65);
            animation: pulseStatus 2.4s ease-in-out infinite;
        }

        .divider {
            width: 1px;
            height: 18px;
            background: rgba(255,255,255,0.08);
        }

        .frame {
            position: absolute;
            inset: 26px;
            border-radius: 30px;
            border: 1px solid rgba(255,255,255,0.025);
            pointer-events: none;
        }

        @keyframes shimmer {
            from { background-position: 0% center; }
            to { background-position: 200% center; }
        }

        @keyframes pulseGlow {
            0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.9; }
            50% { transform: translate(-50%, -50%) scale(1.08); opacity: 1; }
        }

        @keyframes floatUp {
            0% { transform: translateY(105vh) scale(.25); opacity: 0; }
            12% { opacity: .7; }
            88% { opacity: .12; }
            100% { transform: translateY(-12vh) scale(1); opacity: 0; }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulseStatus {
            0% { box-shadow: 0 0 0 0 rgba(32,240,122,0.55); }
            70% { box-shadow: 0 0 0 12px rgba(32,240,122,0); }
            100% { box-shadow: 0 0 0 0 rgba(32,240,122,0); }
        }

        @keyframes twinkle {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.9; }
            50% { transform: scale(1.15) rotate(8deg); opacity: 1; }
        }

        @media (max-width: 900px) {
            body { overflow: auto; }
            .page { padding: 22px; }
            .nav { gap: 16px; }
            .nav-right { gap: 12px; }
            .nav-btn { padding: 14px 20px; }
            .hero { padding: 20px 0 50px; }
            .subtitle { font-size: 16px; }
            .actions { flex-direction: column; width: 100%; align-items: center; }
            .btn { width: min(100%, 340px); }
        }

        @media (max-width: 640px) {
            .nav {
                flex-direction: column;
                align-items: flex-start;
                gap: 18px;
            }
            .nav-right {
                width: 100%;
                justify-content: space-between;
            }
            h1 { letter-spacing: -2.8px; }
            .eyebrow { font-size: 12px; padding: 10px 16px; }
            .status-row {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="grid"></div>
    <div class="noise"></div>
    <div class="glow"></div>
    <div class="particles" id="particles"></div>
    <div class="frame"></div>

    <main class="page">
        <header class="nav">
            <div class="brand">Zaid</div>
            <div class="nav-right">
                <a href="#" class="nav-link">Overview</a>
                <a href="/api/v1/health" class="nav-btn">Get Started</a>
            </div>
        </header>

        <section class="hero">
            <div class="hero-inner">
                <div class="eyebrow"><span class="spark">✦</span> AI Productivity Engine</div>

                <h1>
                    <span class="line solid">Simple outside.</span>
                    <span class="line gradient">Powerful inside.</span>
                </h1>

                <p class="subtitle">
                    Backend yang modern untuk pengalaman produktivitas yang cepat, stabil, aman, dan terasa seamless di balik setiap interaksi.
                </p>

                <div class="actions">
                    <a href="/api/v1/health" class="btn btn-primary">Get Started <span style="font-size:24px; line-height:0;">→</span></a>
                    <a href="#" class="btn btn-secondary">View Overview <span style="font-size:18px; opacity:.85;">⌘</span></a>
                </div>

                <div class="status-row">
                    <span class="status-dot"></span>
                    <span>All systems operational</span>
                    <span class="divider"></span>
                    <span>Private & production-ready</span>
                </div>
            </div>
        </section>
    </main>

    <script>
        const particles = document.getElementById('particles');
        for (let i = 0; i < 28; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            p.style.left = (Math.random() * 100) + '%';
            p.style.animationDuration = (10 + Math.random() * 14) + 's';
            p.style.animationDelay = (Math.random() * 12) + 's';
            const size = 2 + Math.random() * 3;
            p.style.width = size + 'px';
            p.style.height = size + 'px';
            particles.appendChild(p);
        }
    </script>
</body>
</html>