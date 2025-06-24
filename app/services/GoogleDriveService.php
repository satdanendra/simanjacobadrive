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
}
