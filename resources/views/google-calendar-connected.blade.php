<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Google Calendar Connected</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: Inter, system-ui, sans-serif;
            background: #0b0619;
            color: #f8f7ff;
        }
        .card {
            width: min(560px, calc(100% - 32px));
            padding: 32px;
            border-radius: 24px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(192, 132, 252, 0.14);
            text-align: center;
            box-shadow: 0 20px 60px rgba(90, 24, 170, 0.28);
        }
        h1 {
            margin: 0 0 12px;
            font-size: 32px;
        }
        p {
            margin: 0;
            line-height: 1.7;
            color: #d8d0ef;
        }
        .ok {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 999px;
            margin-bottom: 18px;
            background: rgba(32, 240, 122, 0.14);
            color: #20f07a;
            font-size: 28px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="ok">✓</div>
        <h1>Google Calendar connected</h1>
        <p>Koneksi Google Calendar berhasil disimpan. Sekarang Anda bisa kembali ke aplikasi dan lanjut memakai sinkronisasi kalender.</p>
    </main>
</body>
</html>
