<?php

// config/pdf.php - Buat file konfigurasi baru
return [
    'temp_path' => storage_path('app/temp/'),
    'max_file_size' => 10240, // KB
    'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'doc', 'docx', 'xls', 'xlsx'],
    'cleanup_interval' => 3600, // seconds (1 hour)
    
    // DomPDF settings
    'dompdf' => [
        'default_font' => 'Arial',
        'paper_size' => 'A4',
        'orientation' => 'portrait',
        'enable_php' => true,
        'enable_remote' => true,
    ],
    
    // Image settings
    'image' => [
        'max_width' => 800,
        'max_height' => 600,
        'quality' => 85,
    ]
];

// .env - Tambahkan konfigurasi ini
/*
# PDF Generation Settings
PDF_TEMP_PATH=storage/app/temp/
PDF_MAX_FILE_SIZE=10240
PDF_CLEANUP_INTERVAL=3600

# Google Drive Settings (pastikan sudah ada)
GOOGLE_DRIVE_CLIENT_ID=your_client_id
GOOGLE_DRIVE_CLIENT_SECRET=your_client_secret
GOOGLE_DRIVE_REDIRECT_URI=your_redirect_uri
GOOGLE_DRIVE_REFRESH_TOKEN=your_refresh_token
*/