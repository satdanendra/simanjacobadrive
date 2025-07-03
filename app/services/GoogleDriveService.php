<?php

namespace App\Services;

use Google\Client as Google_Client;
use Google\Service\Drive as Google_Service_Drive;
use Google\Service\Drive\DriveFile as Google_Service_Drive_DriveFile;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected $client;
    protected $service;
    protected $folderId;

    public function __construct()
    {
        // Inisialisasi Google Client
        $this->client = new Google_Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));

        // Autentikasi dengan refresh token
        $this->client->fetchAccessTokenWithRefreshToken(config('services.google.refresh_token'));
        $this->client->setAccessType('offline');
        $this->client->setScopes([
            Google_Service_Drive::DRIVE,
            Google_Service_Drive::DRIVE_FILE
        ]);

        $this->service = new Google_Service_Drive($this->client);
        $this->folderId = config('services.google.folder_id');
    }

    /**
     * Upload file dari path lokal ke Google Drive.
     *
     * @param string $filePath
     * @param string $filename
     * @return string|null Drive ID jika berhasil
     */
    public function uploadFile(string $filePath, string $filename): ?string
    {
        try {
            // Siapkan metadata file
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $filename,
                'parents' => [$this->folderId]
            ]);

            // Baca isi file dari path lokal
            $content = file_get_contents($filePath);
            $mimeType = mime_content_type($filePath);

            // Upload ke Google Drive
            $uploadedFile = $this->service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]);

            return $uploadedFile->getId();
        } catch (\Exception $e) {
            Log::error('Google Drive Upload Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Mendapatkan informasi file dari Google Drive
     *
     * @param string $fileId
     * @return array|null
     */
    public function getFileInfo(string $fileId): ?array
    {
        try {
            $file = $this->service->files->get($fileId, [
                'fields' => 'id,name,size,mimeType,createdTime,modifiedTime'
            ]);

            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'size' => $file->getSize(),
                'mimeType' => $file->getMimeType(),
                'createdTime' => $file->getCreatedTime(),
                'modifiedTime' => $file->getModifiedTime()
            ];
        } catch (\Exception $e) {
            Log::error('Google Drive Get File Info Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Download konten file dari Google Drive
     *
     * @param string $fileId
     * @return string|null Binary content dari file
     */
    public function downloadFileContent(string $fileId): ?string
    {
        try {
            // Metode alternatif menggunakan cURL
            $accessToken = $this->client->getAccessToken();

            if (!$accessToken || !isset($accessToken['access_token'])) {
                throw new \Exception('No valid access token available');
            }

            $url = "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken['access_token'],
            ]);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $content !== false) {
                return $content;
            } else {
                throw new \Exception("Failed to download file: HTTP {$httpCode}");
            }
        } catch (\Exception $e) {
            Log::error('Google Drive Download Content Error: ' . $e->getMessage());
            return null;
        }
    }

    public function getViewUrl(string $fileId): string
    {
        return "https://drive.google.com/file/d/{$fileId}/view";
    }

    public function getDownloadUrl(string $fileId): string
    {
        return "https://drive.google.com/uc?export=download&id={$fileId}";
    }

    public function getThumbnailUrl(string $fileId): string
    {
        return "https://drive.google.com/thumbnail?id={$fileId}";
    }

    public function deleteFile(string $fileId): bool
    {
        try {
            $this->service->files->delete($fileId);
            return true;
        } catch (\Exception $e) {
            Log::error('Google Drive Delete Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get high-quality preview image from Google Drive
     * No external dependencies required!
     */
    public function getDocumentPreviewImage(string $fileId, int $size = 1000): ?string
    {
        try {
            $accessToken = $this->client->getAccessToken();

            if (!$accessToken || !isset($accessToken['access_token'])) {
                $this->client->fetchAccessTokenWithRefreshToken(config('services.google.refresh_token'));
                $accessToken = $this->client->getAccessToken();
            }

            // Google Drive thumbnail dengan size yang bisa diatur
            // sz parameter: w1000 = width 1000px, s1000 = square 1000px
            $thumbnailUrl = "https://drive.google.com/thumbnail?id={$fileId}&sz=w{$size}";

            Log::info("Getting document preview", [
                'file_id' => $fileId,
                'size' => $size,
                'url' => $thumbnailUrl
            ]);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $thumbnailUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken['access_token'],
                ],
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Laravel-GoogleDrive)',
            ]);

            $imageContent = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            if ($httpCode === 200 && $imageContent && str_contains($contentType, 'image')) {
                Log::info("Preview image downloaded successfully", [
                    'file_id' => $fileId,
                    'size_bytes' => strlen($imageContent),
                    'content_type' => $contentType
                ]);

                return $imageContent;
            } else {
                Log::warning("Failed to get preview image", [
                    'file_id' => $fileId,
                    'http_code' => $httpCode,
                    'content_type' => $contentType
                ]);

                return null;
            }
        } catch (\Exception $e) {
            Log::error('Error getting document preview: ' . $e->getMessage(), [
                'file_id' => $fileId
            ]);

            return null;
        }
    }

    /**
     * Get multiple sizes for responsive preview
     */
    public function getDocumentPreviewMultiSize(string $fileId): array
    {
        $sizes = [
            'thumbnail' => 150,  // Small thumbnail
            'medium' => 500,     // Medium preview
            'large' => 1000      // High quality
        ];

        $images = [];

        foreach ($sizes as $label => $size) {
            $imageContent = $this->getDocumentPreviewImage($fileId, $size);
            if ($imageContent) {
                $images[$label] = [
                    'content' => $imageContent,
                    'size' => $size,
                    'bytes' => strlen($imageContent)
                ];
            }
        }

        return $images;
    }

    /**
     * Get document preview via Google Docs viewer (alternative method)
     */
    public function getDocumentPreviewViaViewer(string $fileId): ?string
    {
        try {
            // Alternatif: gunakan Google Docs viewer untuk generate preview
            $viewerUrl = "https://docs.google.com/gview?id={$fileId}&embedded=true";

            // Ini return HTML, bisa di-parse untuk extract preview image
            // Atau bisa di-screenshot dengan headless browser

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $viewerUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Laravel-GoogleDrive)',
            ]);

            $htmlContent = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $htmlContent) {
                // Parse HTML untuk cari image URL
                $pattern = '/src="(https:\/\/[^"]*\.(?:png|jpg|jpeg)[^"]*)"/i';
                if (preg_match($pattern, $htmlContent, $matches)) {
                    $imageUrl = $matches[1];

                    // Download image
                    $imageContent = file_get_contents($imageUrl);
                    return $imageContent;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error getting viewer preview: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Detect best preview method for file type
     */
    public function getBestDocumentPreview(string $fileId): ?array
    {
        try {
            $fileInfo = $this->getFileInfo($fileId);
            if (!$fileInfo) {
                return null;
            }

            $mimeType = $fileInfo['mimeType'];
            $fileName = $fileInfo['name'];

            Log::info("Getting best preview for file", [
                'file_id' => $fileId,
                'mime_type' => $mimeType,
                'file_name' => $fileName
            ]);

            // Prioritas method berdasarkan tipe file
            $previewMethods = [
                'application/pdf' => ['thumbnail_large', 'viewer'],
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['thumbnail_large', 'viewer'],
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['thumbnail_large'],
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['thumbnail_large'],
                'default' => ['thumbnail_medium']
            ];

            $methods = $previewMethods[$mimeType] ?? $previewMethods['default'];

            foreach ($methods as $method) {
                switch ($method) {
                    case 'thumbnail_large':
                        $imageContent = $this->getDocumentPreviewImage($fileId, 1000);
                        break;
                    case 'thumbnail_medium':
                        $imageContent = $this->getDocumentPreviewImage($fileId, 500);
                        break;
                    case 'viewer':
                        $imageContent = $this->getDocumentPreviewViaViewer($fileId);
                        break;
                    default:
                        $imageContent = null;
                }

                if ($imageContent) {
                    return [
                        'content' => $imageContent,
                        'method' => $method,
                        'size' => strlen($imageContent),
                        'mime_type' => $mimeType,
                        'file_name' => $fileName
                    ];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error getting best preview: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get document preview using multiple Google Drive methods
     */
    public function getEnhancedDocumentPreview(string $fileId): ?array
    {
        try {
            Log::info("Getting enhanced document preview", ['file_id' => $fileId]);

            // Method 1: High-quality thumbnail (sz=w1000)
            $largePreview = $this->getDocumentThumbnail($fileId, 'w1000');
            if ($largePreview) {
                return [
                    'content' => $largePreview,
                    'method' => 'thumbnail_large',
                    'size' => strlen($largePreview)
                ];
            }

            // Method 2: Medium thumbnail (sz=w600) 
            $mediumPreview = $this->getDocumentThumbnail($fileId, 'w600');
            if ($mediumPreview) {
                return [
                    'content' => $mediumPreview,
                    'method' => 'thumbnail_medium',
                    'size' => strlen($mediumPreview)
                ];
            }

            // Method 3: Standard thumbnail (sz=s400)
            $standardPreview = $this->getDocumentThumbnail($fileId, 's400');
            if ($standardPreview) {
                return [
                    'content' => $standardPreview,
                    'method' => 'thumbnail_standard',
                    'size' => strlen($standardPreview)
                ];
            }

            // Method 4: Google Docs export (for Google native docs)
            $exportPreview = $this->tryGoogleDocsExport($fileId);
            if ($exportPreview) {
                return [
                    'content' => $exportPreview,
                    'method' => 'google_export',
                    'size' => strlen($exportPreview)
                ];
            }

            Log::warning("All preview methods failed", ['file_id' => $fileId]);
            return null;
        } catch (\Exception $e) {
            Log::error('Enhanced preview error: ' . $e->getMessage(), ['file_id' => $fileId]);
            return null;
        }
    }

    /**
     * Get thumbnail with specific size parameter
     */
    private function getDocumentThumbnail(string $fileId, string $sizeParam): ?string
    {
        try {
            $accessToken = $this->client->getAccessToken();

            if (!$accessToken || !isset($accessToken['access_token'])) {
                $this->client->fetchAccessTokenWithRefreshToken(config('services.google.refresh_token'));
                $accessToken = $this->client->getAccessToken();
            }

            // URL dengan parameter size yang berbeda:
            // sz=w1000 = width 1000px
            // sz=s400 = square 400px  
            // sz=w600-h800 = width 600, height 800
            $thumbnailUrl = "https://drive.google.com/thumbnail?id={$fileId}&sz={$sizeParam}";

            Log::info("Trying thumbnail", [
                'file_id' => $fileId,
                'size_param' => $sizeParam,
                'url' => $thumbnailUrl
            ]);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $thumbnailUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken['access_token'],
                ],
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ]);

            $imageContent = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            curl_close($ch);

            Log::info("Thumbnail response", [
                'file_id' => $fileId,
                'size_param' => $sizeParam,
                'http_code' => $httpCode,
                'content_type' => $contentType,
                'content_length' => $contentLength
            ]);

            if ($httpCode === 200 && $imageContent && str_contains($contentType, 'image')) {
                // Validasi bahwa ini benar-benar image yang valid
                if (strlen($imageContent) > 1000) { // Minimal 1KB untuk image yang valid
                    Log::info("Valid thumbnail obtained", [
                        'file_id' => $fileId,
                        'size_param' => $sizeParam,
                        'size_bytes' => strlen($imageContent)
                    ]);

                    return $imageContent;
                } else {
                    Log::warning("Thumbnail too small, probably error image", [
                        'file_id' => $fileId,
                        'size_param' => $sizeParam,
                        'size_bytes' => strlen($imageContent)
                    ]);
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Thumbnail error for {$sizeParam}: " . $e->getMessage(), ['file_id' => $fileId]);
            return null;
        }
    }

    /**
     * Try Google Docs export for native Google documents
     */
    private function tryGoogleDocsExport(string $fileId): ?string
    {
        try {
            // Cek apakah file adalah Google native document
            $fileInfo = $this->getFileInfo($fileId);
            if (!$fileInfo) {
                return null;
            }

            $mimeType = $fileInfo['mimeType'];

            // Hanya untuk Google native documents
            if (!str_starts_with($mimeType, 'application/vnd.google-apps.')) {
                return null;
            }

            $accessToken = $this->client->getAccessToken();

            // Export sebagai PNG
            $exportUrl = "https://www.googleapis.com/drive/v3/files/{$fileId}/export?mimeType=image/png";

            Log::info("Trying Google Docs export", [
                'file_id' => $fileId,
                'original_mime' => $mimeType,
                'export_url' => $exportUrl
            ]);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $exportUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken['access_token'],
                ],
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
            ]);

            $imageContent = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $imageContent && strlen($imageContent) > 1000) {
                Log::info("Google Docs export successful", [
                    'file_id' => $fileId,
                    'size_bytes' => strlen($imageContent)
                ]);

                return $imageContent;
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Google Docs export error: " . $e->getMessage(), ['file_id' => $fileId]);
            return null;
        }
    }

    /**
     * Alternative: Try accessing via different Google Drive URLs
     */
    public function tryAlternativePreview(string $fileId): ?string
    {
        $alternativeUrls = [
            // Method 1: Direct thumbnail dengan parameter berbeda
            "https://lh3.googleusercontent.com/d/{$fileId}=w1000",
            "https://lh3.googleusercontent.com/d/{$fileId}=s1000",

            // Method 2: Drive API dengan parameter alt
            "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media&key=" . config('services.google.api_key'),

            // Method 3: Apps Script web app (jika ada)
            // "https://script.google.com/macros/s/YOUR_SCRIPT_ID/exec?fileId={$fileId}",
        ];

        foreach ($alternativeUrls as $index => $url) {
            try {
                Log::info("Trying alternative URL", [
                    'file_id' => $fileId,
                    'method' => $index + 1,
                    'url' => $url
                ]);

                $accessToken = $this->client->getAccessToken();

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $accessToken['access_token'],
                    ],
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 20,
                ]);

                $content = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                curl_close($ch);

                if ($httpCode === 200 && $content && str_contains($contentType, 'image') && strlen($content) > 1000) {
                    Log::info("Alternative method successful", [
                        'file_id' => $fileId,
                        'method' => $index + 1,
                        'size_bytes' => strlen($content)
                    ]);

                    return $content;
                }
            } catch (\Exception $e) {
                Log::warning("Alternative method {$index} failed: " . $e->getMessage());
                continue;
            }
        }

        return null;
    }
}