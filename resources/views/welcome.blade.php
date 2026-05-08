<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zaid</title>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
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
            --red: #ef4444;
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
            width: 420px; height: 420px; left: 50%; top: 42%; transform: translate(-50%, -50%);
            background: radial-gradient(circle, rgba(124,58,237,.22), transparent 70%);
            animation: pulseGlow 7s ease-in-out infinite;
        }
        .glow::after {
            width: 260px; height: 260px; left: 50%; top: 58%; transform: translate(-50%, -50%);
            background: radial-gradient(circle, rgba(216,180,254,.10), transparent 70%);
            animation: pulseGlow 9s ease-in-out infinite reverse;
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
            width: min(1080px, 100%);
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
            font-size: clamp(44px, 6vw, 74px);
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
            padding: 28px;
        }

        .panel h2 {
            font-size: 28px;
            letter-spacing: -1px;
            margin-bottom: 8px;
        }

        .panel p.lead {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 22px;
        }

        .steps {
            display: grid;
            gap: 14px;
        }

        .step {
            display: none;
            border-radius: 22px;
            padding: 18px;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(192,132,252,.10);
        }
        .step.active { display: block; }

        .step-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .step-desc {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.65;
            margin-bottom: 14px;
        }

        .field {
            display: grid;
            gap: 8px;
            margin-bottom: 12px;
        }

        .label {
            font-size: 12px;
            color: var(--soft);
            font-weight: 600;
            letter-spacing: .4px;
        }

        .input {
            width: 100%;
            border: 1px solid rgba(192,132,252,.14);
            background: rgba(255,255,255,.04);
            color: white;
            border-radius: 16px;
            padding: 14px 16px;
            outline: none;
            font-size: 14px;
            transition: border-color .2s ease, background .2s ease;
        }
        .input:focus {
            border-color: rgba(192,132,252,.35);
            background: rgba(255,255,255,.06);
        }

        .btn {
            width: 100%;
            border: 0;
            cursor: pointer;
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

        .inline-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .panel-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }

        .logout-btn {
            display: none;
            width: auto;
            padding: 10px 14px;
            border-radius: 14px;
            border: 1px solid rgba(192,132,252,.14);
            background: rgba(255,255,255,.04);
            color: #f3edff;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .logout-btn.show {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .status-box {
            margin-top: 16px;
            border-radius: 18px;
            padding: 14px 16px;
            background: rgba(255,255,255,.025);
            border: 1px solid rgba(192,132,252,.10);
            color: var(--soft);
            font-size: 13px;
            line-height: 1.65;
            white-space: pre-wrap;
        }

        .status-box.success { border-color: rgba(34,197,94,.25); color: #d1fae5; }
        .status-box.error { border-color: rgba(239,68,68,.24); color: #fecaca; }

        .user-meta {
            display: grid;
            gap: 8px;
            margin-bottom: 14px;
            color: var(--soft);
            font-size: 13px;
        }

        .tiny {
            margin-top: 12px;
            font-size: 12px;
            color: var(--muted);
        }

        @keyframes shimmer {
            from { background-position: 0% center; }
            to { background-position: 200% center; }
        }
        @keyframes pulseGlow {
            0%,100% { transform: translate(-50%, -50%) scale(1); opacity: .95; }
            50% { transform: translate(-50%, -50%) scale(1.08); opacity: 1; }
        }

        @media (max-width: 980px) {
            .shell { grid-template-columns: 1fr; }
            body { overflow: auto; }
        }
        @media (max-width: 640px) {
            .page { padding: 18px; }
            .hero, .panel { padding: 22px; }
            .hero-footer { grid-template-columns: 1fr; }
            .inline-actions { grid-template-columns: 1fr; }
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
                    <div class="badge"><span class="dot"></span> Web Onboarding</div>
                    <h1>
                        <span class="solid">Auth once.</span>
                        <span class="gradient">Connect everything.</span>
                    </h1>
                    <p>
                        Login dengan Google, verifikasi nomor HP, lalu sambungkan Google Calendar.
                        Semua flow dasar produk bisa lu tes langsung dari web ini.
                    </p>
                </div>

                <div class="hero-footer">
                    <div class="mini"><strong>1. Google Auth</strong><span>Masuk pakai akun Google user.</span></div>
                    <div class="mini"><strong>2. Verify No. HP</strong><span>Kirim OTP ke WhatsApp, fallback ke email kalau perlu.</span></div>
                    <div class="mini"><strong>3. Connect Calendar</strong><span>Opsional, dilakukan setelah onboarding selesai.</span></div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-top">
                    <h2>Simple test flow</h2>
                    <button class="logout-btn" id="btn-logout">Logout</button>
                </div>
                <p class="lead">Gunakan panel ini untuk ngetes flow utama: login, no HP, OTP, dan connect Google Calendar.</p>

                <div class="steps">
                    <div class="step active" id="step-auth">
                        <div class="step-title">Step 1 — Login / daftar</div>
                        <div class="step-desc">Pakai tombol Google di bawah ini untuk masuk. Setelah berhasil, sistem akan cek apakah user perlu isi nomor HP atau sudah bisa lanjut.</div>
                        <div id="google-signin-button"></div>
                        <div class="tiny">Google Client ID aktif akan dipakai dari konfigurasi backend.</div>
                    </div>

                    <div class="step" id="step-phone">
                        <div class="step-title">Step 2 — Input nomor HP</div>
                        <div class="step-desc">Masukkan nomor WhatsApp aktif. OTP akan dikirim ke WhatsApp dulu, lalu fallback ke email kalau kirim WA gagal.</div>
                        <div class="field">
                            <label class="label" for="phone_number">Nomor HP / WhatsApp</label>
                            <input class="input" id="phone_number" type="text" placeholder="0812xxxxxxx">
                        </div>
                        <button class="btn btn-primary" id="btn-submit-phone">Kirim OTP</button>
                    </div>

                    <div class="step" id="step-otp">
                        <div class="step-title">Step 3 — Verifikasi OTP</div>
                        <div class="step-desc">Masukkan OTP yang masuk ke WhatsApp atau email.</div>
                        <div class="field">
                            <label class="label" for="otp_code">Kode OTP</label>
                            <input class="input" id="otp_code" type="text" placeholder="123456">
                        </div>
                        <div class="inline-actions">
                            <button class="btn btn-primary" id="btn-verify-otp">Verifikasi OTP</button>
                            <button class="btn btn-secondary" id="btn-resend-otp">Kirim Ulang</button>
                        </div>
                    </div>

                    <div class="step" id="step-calendar">
                        <div class="step-title">Step 4 — Connect Google Calendar</div>
                        <div class="step-desc">Step ini optional. Kalau mau, sambungkan calendar sekarang. Kalau belum, user tetap sudah bisa lanjut pakai sistem.</div>
                        <div class="user-meta" id="user-meta"></div>
                        <div class="inline-actions">
                            <button class="btn btn-primary" id="btn-connect-calendar">Connect Google Calendar</button>
                            <button class="btn btn-secondary" id="btn-check-calendar">Check Status</button>
                        </div>
                        <div class="tiny">Kalau sudah connected, callback akan kembali ke halaman sukses lalu status bisa dicek lagi di sini.</div>
                    </div>
                </div>

                <div id="status-box" class="status-box">Belum ada aksi.</div>
            </div>
        </section>
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
        const stepCalendar = document.getElementById('step-calendar');
        const phoneInput = document.getElementById('phone_number');
        const otpInput = document.getElementById('otp_code');
        const userMeta = document.getElementById('user-meta');
        const logoutButton = document.getElementById('btn-logout');

        function showStep(step) {
            [stepAuth, stepPhone, stepOtp, stepCalendar].forEach(el => el.classList.remove('active'));
            step.classList.add('active');
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
            const user = authData.data.user;

            userMeta.innerHTML = `
                <div><strong>User:</strong> ${user.full_name || '-'} (${user.email})</div>
                <div><strong>Status:</strong> ${user.status}</div>
            `;

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

            showStep(stepCalendar);
            setStatus('Login dan onboarding dasar sudah selesai. Sekarang lu bisa connect Google Calendar kalau mau.', 'success');
        }

        async function refreshState() {
            const token = getToken();
            if (!token) {
                logoutButton.classList.remove('show');
                showStep(stepAuth);
                setStatus('Belum login. Mulai dari Google login.', '');
                return;
            }

            logoutButton.classList.add('show');

            try {
                const status = await api('/onboarding/status', {
                    method: 'GET',
                    headers: authHeaders(),
                });
                const me = await api('/me', {
                    method: 'GET',
                    headers: authHeaders(),
                });

                userMeta.innerHTML = `
                    <div><strong>User:</strong> ${me.data.full_name || '-'} (${me.data.email})</div>
                    <div><strong>Phone verified:</strong> ${me.data.phone_verified ? 'Yes' : 'No'}</div>
                    <div><strong>Status:</strong> ${me.data.status}</div>
                `;

                if (status.data.next_step === 'phone_input') {
                    showStep(stepPhone);
                    setStatus('Silakan isi nomor HP.', '');
                    return;
                }

                if (status.data.next_step === 'verify_otp') {
                    showStep(stepOtp);
                    setStatus('Silakan verifikasi OTP.', '');
                    return;
                }

                showStep(stepCalendar);
                setStatus('Onboarding selesai. Google Calendar bisa dihubungkan kapan saja.', 'success');
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
                setStatus('Google Client ID belum siap di backend.', 'error');
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

                showStep(stepCalendar);
                setStatus(result.message || 'OTP berhasil diverifikasi.', 'success');
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

        document.getElementById('btn-connect-calendar').addEventListener('click', async () => {
            try {
                const result = await api('/integrations/google-calendar/connect', {
                    method: 'GET',
                    headers: authHeaders(),
                });
                window.location.href = result.data.redirect_url;
            } catch (error) {
                setStatus(error.message || 'Gagal generate Google Calendar connect URL.', 'error');
            }
        });

        document.getElementById('btn-check-calendar').addEventListener('click', async () => {
            try {
                const result = await api('/integrations/google-calendar/status', {
                    method: 'GET',
                    headers: authHeaders(),
                });

                if (result.data.connected) {
                    setStatus('Google Calendar sudah connected.', 'success');
                } else {
                    setStatus('Google Calendar belum connected.', '');
                }
            } catch (error) {
                setStatus(error.message || 'Gagal cek status Google Calendar.', 'error');
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
                userMeta.innerHTML = '';
                showStep(stepAuth);
                setStatus('Berhasil logout. Silakan login lagi kalau mau lanjut test.', 'success');
            }
        });

        window.addEventListener('load', () => {
            initGoogleButton();
            refreshState();
        });
    </script>
</body>
</html>
