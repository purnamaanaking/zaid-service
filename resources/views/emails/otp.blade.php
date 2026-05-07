<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f7; padding: 20px; }
        .container { max-width: 480px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 40px 32px; }
        .logo { text-align: center; margin-bottom: 24px; font-size: 28px; font-weight: bold; color: #6C63FF; }
        .otp-code { text-align: center; font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #1a1a2e; margin: 24px 0; padding: 16px; background: #f0f0ff; border-radius: 8px; }
        .message { color: #555; font-size: 15px; line-height: 1.6; text-align: center; }
        .footer { text-align: center; margin-top: 24px; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">Zaid</div>

        <p class="message">Halo <strong>{{ $userName }}</strong>,</p>

        <p class="message">Gunakan kode berikut untuk verifikasi nomor HP kamu:</p>

        <div class="otp-code">{{ $otpCode }}</div>

        <p class="message">Kode ini berlaku selama <strong>5 menit</strong>.<br>Jangan bagikan kode ini kepada siapapun.</p>

        <div class="footer">
            &copy; {{ date('Y') }} Zaid &mdash; AI Task & Calendar Assistant
        </div>
    </div>
</body>
</html>
