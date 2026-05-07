<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zaid — AI Task & Calendar Assistant</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0a1a;
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .bg-grid {
            position: fixed; inset: 0; z-index: 0;
            background-image:
                linear-gradient(rgba(108, 99, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(108, 99, 255, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        .bg-glow {
            position: fixed; top: -200px; left: 50%; transform: translateX(-50%);
            width: 800px; height: 800px; z-index: 0;
            background: radial-gradient(circle, rgba(108, 99, 255, 0.15) 0%, transparent 70%);
            filter: blur(80px);
        }

        .container { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; padding: 0 24px; }

        nav {
            display: flex; justify-content: space-between; align-items: center;
            padding: 24px 0;
        }
        .logo { font-size: 28px; font-weight: 800; color: #6C63FF; letter-spacing: -1px; }
        .nav-links { display: flex; gap: 24px; align-items: center; }
        .nav-links a {
            color: #888; text-decoration: none; font-size: 14px; font-weight: 500;
            transition: color 0.2s;
        }
        .nav-links a:hover { color: #fff; }
        .btn-docs {
            background: rgba(108, 99, 255, 0.1); border: 1px solid rgba(108, 99, 255, 0.3);
            color: #6C63FF; padding: 8px 20px; border-radius: 8px; font-size: 14px;
            text-decoration: none; font-weight: 600; transition: all 0.2s;
        }
        .btn-docs:hover { background: rgba(108, 99, 255, 0.2); border-color: #6C63FF; }

        .hero {
            text-align: center; padding: 100px 0 60px;
        }
        .badge {
            display: inline-block; background: rgba(108, 99, 255, 0.1);
            border: 1px solid rgba(108, 99, 255, 0.25); border-radius: 100px;
            padding: 6px 16px; font-size: 13px; color: #9D97FF; margin-bottom: 24px;
            font-weight: 500;
        }
        .hero h1 {
            font-size: 56px; font-weight: 800; line-height: 1.1;
            background: linear-gradient(135deg, #fff 0%, #9D97FF 50%, #6C63FF 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; margin-bottom: 20px; letter-spacing: -2px;
        }
        .hero p {
            font-size: 18px; color: #777; max-width: 560px; margin: 0 auto 40px;
            line-height: 1.7;
        }
        .hero-buttons { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
        .btn-primary {
            background: #6C63FF; color: #fff; padding: 14px 32px; border-radius: 12px;
            text-decoration: none; font-weight: 700; font-size: 15px;
            transition: all 0.2s; box-shadow: 0 4px 24px rgba(108, 99, 255, 0.3);
        }
        .btn-primary:hover { background: #5a52e0; transform: translateY(-1px); box-shadow: 0 6px 32px rgba(108, 99, 255, 0.4); }
        .btn-secondary {
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            color: #ccc; padding: 14px 32px; border-radius: 12px;
            text-decoration: none; font-weight: 600; font-size: 15px; transition: all 0.2s;
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.2); color: #fff; }

        .features { padding: 60px 0 80px; }
        .features-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
        }
        .feature-card {
            background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px; padding: 32px; transition: all 0.3s;
        }
        .feature-card:hover {
            background: rgba(108, 99, 255, 0.04); border-color: rgba(108, 99, 255, 0.15);
            transform: translateY(-2px);
        }
        .feature-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; margin-bottom: 16px;
            background: rgba(108, 99, 255, 0.1);
        }
        .feature-card h3 { font-size: 16px; font-weight: 700; margin-bottom: 8px; color: #eee; }
        .feature-card p { font-size: 14px; color: #666; line-height: 1.6; }

        .stats {
            display: flex; justify-content: center; gap: 60px; padding: 40px 0 80px;
            flex-wrap: wrap;
        }
        .stat { text-align: center; }
        .stat-value { font-size: 36px; font-weight: 800; color: #6C63FF; }
        .stat-label { font-size: 13px; color: #555; margin-top: 4px; font-weight: 500; }

        .api-preview {
            background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px; padding: 32px; margin-bottom: 80px; overflow-x: auto;
        }
        .api-preview h3 { font-size: 14px; color: #6C63FF; font-weight: 700; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 1px; }
        .api-table { width: 100%; border-collapse: collapse; }
        .api-table th { text-align: left; font-size: 12px; color: #555; padding: 8px 12px; border-bottom: 1px solid rgba(255,255,255,0.06); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .api-table td { padding: 10px 12px; font-size: 14px; border-bottom: 1px solid rgba(255,255,255,0.03); }
        .method { font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 4px; font-family: monospace; }
        .method-get { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
        .method-post { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .method-patch { background: rgba(249, 115, 22, 0.1); color: #f97316; }
        .method-delete { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .api-path { font-family: 'SF Mono', 'Fira Code', monospace; color: #aaa; font-size: 13px; }
        .api-desc { color: #555; font-size: 13px; }

        footer {
            text-align: center; padding: 40px 0; border-top: 1px solid rgba(255,255,255,0.04);
            color: #444; font-size: 13px;
        }
        footer a { color: #6C63FF; text-decoration: none; }

        @media (max-width: 768px) {
            .hero h1 { font-size: 36px; }
            .features-grid { grid-template-columns: 1fr; }
            .stats { gap: 32px; }
            nav { flex-direction: column; gap: 16px; }
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="bg-glow"></div>

    <div class="container">
        <nav>
            <div class="logo">Zaid</div>
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#api">API</a>
                <a href="/docs/api" class="btn-docs">API Docs →</a>
            </div>
        </nav>

        <section class="hero">
            <div class="badge">✨ AI-Powered Productivity Backend</div>
            <h1>Manage Tasks.<br>From Anywhere.</h1>
            <p>Zaid adalah backend service untuk AI task & calendar assistant. Kelola jadwal lewat mobile app atau WhatsApp, dengan natural language.</p>
            <div class="hero-buttons">
                <a href="/docs/api" class="btn-primary">Explore API Docs</a>
                <a href="/api/v1/health" class="btn-secondary">Health Check ↗</a>
            </div>
        </section>

        <div class="stats">
            <div class="stat">
                <div class="stat-value">28</div>
                <div class="stat-label">API Endpoints</div>
            </div>
            <div class="stat">
                <div class="stat-value">15</div>
                <div class="stat-label">Database Tables</div>
            </div>
            <div class="stat">
                <div class="stat-value">39</div>
                <div class="stat-label">Tests Passing</div>
            </div>
            <div class="stat">
                <div class="stat-value">2</div>
                <div class="stat-label">AI Models</div>
            </div>
        </div>

        <section id="features" class="features">
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🔐</div>
                    <h3>Google Auth + OTP</h3>
                    <p>Login cuma pakai Google. Verifikasi nomor HP via OTP email. Satu flow, aman, simpel.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📋</div>
                    <h3>Task & Calendar</h3>
                    <p>CRUD task lengkap dengan recurrence, agenda harian, calendar bulanan, dan audit log.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🤖</div>
                    <h3>AI Prompt Engine</h3>
                    <p>Ketik perintah bahasa Indonesia, AI parsing intent dan entity, task otomatis terbuat.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💬</div>
                    <h3>WhatsApp Integration</h3>
                    <p>Webhook WA Cloud API, sender matching, bot reply otomatis. Chat = aksi.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🖼️</div>
                    <h3>Multimodal Support</h3>
                    <p>Dual-model parser: text murah (MiniMax), image + voice otomatis switch ke Gemini.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>Production Ready</h3>
                    <p>Rate limiting, standardized error envelope, Sanctum auth, soft delete, dan Scramble API docs.</p>
                </div>
            </div>
        </section>

        <section id="api" class="api-preview">
            <h3>API Endpoints</h3>
            <table class="api-table">
                <thead>
                    <tr><th>Method</th><th>Endpoint</th><th>Description</th></tr>
                </thead>
                <tbody>
                    <tr><td><span class="method method-post">POST</span></td><td class="api-path">/api/v1/auth/google</td><td class="api-desc">Login / register with Google</td></tr>
                    <tr><td><span class="method method-post">POST</span></td><td class="api-path">/api/v1/auth/refresh</td><td class="api-desc">Refresh access token</td></tr>
                    <tr><td><span class="method method-post">POST</span></td><td class="api-path">/api/v1/auth/logout</td><td class="api-desc">Revoke current token</td></tr>
                    <tr><td><span class="method method-post">POST</span></td><td class="api-path">/api/v1/onboarding/phone</td><td class="api-desc">Submit phone number, send OTP</td></tr>
                    <tr><td><span class="method method-post">POST</span></td><td class="api-path">/api/v1/onboarding/phone/verify</td><td class="api-desc">Verify OTP code</td></tr>
                    <tr><td><span class="method method-get">GET</span></td><td class="api-path">/api/v1/me</td><td class="api-desc">Current user profile</td></tr>
                    <tr><td><span class="method method-patch">PATCH</span></td><td class="api-path">/api/v1/me</td><td class="api-desc">Update profile</td></tr>
                    <tr><td><span class="method method-get">GET</span></td><td class="api-path">/api/v1/tasks</td><td class="api-desc">List tasks (filterable)</td></tr>
                    <tr><td><span class="method method-post">POST</span></td><td class="api-path">/api/v1/tasks</td><td class="api-desc">Create task</td></tr>
                    <tr><td><span class="method method-patch">PATCH</span></td><td class="api-path">/api/v1/tasks/{id}</td><td class="api-desc">Update task</td></tr>
                    <tr><td><span class="method method-delete">DEL</span></td><td class="api-path">/api/v1/tasks/{id}</td><td class="api-desc">Delete task (soft)</td></tr>
                    <tr><td><span class="method method-get">GET</span></td><td class="api-path">/api/v1/agenda/day</td><td class="api-desc">Daily agenda</td></tr>
                    <tr><td><span class="method method-get">GET</span></td><td class="api-path">/api/v1/calendar/month</td><td class="api-desc">Monthly calendar summary</td></tr>
                    <tr><td><span class="method method-post">POST</span></td><td class="api-path">/api/v1/prompts</td><td class="api-desc">AI prompt (text/image/voice)</td></tr>
                    <tr><td><span class="method method-post">POST</span></td><td class="api-path">/api/v1/upload</td><td class="api-desc">File upload</td></tr>
                    <tr><td><span class="method method-post">POST</span></td><td class="api-path">/api/v1/webhooks/whatsapp</td><td class="api-desc">WhatsApp webhook handler</td></tr>
                </tbody>
            </table>
        </section>

        <footer>
            <p>Zaid Service v1.0 — Built with Laravel, PostgreSQL & AI</p>
            <p style="margin-top: 8px;">
                <a href="/docs/api">API Documentation</a> · <a href="/api/v1/health">Health</a>
            </p>
        </footer>
    </div>
</body>
</html>
