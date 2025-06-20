<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Auth Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .code-box {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-family: monospace;
            word-break: break-all;
            margin: 20px 0;
        }
        .info {
            background: #e6f7ff;
            border-left: 4px solid #1890ff;
            padding: 10px 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Google Authorization Code</h1>
    
    <div class="info">
        Authorization code berhasil diterima. Gunakan kode berikut untuk mendapatkan refresh token.
    </div>
    
    <div class="code-box">
        {{ $code }}
    </div>
    
    <p>Instruksi:</p>
    <ol>
        <li>Salin kode di atas</li>
        <li>Gunakan kode tersebut di script refresh_token.php Anda</li>
        <li>Jalankan: <code>php refresh_token.php [kode_yang_disalin]</code></li>
        <li>Simpan refresh token yang dihasilkan di file .env</li>
    </ol>
</body>
</html>