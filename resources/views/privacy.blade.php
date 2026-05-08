<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy — Zaid</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #0b0619;
            color: #f8f7ff;
            line-height: 1.75;
            padding: 40px 20px;
        }
        .container {
            max-width: 860px;
            margin: 0 auto;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(192, 132, 252, 0.14);
            border-radius: 24px;
            padding: 36px;
            box-shadow: 0 20px 60px rgba(90, 24, 170, 0.22);
        }
        .brand {
            font-size: 30px;
            font-weight: 900;
            letter-spacing: -1.4px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #ffffff 0%, #d8b4fe 40%, #a855f7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        h1 { font-size: 32px; margin-bottom: 12px; }
        h2 { font-size: 20px; margin: 28px 0 10px; }
        p, li { color: #d7d0e8; font-size: 15px; }
        ul { padding-left: 20px; }
        .muted { color: #9b92b6; font-size: 13px; }
        a { color: #c084fc; text-decoration: none; }
        .back { display: inline-block; margin-top: 28px; }
    </style>
</head>
<body>
    <main class="container">
        <div class="brand">Zaid</div>
        <h1>Privacy Policy</h1>
        <p class="muted">Last updated: {{ now()->format('F j, Y') }}</p>

        <p style="margin-top: 18px;">
            Zaid menghargai privasi pengguna. Kebijakan privasi ini menjelaskan bagaimana Zaid mengumpulkan,
            menggunakan, menyimpan, dan melindungi informasi pengguna saat menggunakan layanan kami.
        </p>

        <h2>1. Informasi yang kami kumpulkan</h2>
        <p>Kami dapat mengumpulkan informasi berikut:</p>
        <ul>
            <li>Informasi akun seperti nama, email, dan identitas Google.</li>
            <li>Nomor HP / WhatsApp yang digunakan untuk verifikasi dan interaksi layanan.</li>
            <li>Data task, jadwal, agenda, dan input prompt yang Anda kirim ke sistem.</li>
            <li>Data integrasi seperti Google Calendar jika Anda memilih menghubungkannya.</li>
        </ul>

        <h2>2. Cara kami menggunakan informasi</h2>
        <ul>
            <li>Mengautentikasi pengguna dan mengamankan akun.</li>
            <li>Menyediakan fitur task, agenda, pengingat, dan sinkronisasi layanan.</li>
            <li>Memproses perintah yang dikirim melalui aplikasi atau WhatsApp.</li>
            <li>Meningkatkan kualitas layanan, keamanan, dan pengalaman pengguna.</li>
        </ul>

        <h2>3. Penyimpanan dan keamanan data</h2>
        <p>
            Kami berupaya menjaga keamanan data pengguna dengan mekanisme autentikasi, pembatasan akses,
            logging, dan pengamanan infrastruktur yang wajar. Namun, tidak ada sistem yang sepenuhnya bebas risiko.
        </p>

        <h2>4. Integrasi pihak ketiga</h2>
        <p>
            Layanan Zaid dapat menggunakan layanan pihak ketiga seperti Google OAuth, Google Calendar,
            WhatsApp, penyedia email, dan penyedia model AI untuk memberikan fungsionalitas tertentu.
        </p>

        <h2>5. Pembagian data</h2>
        <p>
            Kami tidak menjual data pribadi pengguna. Data hanya digunakan untuk kebutuhan operasional layanan,
            integrasi yang Anda setujui, atau kewajiban hukum jika diperlukan.
        </p>

        <h2>6. Hak pengguna</h2>
        <p>
            Pengguna dapat meminta perubahan, pembaruan, atau penghapusan data tertentu sesuai kebijakan layanan
            dan kemampuan sistem yang tersedia.
        </p>

        <h2>7. Perubahan kebijakan</h2>
        <p>
            Kebijakan privasi ini dapat diperbarui dari waktu ke waktu. Perubahan signifikan akan ditampilkan
            pada halaman ini.
        </p>

        <h2>8. Kontak</h2>
        <p>
            Jika Anda memiliki pertanyaan terkait privasi, silakan hubungi kami di
            <a href="mailto:zaidassist@gmail.com">zaidassist@gmail.com</a>.
        </p>

        <a class="back" href="/">← Kembali ke Zaid</a>
    </main>
</body>
</html>
