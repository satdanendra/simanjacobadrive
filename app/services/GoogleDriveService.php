<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected $client;
    protected $service;
    protected $folderId;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRefreshToken(config('services.google.refresh_token'));
        $this->client->setAccessType('offline');
        $this->client->setScopes([Drive::DRIVE, Drive::DRIVE_FILE]);
        
        $this->service = new Drive($this->client);
        $this->folderId = config('services.google.folder_id');
    }

    /**
     * Upload file ke Google Drive.
     *
     * @param UploadedFile $file
     * @param string $filename
     * @return string|null Drive ID jika berhasil
     */
    public function uploadFile(UploadedFile $file, $filename)
    {
        try {
            // Set metadata file
            $fileMetadata = new DriveFile([
                'name' => $filename,
                'parents' => [$this->folderId]
            ]);

            // Baca content file
            $content = file_get_contents($file->getRealPath());
            
            // Upload file ke Drive
            $uploadedFile = $this->service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $file->getMimeType(),
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]);

            // Kembalikan ID file yang diupload
            return $uploadedFile->getId();
        } catch (\Exception $e) {
            Log::error('Google Drive Upload Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ambil URL untuk melihat file.
     *
     * @param string $fileId
     * @return string
     */
    public function getViewUrl($fileId)
    {
        return "https://drive.google.com/file/d/{$fileId}/view";
    }
    
    /**
     * Ambil URL untuk download file.
     *
     * @param string $fileId
     * @return string
     */
    public function getDownloadUrl($fileId)
    {
        return "https://drive.google.com/uc?export=download&id={$fileId}";
    }
    
    /**
     * Ambil URL untuk thumbnail file.
     *
     * @param string $fileId
     * @return string
     */
    public function getThumbnailUrl($fileId)
    {
        return "https://drive.google.com/thumbnail?id={$fileId}";
    }
    
    /**
     * Hapus file dari Drive.
     *
     * @param string $fileId
     * @return bool
     */
    public function deleteFile($fileId)
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