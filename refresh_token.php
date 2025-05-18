<?php
// refresh_token.php

require 'vendor/autoload.php';

// Konfigurasi client
$client = new Google_Client();
$client->setClientId('743383905190-nnrpgopctah86430kjthheenbongla0h.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost:8000/auth/google/callback');
$client->setAccessType('offline'); // Ini penting untuk mendapatkan refresh token
$client->setApprovalPrompt('force'); // Ini memastikan selalu mendapatkan refresh token baru
$client->setScopes([
    Google_Service_Drive::DRIVE,
    Google_Service_Drive::DRIVE_FILE,
]);

// Langkah 1: Dapatkan authorization URL
$authUrl = $client->createAuthUrl();
echo "Buka URL ini di browser Anda: " . $authUrl . "\n";
echo "Masukkan kode yang didapat setelah redirect: ";
$authCode = trim(fgets(STDIN));

// Langkah 2: Exchange authorization code untuk access token dan refresh token
try {
    $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
    $client->setAccessToken($accessToken);
    
    if (array_key_exists('refresh_token', $accessToken)) {
        echo "Refresh Token: " . $accessToken['refresh_token'] . "\n";
        echo "Simpan refresh token ini di file .env Anda sebagai GOOGLE_DRIVE_REFRESH_TOKEN.\n";
    } else {
        echo "Tidak mendapatkan refresh token. Coba revoke akses aplikasi di https://myaccount.google.com/permissions dan coba lagi.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}