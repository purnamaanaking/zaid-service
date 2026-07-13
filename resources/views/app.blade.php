<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zaid Assistant Onboarding</title>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,700;9..144,800&family=Space+Grotesk:wght@400;500;600;700&display=swap');

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

        .page { position: relative; z-index: 1; min-height: 100vh; padding: 28px; display: grid; place-items: center; }
        .shell { width: min(1120px, 100%); display: grid; grid-template-columns: .95fr 1.05fr; gap: 22px; align-items: stretch; }
        .hero, .panel {
            border: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(255,255,255,.075), rgba(255,255,255,.028));
            box-shadow: var(--shadow), inset 0 1px 0 rgba(255,255,255,.055);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }
        .hero { border-radius: 36px; padding: clamp(26px, 4vw, 42px); display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; position: relative; }
        .hero::before {
            content: ""; position: absolute; inset: 90px -80px auto auto; width: 360px; height: 360px;
            border-radius: 999px; border: 1px dashed rgba(216,180,254,.18);
            box-shadow: inset 0 0 70px rgba(168,85,247,.08);
        }
        .brand { display: inline-flex; align-items: baseline; gap: 6px; margin-bottom: 22px; color: var(--soft); font-size: 20px; font-weight: 550; letter-spacing: -.055em; text-decoration: none; }
        .brand strong { color: var(--lavender); font-weight: 800; }
        .badge { display: inline-flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 999px; border: 1px solid rgba(216,180,254,.24); background: rgba(216,180,254,.08); color: var(--lavender); font-size: 12px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; margin-bottom: 22px; }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--lavender); box-shadow: 0 0 0 8px rgba(216,180,254,.12), 0 0 22px rgba(216,180,254,.75); }
        h1 { font-family: 'Fraunces', Georgia, serif; font-size: clamp(46px, 6vw, 82px); line-height: .9; letter-spacing: -.06em; margin-bottom: 22px; }
        h1 .solid, h1 .gradient { display: block; }
        h1 .gradient { color: var(--lavender); text-shadow: 0 0 40px rgba(216,180,254,.22); }
        .hero p { color: var(--soft); font-size: 17px; line-height: 1.75; max-width: 560px; }
        .hero-footer { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 38px; }
        .mini { border-radius: 22px; padding: 18px 16px; background: rgba(255,255,255,.045); border: 1px solid var(--line); }
        .mini strong { display: block; font-size: 14px; margin-bottom: 7px; color: var(--soft); }
        .mini span { display: block; font-size: 13px; line-height: 1.55; color: var(--muted); }

        .panel { border-radius: 32px; padding: clamp(22px, 3vw, 32px); }
        .panel-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
        .panel h2 { font-family: 'Fraunces', Georgia, serif; font-size: 34px; letter-spacing: -.05em; }
        .panel p.lead { color: var(--muted); font-size: 14px; line-height: 1.75; margin-bottom: 22px; }
        .logout-btn { display: none; width: auto; padding: 10px 14px; border-radius: 999px; border: 1px solid var(--line); background: rgba(255,255,255,.05); color: var(--soft); font-size: 12px; font-weight: 800; cursor: pointer; }
        .logout-btn.show { display: inline-flex; align-items: center; justify-content: center; }
        .steps { display: grid; gap: 14px; }
        .step { display: none; border-radius: 24px; padding: 20px; background: rgba(12,4,23,.58); border: 1px solid rgba(216,180,254,.14); box-shadow: inset 0 1px 0 rgba(255,255,255,.04); }
        .step.active { display: block; }
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
        .legal-links { width: min(1120px, 100%); display: flex; gap: 18px; padding: 2px 6px 0; color: var(--muted); font-size: 13px; }
        .legal-links a { color: inherit; text-decoration: none; }
        .legal-links a:hover { color: var(--lavender); }

        @media (max-width: 980px) {
            .page { place-items: start center; }
            .shell { grid-template-columns: 1fr; }
            .hero-footer { grid-template-columns: 1fr 1fr 1fr; }
        }
        @media (max-width: 640px) {
            .page { padding: 16px; }
            .hero, .panel { border-radius: 26px; }
            .hero-footer, .inline-actions { grid-template-columns: 1fr; }
            h1 { font-size: clamp(42px, 15vw, 62px); }
            .panel-top { align-items: flex-start; }
            .bg-orb { opacity: .5; right: -340px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; }
            body::before, body::after, .bg-orb { transform: none !important; }
        }
    </style>
