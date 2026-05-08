<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zaid — AI Productivity Assistant</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        :root {
            --bg: #070312;
            --bg-2: #12071f;
            --panel: rgba(255,255,255,0.05);
            --border: rgba(192,132,252,0.16);
            --text: #f8f7ff;
            --soft: #d7d0e8;
            --muted: #9b92b6;
            --purple: #7c3aed;
            --purple-2: #a855f7;
            --purple-3: #d8b4fe;
            --green: #22c55e;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 20% 20%, rgba(124,58,237,.18), transparent 25%),
                radial-gradient(circle at 80% 10%, rgba(168,85,247,.14), transparent 20%),
                radial-gradient(circle at 50% 100%, rgba(88,28,135,.45), transparent 35%),
                linear-gradient(180deg, var(--bg), var(--bg-2));
            overflow-x: hidden;
            position: relative;
        }

        .grid, .glow {
            position: fixed; inset: 0; pointer-events: none;
        }
        .grid {
            background-image:
                linear-gradient(rgba(192,132,252,.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(192,132,252,.035) 1px, transparent 1px);
            background-size: 62px 62px;
            mask-image: radial-gradient(circle at center, black 40%, transparent 85%);
            -webkit-mask-image: radial-gradient(circle at center, black 40%, transparent 85%);
        }
        .glow::before, .glow::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            filter: blur(100px);
        }
        .glow::before {
            width: 420px; height: 420px; left: 50%; top: 35%; transform: translate(-50%, -50%);
            background: radial-gradient(circle, rgba(124,58,237,.22), transparent 70%);
        }
        .glow::after {
            width: 260px; height: 260px; left: 65%; top: 65%; transform: translate(-50%, -50%);
            background: radial-gradient(circle, rgba(216,180,254,.10), transparent 70%);
        }

        .page {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px;
        }

        .shell {
            width: min(1160px, 100%);
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 24px;
        }

        .hero, .panel {
            border-radius: 28px;
            border: 1px solid var(--border);
            background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03));
            box-shadow: 0 24px 80px rgba(88, 28, 135, .22), inset 0 1px 0 rgba(255,255,255,.06);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .hero {
            padding: 36px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .brand {
            font-size: 34px;
            font-weight: 900;
            letter-spacing: -1.8px;
            background: linear-gradient(135deg, #ffffff 0%, var(--purple-3) 30%, var(--purple-2) 70%, #ddd6fe 100%);
            background-size: 220% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 8s linear infinite;
            margin-bottom: 18px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(124,58,237,.10);
            border: 1px solid rgba(192,132,252,.15);
            color: var(--purple-3);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .8px;
            text-transform: uppercase;
            margin-bottom: 24px;
        }

        .dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--purple-3);
            box-shadow: 0 0 14px rgba(192,132,252,.7);
        }

        h1 {
            font-size: clamp(46px, 6vw, 76px);
            line-height: .96;
            letter-spacing: -3.5px;
            font-weight: 900;
            margin-bottom: 18px;
        }
        h1 .solid { display: block; color: white; }
        h1 .gradient {
            display: block;
            background: linear-gradient(135deg, #f5d0fe 0%, var(--purple-3) 25%, var(--purple-2) 60%, #c4b5fd 100%);
            background-size: 220% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 10s linear infinite;
        }

        .hero p {
            color: var(--soft);
            font-size: 17px;
            line-height: 1.8;
            max-width: 560px;
        }

        .hero-footer {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-top: 34px;
        }

        .mini {
            border-radius: 20px;
            padding: 18px 16px;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(192,132,252,.10);
        }
        .mini strong {
            display: block;
            font-size: 14px;
            margin-bottom: 6px;
        }
        .mini span {
            display: block;
            font-size: 13px;
            line-height: 1.6;
            color: var(--muted);
        }

        .panel {
            padding: 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 20px;
        }

        .panel h2 {
            font-size: 28px;
            letter-spacing: -1px;
            margin-bottom: 6px;
        }

        .panel p {
            color: var(--soft);
            font-size: 15px;
            line-height: 1.8;
        }

        .list {
            display: grid;
            gap: 14px;
            margin-top: 6px;
        }

        .item {
            border-radius: 18px;
            padding: 16px 18px;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(192,132,252,.10);
        }
        .item strong {
            display: block;
            font-size: 14px;
            margin-bottom: 4px;
        }
        .item span {
            display: block;
            font-size: 13px;
            color: var(--muted);
            line-height: 1.65;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border-radius: 18px;
            padding: 14px 18px;
            font-size: 14px;
            font-weight: 700;
            transition: transform .22s ease, box-shadow .22s ease, opacity .22s ease;
        }

        .btn-primary {
            color: white;
            background: linear-gradient(180deg, #7f35ff 0%, #6024d9 100%);
            box-shadow: 0 12px 34px rgba(110,42,230,.36);
        }
        .btn-primary:hover { transform: translateY(-1px); }

        .btn-secondary {
            color: #f3edff;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(192,132,252,.14);
        }

        .legal-links {
            display: flex;
            gap: 18px;
            margin-top: 4px;
            font-size: 13px;
        }
        .legal-links a {
            color: var(--muted);
            text-decoration: none;
        }
        .legal-links a:hover { color: var(--purple-3); }

        @keyframes shimmer {
            from { background-position: 0% center; }
            to { background-position: 200% center; }
        }

        @media (max-width: 980px) {
            .shell { grid-template-columns: 1fr; }
            body { overflow: auto; }
        }
        @media (max-width: 640px) {
            .page { padding: 18px; }
            .hero, .panel { padding: 22px; }
            .hero-footer { grid-template-columns: 1fr; }
            .actions { flex-direction: column; }
            h1 { letter-spacing: -2px; }
        }
    </style>
</head>
<body>
    <div class="grid"></div>
    <div class="glow"></div>

    <main class="page">
        <section class="shell">
            <div class="hero">
                <div>
                    <div class="brand">Zaid</div>
                    <div class="badge"><span class="dot"></span> AI Productivity Assistant</div>
                    <h1>
                        <span class="solid">Simple outside.</span>
                        <span class="gradient">Powerful inside.</span>
                    </h1>
                    <p>
                        Zaid membantu pengguna mengelola task, agenda, pengingat, dan produktivitas harian melalui aplikasi serta interaksi natural. Sistem menggunakan Google login untuk identitas akun, verifikasi nomor HP untuk keamanan, dan integrasi kalender sebagai fitur tambahan opsional.
                    </p>
                </div>

                <div class="hero-footer">
                    <div class="mini"><strong>Secure onboarding</strong><span>Login Google, verifikasi nomor HP, lalu mulai gunakan layanan dengan aman.</span></div>
                    <div class="mini"><strong>Task & schedule flow</strong><span>Mengelola task, agenda, dan reminder dalam satu alur kerja yang konsisten.</span></div>
                    <div class="mini"><strong>Optional integrations</strong><span>Pengguna bisa memilih menghubungkan kalender untuk sinkronisasi tambahan.</span></div>
                </div>
            </div>

            <div class="panel">
                <h2>What Zaid does</h2>
                <p>
                    Zaid dirancang sebagai asisten produktivitas modern yang memusatkan pengelolaan task dan agenda dalam satu sistem. Aplikasi ini meminta akses akun dasar Google untuk autentikasi pengguna, nomor HP untuk verifikasi identitas, dan akses kalender hanya jika pengguna memilih mengaktifkan integrasi tambahan.
                </p>

                <div class="list">
                    <div class="item">
                        <strong>Google account access</strong>
                        <span>Dipakai untuk login, identitas akun, dan memastikan akses pengguna tetap aman.</span>
                    </div>
                    <div class="item">
                        <strong>Phone verification</strong>
                        <span>Dipakai untuk menghubungkan nomor pengguna ke layanan dan menjaga keamanan onboarding.</span>
                    </div>
                    <div class="item">
                        <strong>Calendar connection</strong>
                        <span>Opsional. Hanya digunakan jika pengguna ingin sinkronisasi jadwal dengan kalender mereka.</span>
                    </div>
                </div>

                <div class="actions">
                    <a class="btn btn-primary" href="/app">Open App Flow</a>
                    <a class="btn btn-secondary" href="/privacy">Privacy Policy</a>
                    <a class="btn btn-secondary" href="/terms">Terms of Service</a>
                </div>

                <div class="legal-links">
                    <a href="mailto:zaidassist@gmail.com">zaidassist@gmail.com</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
