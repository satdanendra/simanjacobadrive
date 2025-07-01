<?php

namespace App\Services;

use App\Models\LaporanHarian;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\Shared\Converter;
use Illuminate\Support\Facades\Log;

class LaporanWordService
{
    public function generateLaporan(LaporanHarian $laporan)
    {
        try {
            Log::info("Mulai generate laporan", ['laporan_id' => $laporan->id]);

            $phpWord = new PhpWord();

            // Set bahasa Indonesia
            $phpWord->getSettings()->setThemeFontLang(new Language('id-ID'));

            // Buat section
            $section = $phpWord->addSection([
                'marginTop' => Converter::cmToTwip(2),
                'marginBottom' => Converter::cmToTwip(2),
                'marginLeft' => Converter::cmToTwip(2),
                'marginRight' => Converter::cmToTwip(2),
            ]);

            // Header styles
            $headerStyle = ['name' => 'Arial', 'size' => 14, 'bold' => true];
            $normalStyle = ['name' => 'Arial', 'size' => 10];
            $boldStyle = ['name' => 'Arial', 'size' => 10, 'bold' => true];

            // Title
            $section->addText('Laporan Harian Pelaksanaan Pekerjaan', $headerStyle, ['alignment' => 'center']);
            $section->addTextBreak(2);

            // Identitas Pegawai
            $this->addIdentitasPegawai($section, $laporan, $boldStyle, $normalStyle);
            $section->addTextBreak();

            // Rencana Kerja dan Kegiatan
            $this->addRencanaKerja($section, $laporan, $boldStyle, $normalStyle);
            $section->addTextBreak();

            // Dasar Pelaksanaan Kegiatan
            $this->addDasarPelaksanaan($section, $laporan, $boldStyle, $normalStyle);
            $section->addTextBreak();

            // Bukti Pelaksanaan Pekerjaan
            $this->addBuktiPelaksanaan($section, $laporan, $boldStyle, $normalStyle);
            $section->addTextBreak();

            // Kendala dan Solusi
            $this->addKendalaSolusi($section, $laporan, $boldStyle, $normalStyle);
            $section->addTextBreak();

            // Catatan
            $this->addCatatan($section, $laporan, $boldStyle, $normalStyle);
            $section->addTextBreak(2);

            // Pengesahan
            $this->addPengesahan($section, $laporan, $boldStyle, $normalStyle);

            // Cek apakah ada bukti dukung untuk lampiran
            if ($laporan->buktiDukungs->count() > 0) {
                Log::info("Adding lampiran pages", ['bukti_count' => $laporan->buktiDukungs->count()]);
                // Tambah halaman baru untuk lampiran
                $this->addLampiranPages($phpWord, $laporan, $boldStyle, $normalStyle);
            }

            // Pastikan folder temp ada
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Save dokumen
            $filename = 'laporan_' . $laporan->id . '_' . time() . '.docx';
            $tempPath = $tempDir . '/' . $filename;

            Log::info("Saving document", ['temp_path' => $tempPath]);

            // **PERBAIKAN**: Set memory limit dan timeout untuk file besar
            ini_set('memory_limit', '512M');
            set_time_limit(300); // 5 minutes

            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempPath);

            // Verifikasi file berhasil dibuat
            if (!file_exists($tempPath) || filesize($tempPath) == 0) {
                throw new \Exception('File dokumen tidak berhasil dibuat atau kosong');
            }

            Log::info("Document berhasil disimpan", [
                'temp_path' => $tempPath,
                'file_size' => filesize($tempPath)
            ]);

            // **PERBAIKAN**: Cleanup temporary image files SETELAH dokument selesai disave
            $this->cleanupTempFiles();

            return $tempPath;
        } catch (\Exception $e) {
            Log::error('Error dalam generateLaporan: ' . $e->getMessage(), [
                'laporan_id' => $laporan->id,
                'trace' => $e->getTraceAsString()
            ]);

            // Cleanup jika terjadi error
            $this->cleanupTempFiles();

            throw $e;
        }
    }

    private function addIdentitasPegawai($section, $laporan, $boldStyle, $normalStyle)
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'width' => 100 * 50
        ]);

        // Header
        $table->addRow();
        $table->addCell(null, ['gridSpan' => 2, 'bgColor' => '7CD440'])
            ->addText('Identitas Pegawai', $boldStyle);

        // Nama
        $table->addRow();
        $table->addCell(3000, ['width' => 3000])->addText('Nama Pegawai', $normalStyle);
        $table->addCell(7000, ['width' => 7000])->addText($laporan->user->name, $boldStyle);

        // Email
        $table->addRow();
        $table->addCell(3000, ['width' => 3000])->addText('Email', $normalStyle);
        $table->addCell(7000, ['width' => 7000])->addText($laporan->user->email, $boldStyle);
    }

    private function addRencanaKerja($section, $laporan, $boldStyle, $normalStyle)
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'width' => 100 * 50
        ]);

        // Header
        $table->addRow();
        $table->addCell(null, ['gridSpan' => 2, 'bgColor' => '7CD440'])
            ->addText('Rencana Kerja dan Kegiatan', $boldStyle);

        // Tim Kegiatan
        $table->addRow();
        $table->addCell(3000, ['width' => 3000])->addText('Tim Kegiatan', $normalStyle);
        $table->addCell(7000, ['width' => 7000])->addText($laporan->proyek->tim->nama_tim, $boldStyle);

        // Proyek
        $table->addRow();
        $table->addCell(3000, ['width' => 3000])->addText('Proyek', $normalStyle);
        $table->addCell(7000, ['width' => 7000])->addText($laporan->proyek->nama_proyek, $boldStyle);

        // Waktu
        $table->addRow();
        $table->addCell(3000, ['width' => 3000])->addText('Waktu', $normalStyle);
        $table->addCell(7000, ['width' => 7000])->addText($laporan->formatted_waktu, $boldStyle);

        // Kegiatan
        $table->addRow();
        $table->addCell(3000, ['width' => 3000, 'valign' => 'top'])->addText('Kegiatan', $normalStyle);
        $table->addCell(7000, ['width' => 7000])->addText($laporan->kegiatan, $boldStyle);

        // Capaian
        $table->addRow();
        $table->addCell(3000, ['width' => 3000, 'valign' => 'top'])->addText('Capaian', $normalStyle);
        $table->addCell(7000, ['width' => 7000])->addText($laporan->capaian, $boldStyle);
    }

    private function addDasarPelaksanaan($section, $laporan, $boldStyle, $normalStyle)
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'width' => 100 * 50
        ]);

        // Header
        $table->addRow();
        $table->addCell(null, ['gridSpan' => 2, 'bgColor' => '7CD440'])
            ->addText('Dasar Pelaksanaan Kegiatan', $boldStyle);

        // Isi
        if ($laporan->dasarPelaksanaans->count() > 0) {
            foreach ($laporan->dasarPelaksanaans as $index => $dasar) {
                $number = $index + 1;
                $text = $number . '. ' . $dasar->formatted_deskripsi;
                $table->addRow();
                $table->addCell(1000)->addText((string)$number . '.', $normalStyle);
                $table->addCell(9000)->addText($dasar->formatted_deskripsi, $normalStyle);
            }
        } else {
            $table->addRow();
            $table->addCell(null)->addText('1. -', $normalStyle);
        }
    }

    private function addBuktiPelaksanaan($section, $laporan, $boldStyle, $normalStyle)
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'width' => 100 * 50
        ]);

        // Header
        $table->addRow();
        $table->addCell(10000, ['bgColor' => '7CD440'])->addText(
            'Bukti Pelaksanaan Pekerjaan (Foto/Dokumentasi/Screenshot/Print Screen, dll)',
            $boldStyle
        );

        // Isi
        if ($laporan->buktiDukungs->count() > 0) {
            foreach ($laporan->buktiDukungs as $index => $bukti) {
                $number = $index + 1;
                $text = $number . '. ' . $bukti->nama_file . ' (Lihat Lampiran ' . $number . ')';

                if ($bukti->keterangan) {
                    $text .= ' - ' . $bukti->keterangan;
                }

                $table->addRow();
                $table->addCell(null)->addText($text, $normalStyle);
            }
        } else {
            $table->addRow();
            $table->addCell(null)->addText('1. (tidak ada)', $normalStyle);
        }
    }

    private function addKendalaSolusi($section, $laporan, $boldStyle, $normalStyle)
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'width' => 100 * 50
        ]);

        // Header
        $table->addRow();
        $table->addCell(null, ['gridSpan' => 3, 'bgColor' => '7CD440'])->addText(
            'Kendala dan Solusi',
            $boldStyle
        );
        $table->addRow();
        $table->addCell(1000, ['bgColor' => 'C2EA92'])->addText('No', $boldStyle, ['alignment' => 'center']);
        $table->addCell(4500, ['bgColor' => 'C2EA92'])->addText('Kendala/Permasalahan', $boldStyle, ['alignment' => 'center']);
        $table->addCell(4500, ['bgColor' => 'C2EA92'])->addText('Solusi', $boldStyle, ['alignment' => 'center']);

        // Content
        $table->addRow();
        $table->addCell(1000)->addText('1.', $normalStyle, ['alignment' => 'center']);
        $table->addCell(4500)->addText($laporan->kendala ?: '-', $normalStyle);
        $table->addCell(4500)->addText($laporan->solusi ?: '-', $normalStyle);
    }

    private function addCatatan($section, $laporan, $boldStyle, $normalStyle)
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'width' => 100 * 50
        ]);

        // Header
        $table->addRow();
        $table->addCell(10000, ['bgColor' => '7CD440'])->addText(
            'Catatan',
            $boldStyle
        );

        // Isi
        if ($laporan->catatan) {
            $table->addRow();
            $table->addCell(null)->addText($laporan->catatan, $normalStyle);
        } else {
            $table->addRow();
            $table->addCell(null)->addText('-', $normalStyle);
        }
    }

    private function addPengesahan($section, $laporan, $boldStyle, $normalStyle)
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 50,
            'width' => 100 * 50
        ]);

        // Header
        $table->addRow();
        $table->addCell(null, ['gridSpan' => 3, 'bgColor' => '7CD440'])->addText('Pengesahan', $boldStyle, ['alignment' => 'center']);

        // Sub header
        $table->addRow();
        $table->addCell(null, ['gridSpan' => 3])->addText('Mengetahui,', $normalStyle, ['alignment' => 'center']);
        $table->addRow();
        $table->addCell(3333)->addText('Pegawai yang Melaporkan,', $normalStyle, ['alignment' => 'center']);
        $table->addCell(3333)->addText('Penanggung Jawab Kegiatan,', $normalStyle, ['alignment' => 'center']);
        $table->addCell(3333)->addText('Atasan Langsung Pegawai', $normalStyle, ['alignment' => 'center']);

        // Nama pegawai
        $table->addRow(1000);
        $table->addCell(3333)->addText('', $normalStyle);
        $table->addCell(3333)->addText('', $normalStyle);
        $table->addCell(3333)->addText('', $normalStyle);

        // Tanda tangan
        $table->addRow();
        $table->addCell(3333)->addText($laporan->user->name, $boldStyle, ['alignment' => 'center']);
        $table->addCell(3333)->addText('BENI NURROFIK', $boldStyle, ['alignment' => 'center']);
        $table->addCell(3333)->addText('ALUISIUS ABRIANTA', $boldStyle, ['alignment' => 'center']);
    }

    private function addLampiranPages($phpWord, $laporan, $boldStyle, $normalStyle)
    {
        foreach ($laporan->buktiDukungs as $index => $bukti) {
            // Tambah section baru (halaman baru)
            $lampiranSection = $phpWord->addSection([
                'marginTop' => Converter::cmToTwip(2),
                'marginBottom' => Converter::cmToTwip(2),
                'marginLeft' => Converter::cmToTwip(2),
                'marginRight' => Converter::cmToTwip(2),
            ]);

            // Header "Lampiran" di pojok kiri atas
            $lampiranSection->addText('Lampiran', $boldStyle, ['alignment' => 'left']);
            $lampiranSection->addTextBreak();

            // Judul lampiran
            $nomorLampiran = $index + 1;
            $lampiranSection->addText(
                "Lampiran {$nomorLampiran}: {$bukti->nama_file}",
                $boldStyle,
                ['alignment' => 'center']
            );
            $lampiranSection->addTextBreak();

            // Keterangan jika ada
            if ($bukti->keterangan) {
                $lampiranSection->addText("Keterangan: {$bukti->keterangan}", $normalStyle);
                $lampiranSection->addTextBreak();
            }

            // Masukkan file berdasarkan tipe
            $this->addLampiranContent($lampiranSection, $bukti, $normalStyle);
        }
    }

    private function addLampiranContent($section, $bukti, $normalStyle)
    {
        if ($this->isImageFile($bukti->nama_file)) {
            // Jika gambar - tampilkan gambar
            $this->addImageToLampiran($section, $bukti);
        } else {
            // Jika dokumen - tampilkan info dokumen + link/embed jika memungkinkan
            $this->addDocumentToLampiran($section, $bukti, $normalStyle);
        }
    }

    private function addImageToLampiran($section, $bukti)
    {
        try {
            Log::info("Processing image", ['bukti_id' => $bukti->id, 'file' => $bukti->nama_file]);

            // Download gambar (sama seperti sebelumnya)
            $imageContent = $this->downloadImageFromDrive($bukti->drive_id);
            if (!$imageContent) {
                $this->addImageFallback($section, $bukti, 'Gagal mengunduh gambar dari Google Drive');
                return;
            }

            // Setup file temporary
            $originalExtension = strtolower(pathinfo($bukti->nama_file, PATHINFO_EXTENSION));
            $supportedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

            if (!in_array($originalExtension, $supportedExtensions)) {
                $this->addImageFallback($section, $bukti, 'Format tidak didukung: ' . $originalExtension);
                return;
            }

            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempImagePath = $tempDir . '/img_' . time() . '_' . $bukti->id . '.' . $originalExtension;
            file_put_contents($tempImagePath, $imageContent);
            $this->tempFiles[] = $tempImagePath;

            usleep(100000);

            // Get image dimensions
            $imageInfo = @getimagesize($tempImagePath);
            if (!$imageInfo) {
                $this->addImageFallback($section, $bukti, 'File bukan gambar valid');
                return;
            }

            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];

            // Kalkulasi ukuran
            $maxWidthCm = 15;
            $maxHeightCm = 18;
            $maxWidthPoints = $maxWidthCm * 28.35;
            $maxHeightPoints = $maxHeightCm * 28.35;
            $scale = min($maxWidthPoints / $originalWidth, $maxHeightPoints / $originalHeight, 1);
            $finalWidthPoints = $originalWidth * $scale;
            $finalHeightPoints = $originalHeight * $scale;

            // **SOLUSI SIMPLE: Buat paragraph dengan center alignment terlebih dahulu**
            $centerParagraph = $section->addTextRun(['alignment' => 'center']);

            // Tambahkan gambar ke paragraph yang sudah center-aligned
            $centerParagraph->addImage($tempImagePath, [
                'width' => $finalWidthPoints,
                'height' => $finalHeightPoints,
                'wrappingStyle' => 'inline'
            ]);

            // Text break
            $section->addTextBreak();

            // Info gambar juga dengan center alignment
            $section->addText(
                'Ukuran asli: ' . $originalWidth . ' x ' . $originalHeight . ' pixels',
                ['size' => 9, 'italic' => true, 'color' => '666666'],
                ['alignment' => 'center']
            );

            if ($scale < 1) {
                $section->addText(
                    'Diperkecil menjadi: ' . round($finalWidthPoints) . ' x ' . round($finalHeightPoints) . ' points (' . round($scale * 100, 1) . '%)',
                    ['size' => 8, 'italic' => true, 'color' => '888888'],
                    ['alignment' => 'center']
                );
            }

            Log::info("Image inserted with simple center alignment", ['bukti_id' => $bukti->id]);
        } catch (\Exception $e) {
            Log::error('Error inserting image: ' . $e->getMessage(), [
                'bukti_id' => $bukti->id,
                'file' => $bukti->nama_file ?? 'unknown'
            ]);

            $this->addImageFallback($section, $bukti, 'Error: ' . $e->getMessage());
        }
    }

    // Method helper untuk fallback ketika gambar gagal dimuat
    private function addImageFallback($section, $bukti, $errorMessage)
    {
        $section->addText($errorMessage, ['color' => 'FF0000', 'italic' => true]);

        // Tambahkan info file
        $section->addTextBreak();
        $section->addText("File: {$bukti->nama_file}", ['bold' => true]);

        if ($bukti->keterangan) {
            $section->addText("Keterangan: {$bukti->keterangan}");
        }

        // Tambahkan link Google Drive sebagai fallback
        $section->addTextBreak();
        $section->addText(
            'Gambar dapat diakses melalui: ' . $this->getGoogleDriveViewUrl($bukti->drive_id),
            ['size' => 9, 'underline' => 'single', 'color' => '0000FF']
        );
    }

    // **PERBAIKAN**: Modifikasi method generateLaporan untuk cleanup file temporary di akhir
    // Tambahkan property untuk track temporary files
    private $tempFiles = [];

    // Di method addImageToLampiran, setelah berhasil buat temp file:
    // $this->tempFiles[] = $tempImagePath;

    // Di akhir method generateLaporan, tambahkan cleanup:
    private function cleanupTempFiles()
    {
        foreach ($this->tempFiles as $tempFile) {
            if (file_exists($tempFile)) {
                try {
                    unlink($tempFile);
                    Log::info("Cleaned up temp file: " . $tempFile);
                } catch (\Exception $e) {
                    Log::warning("Failed to cleanup temp file: " . $tempFile . " - " . $e->getMessage());
                }
            }
        }
        $this->tempFiles = [];
    }

    private function addDocumentToLampiran($section, $bukti, $normalStyle)
    {
        try {
            Log::info("Processing document for lampiran", [
                'bukti_id' => $bukti->id,
                'file' => $bukti->nama_file,
                'type' => $bukti->file_type,
                'drive_id' => $bukti->drive_id
            ]);

            $driveService = app(\App\Services\GoogleDriveService::class);

            // Try to get enhanced document preview
            $previewData = $driveService->getEnhancedDocumentPreview($bukti->drive_id);

            if ($previewData && $previewData['content']) {
                Log::info("Preview found, adding as image", [
                    'bukti_id' => $bukti->id,
                    'method' => $previewData['method'],
                    'size' => $previewData['size']
                ]);

                $this->addDocumentPreviewAsImage($section, $bukti, $previewData, $normalStyle);
            } else {
                Log::info("No preview available, showing info only", ['bukti_id' => $bukti->id]);

                // Try alternative methods before giving up
                $alternativePreview = $driveService->tryAlternativePreview($bukti->drive_id);

                if ($alternativePreview) {
                    Log::info("Alternative preview found", [
                        'bukti_id' => $bukti->id,
                        'size' => strlen($alternativePreview)
                    ]);

                    $previewData = [
                        'content' => $alternativePreview,
                        'method' => 'alternative',
                        'size' => strlen($alternativePreview)
                    ];

                    $this->addDocumentPreviewAsImage($section, $bukti, $previewData, $normalStyle);
                } else {
                    // Final fallback: show document info only
                    $this->addDocumentInfoOnly($section, $bukti, $normalStyle);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing document: ' . $e->getMessage(), [
                'bukti_id' => $bukti->id,
                'file' => $bukti->nama_file,
                'trace' => $e->getTraceAsString()
            ]);

            $this->addDocumentInfoOnly($section, $bukti, $normalStyle);
        }
    }

    /**
     * Add document preview as image with enhanced error handling
     */
    private function addDocumentPreviewAsImage($section, $bukti, $previewData, $normalStyle)
    {
        try {
            $imageContent = $previewData['content'];
            $method = $previewData['method'] ?? 'unknown';

            // Validate image content
            if (strlen($imageContent) < 1000) {
                throw new \Exception("Image content too small (" . strlen($imageContent) . " bytes)");
            }

            // Create temp file for preview image
            $tempImagePath = storage_path('app/temp/doc_preview_' . time() . '_' . $bukti->id . '.png');

            // Ensure temp directory exists
            $tempDir = dirname($tempImagePath);
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $writeResult = file_put_contents($tempImagePath, $imageContent);
            if ($writeResult === false) {
                throw new \Exception("Failed to write temp image file");
            }

            $this->tempFiles[] = $tempImagePath;

            // Validate that it's a real image
            $imageInfo = @getimagesize($tempImagePath);
            if (!$imageInfo) {
                throw new \Exception("Invalid image data");
            }

            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];

            Log::info("Valid preview image created", [
                'bukti_id' => $bukti->id,
                'method' => $method,
                'dimensions' => $originalWidth . 'x' . $originalHeight,
                'temp_path' => $tempImagePath
            ]);

            // Add document header
            $section->addText("Dokumen: {$bukti->nama_file}", ['bold' => true], ['alignment' => 'center']);
            $section->addText("Tipe: {$bukti->file_type}", $normalStyle, ['alignment' => 'center']);

            // Get and display file size
            try {
                $driveService = app(\App\Services\GoogleDriveService::class);
                $fileInfo = $driveService->getFileInfo($bukti->drive_id);
                $size = isset($fileInfo['size']) ? $this->formatBytes($fileInfo['size']) : 'Unknown';
                $section->addText("Ukuran File: {$size}", $normalStyle, ['alignment' => 'center']);
            } catch (\Exception $e) {
                Log::warning("Could not get file size", ['bukti_id' => $bukti->id]);
            }

            $section->addTextBreak();

            // Calculate display size
            $maxWidthCm = 15;   // Max 15cm width
            $maxHeightCm = 20;  // Max 20cm height
            $maxWidthPoints = $maxWidthCm * 28.35;
            $maxHeightPoints = $maxHeightCm * 28.35;

            $scale = min($maxWidthPoints / $originalWidth, $maxHeightPoints / $originalHeight, 1);
            $finalWidth = $originalWidth * $scale;
            $finalHeight = $originalHeight * $scale;

            // Add preview image with center alignment
            $centerParagraph = $section->addTextRun(['alignment' => 'center']);
            $centerParagraph->addImage($tempImagePath, [
                'width' => $finalWidth,
                'height' => $finalHeight,
                'wrappingStyle' => 'inline'
            ]);

            // Add info below image
            $section->addTextBreak();
            $section->addText(
                "Preview Dokumen",
                ['size' => 10, 'bold' => true],
                ['alignment' => 'center']
            );

            $section->addText(
                "Resolusi: {$originalWidth} x {$originalHeight} pixels",
                ['size' => 9, 'italic' => true, 'color' => '666666'],
                ['alignment' => 'center']
            );

            if ($scale < 1) {
                $section->addText(
                    "Diperkecil ke: " . round($finalWidth) . ' x ' . round($finalHeight) . " points (" . round($scale * 100, 1) . "%)",
                    ['size' => 8, 'italic' => true, 'color' => '888888'],
                    ['alignment' => 'center']
                );
            }

            // Method info (for debugging)
            $section->addText(
                "Metode: " . ucfirst(str_replace('_', ' ', $method)),
                ['size' => 8, 'italic' => true, 'color' => 'AAAAAA'],
                ['alignment' => 'center']
            );

            // Add access link
            $section->addTextBreak();
            $section->addText(
                "Akses dokumen lengkap:",
                ['size' => 9, 'italic' => true],
                ['alignment' => 'center']
            );

            $section->addText(
                $this->getGoogleDriveViewUrl($bukti->drive_id),
                ['size' => 9, 'underline' => 'single', 'color' => '0000FF'],
                ['alignment' => 'center']
            );

            Log::info("Document preview added successfully", [
                'bukti_id' => $bukti->id,
                'method' => $method,
                'final_dimensions' => round($finalWidth) . 'x' . round($finalHeight)
            ]);
        } catch (\Exception $e) {
            Log::error('Error adding document preview image: ' . $e->getMessage(), [
                'bukti_id' => $bukti->id,
                'method' => $previewData['method'] ?? 'unknown'
            ]);

            // Cleanup temp file if created
            if (isset($tempImagePath) && file_exists($tempImagePath)) {
                unlink($tempImagePath);
                // Remove from tempFiles array
                $this->tempFiles = array_filter($this->tempFiles, function ($file) use ($tempImagePath) {
                    return $file !== $tempImagePath;
                });
            }

            // Fallback to info only
            $this->addDocumentInfoOnly($section, $bukti, $normalStyle);
        }
    }

    /**
     * Enhanced document info only (improved styling)
     */
    private function addDocumentInfoOnly($section, $bukti, $normalStyle)
    {
        // Document header with better styling
        $section->addText("Dokumen: {$bukti->nama_file}", ['bold' => true, 'size' => 12], ['alignment' => 'center']);
        $section->addText("Tipe: {$bukti->file_type}", $normalStyle, ['alignment' => 'center']);

        // Get file size
        try {
            $driveService = app(\App\Services\GoogleDriveService::class);
            $fileInfo = $driveService->getFileInfo($bukti->drive_id);
            $size = isset($fileInfo['size']) ? $this->formatBytes($fileInfo['size']) : 'Unknown';
            $section->addText("Ukuran: {$size}", $normalStyle, ['alignment' => 'center']);
        } catch (\Exception $e) {
            Log::warning("Could not get file size", ['bukti_id' => $bukti->id]);
        }

        $section->addTextBreak(2);

        // Better explanation with icon-like text
        $section->addText(
            "📄 Preview Visual Tidak Tersedia",
            ['bold' => true, 'size' => 11, 'color' => '666666'],
            ['alignment' => 'center']
        );

        $section->addText(
            "Dokumen dapat diakses melalui link Google Drive di bawah ini",
            ['italic' => true, 'size' => 10, 'color' => '666666'],
            ['alignment' => 'center']
        );

        $section->addTextBreak();

        // Access link with better styling
        $section->addText(
            "🔗 Buka Dokumen:",
            ['bold' => true, 'size' => 10],
            ['alignment' => 'center']
        );

        $section->addText(
            $this->getGoogleDriveViewUrl($bukti->drive_id),
            ['size' => 9, 'underline' => 'single', 'color' => '0000FF'],
            ['alignment' => 'center']
        );

        Log::info("Document info added (no preview available)", [
            'bukti_id' => $bukti->id,
            'file' => $bukti->nama_file
        ]);
    }

    private function getFileSize($bukti)
    {
        try {
            // Ambil info file dari Google Drive
            $driveService = app(GoogleDriveService::class);
            $fileInfo = $driveService->getFileInfo($bukti->drive_id);

            if (isset($fileInfo['size'])) {
                return $this->formatBytes($fileInfo['size']);
            }
        } catch (\Exception $e) {
            // Ignore error
        }

        return 'Unknown';
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB');

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function isImageFile($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp']);
    }

    /**
     * Download gambar dari Google Drive dengan validasi tambahan
     */
    private function downloadImageFromDrive($driveId)
    {
        try {
            $driveService = app(\App\Services\GoogleDriveService::class);

            // Cek info file terlebih dahulu
            $fileInfo = $driveService->getFileInfo($driveId);

            if (!$fileInfo) {
                throw new \Exception('File tidak ditemukan di Google Drive');
            }

            // Validasi tipe MIME
            $allowedMimeTypes = [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'image/bmp'
            ];

            if (!in_array($fileInfo['mimeType'], $allowedMimeTypes)) {
                throw new \Exception('Tipe file tidak didukung: ' . $fileInfo['mimeType']);
            }

            // Download content
            $content = $driveService->downloadFileContent($driveId);

            if (!$content) {
                throw new \Exception('Gagal mengunduh konten file');
            }

            // Validasi ukuran file (tidak boleh kosong)
            if (strlen($content) == 0) {
                throw new \Exception('File kosong');
            }

            return $content;
        } catch (\Exception $e) {
            Log::error("Gagal download gambar dari Google Drive: " . $e->getMessage(), [
                'drive_id' => $driveId
            ]);
            return null;
        }
    }

    /**
     * Generate URL untuk view file di Google Drive
     */
    private function getGoogleDriveViewUrl($driveId)
    {
        return "https://drive.google.com/file/d/{$driveId}/view";
    }
}