</head>
<body>
    <div class="bg-orb" aria-hidden="true"></div>

    <main class="page">
        <section class="shell">
            <div class="hero">
                <div>
                    <a class="brand" href="/" aria-label="Zaid Assistant home"><span>Zaid</span><strong>Assistant</strong></a>
                    <div class="badge"><span class="dot"></span> Web Onboarding</div>
                    <h1>
                        <span class="solid">Auth once.</span>
                        <span class="gradient">Connect everything.</span>
                    </h1>
                    <p>
                        Login dengan Google, verifikasi nomor HP, lalu sambungkan Google Calendar & Tasks.
                        Semua flow dasar produk bisa lu tes langsung dari web ini.
                    </p>
                </div>

                <div class="hero-footer">
                    <div class="mini"><strong>1. Google Auth</strong><span>Masuk pakai akun Google user.</span></div>
                    <div class="mini"><strong>2. Verify No. HP</strong><span>Kirim OTP ke WhatsApp, fallback ke email kalau perlu.</span></div>
                    <div class="mini"><strong>3. Connect Calendar & Tasks</strong><span>Opsional, dilakukan setelah onboarding selesai.</span></div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-top">
                    <h2>Simple test flow</h2>
                    <button class="logout-btn" id="btn-logout">Logout</button>
                </div>
                <p class="lead">Gunakan panel ini untuk ngetes flow utama: login, no HP, OTP, dan connect Google Calendar & Tasks.</p>

                <div class="steps">
                    <div class="step active" id="step-auth">
                        <div class="step-title">Continue with Google</div>
                        <div class="step-desc">Sign in securely with your Google account. After sign-in, Zaid Assistant checks whether phone verification is still required.</div>
                        <div id="google-signin-button"></div>
                        <div class="tiny">Google Client ID aktif akan dipakai dari konfigurasi backend.</div>
                    </div>

                    <div class="step" id="step-phone">
                        <div class="step-title">Verify your phone</div>
                        <div class="step-desc">Masukkan nomor WhatsApp aktif. OTP akan dikirim ke WhatsApp dulu, lalu fallback ke email kalau kirim WA gagal.</div>
                        <div class="field">
                            <label class="label" for="phone_number">Nomor HP / WhatsApp</label>
                            <input class="input" id="phone_number" type="text" placeholder="0812xxxxxxx">
                        </div>
                        <button class="btn btn-primary" id="btn-submit-phone">Kirim OTP</button>
                    </div>

                    <div class="step" id="step-otp">
                        <div class="step-title">Verify OTP</div>
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
                        <div class="step-title">Connect Calendar and Tasks</div>
                        <div class="step-desc">Connect Google Calendar and Google Tasks after verification. Zaid Assistant uses these permissions only for actions you directly request.</div>
                        <div class="user-meta" id="user-meta"></div>
                        <div class="inline-actions">
                            <button class="btn btn-primary" id="btn-connect-calendar">Connect Calendar & Tasks</button>
                            <button class="btn btn-secondary" id="btn-check-calendar">Check Status</button>
                        </div>
                        <div class="tiny">Kalau sudah connected sebelumnya tapi mau tambah permission Tasks, klik Connect lagi untuk reconnect.</div>
                    </div>
                </div>

                <div id="status-box" class="status-box">Belum ada aksi.</div>
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
            setStatus('Login dan onboarding dasar sudah selesai. Sekarang lu bisa connect Google Calendar & Tasks kalau mau.', 'success');
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
                setStatus('Onboarding selesai. Google Calendar & Tasks bisa dihubungkan kapan saja.', 'success');
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
                    setStatus('Google Calendar & Tasks sudah connected.', 'success');
                } else {
                    setStatus('Google Calendar & Tasks belum connected.', '');
                }
            } catch (error) {
                setStatus(error.message || 'Gagal cek status Google Calendar & Tasks.', 'error');
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
