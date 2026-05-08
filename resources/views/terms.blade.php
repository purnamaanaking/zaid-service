<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service — Zaid</title>
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
        <h1>Terms of Service</h1>
        <p class="muted">Last updated: {{ now()->format('F j, Y') }}</p>

        <p style="margin-top: 18px;">
            Dengan menggunakan layanan Zaid, Anda setuju untuk terikat oleh syarat dan ketentuan berikut.
            Jika Anda tidak setuju, mohon untuk tidak menggunakan layanan ini.
        </p>

        <h2>1. Penggunaan layanan</h2>
        <p>
            Zaid menyediakan layanan berbasis produktivitas untuk membantu pengguna mengelola task,
            agenda, pengingat, dan integrasi tertentu. Pengguna bertanggung jawab atas penggunaan akun
            dan data yang mereka kirimkan ke sistem.
        </p>

        <h2>2. Akun pengguna</h2>
        <ul>
            <li>Pengguna wajib menggunakan informasi yang benar saat login dan onboarding.</li>
            <li>Pengguna bertanggung jawab menjaga keamanan akses akun mereka.</li>
            <li>Zaid berhak membatasi akses bila ditemukan penyalahgunaan atau aktivitas mencurigakan.</li>
        </ul>

        <h2>3. Integrasi pihak ketiga</h2>
        <p>
            Beberapa fitur menggunakan layanan pihak ketiga seperti Google, Google Calendar, WhatsApp,
            email provider, atau penyedia model AI. Penggunaan layanan tersebut juga tunduk pada kebijakan
            masing-masing penyedia.
        </p>

        <h2>4. Batasan penggunaan</h2>
        <ul>
            <li>Pengguna tidak boleh menggunakan layanan untuk aktivitas melanggar hukum.</li>
            <li>Pengguna tidak boleh mencoba merusak, mengeksploitasi, atau mengganggu stabilitas sistem.</li>
            <li>Pengguna tidak boleh menyalahgunakan integrasi atau otomatisasi untuk spam atau abuse.</li>
        </ul>

        <h2>5. Ketersediaan layanan</h2>
        <p>
            Kami berupaya menjaga layanan tetap tersedia dan andal, namun tidak menjamin layanan selalu
            bebas gangguan, bug, atau downtime.
        </p>

        <h2>6. Batas tanggung jawab</h2>
        <p>
            Layanan disediakan sebagaimana adanya. Zaid tidak bertanggung jawab atas kerugian tidak langsung,
            kehilangan data, atau dampak lain yang timbul dari penggunaan layanan, sejauh diizinkan oleh hukum.
        </p>

        <h2>7. Perubahan layanan</h2>
        <p>
            Kami dapat memperbarui, menyesuaikan, atau menghentikan sebagian fitur layanan sewaktu-waktu
            untuk pengembangan produk, pemeliharaan, atau alasan operasional lain.
        </p>

        <h2>8. Perubahan syarat</h2>
        <p>
            Syarat layanan ini dapat diperbarui dari waktu ke waktu. Versi terbaru akan tersedia di halaman ini.
        </p>

        <h2>9. Kontak</h2>
        <p>
            Untuk pertanyaan terkait syarat layanan, silakan hubungi
            <a href="mailto:zaidassist@gmail.com">zaidassist@gmail.com</a>.
        </p>

        <a class="back" href="/">← Kembali ke Zaid</a>
    </main>
</body>
</html>
