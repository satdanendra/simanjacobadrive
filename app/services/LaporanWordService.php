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

        // Cek apakah ada bukti dukung
        if ($laporan->buktiDukungs->count() > 0) {
            // Tambah halaman baru untuk lampiran
            $this->addLampiranPages($phpWord, $laporan, $boldStyle, $normalStyle);
        }

        // Save dokumen
        $filename = 'laporan_' . time() . '.docx';
        $tempPath = storage_path('app/temp/' . $filename);

        // Pastikan folder temp ada
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempPath);

        return $tempPath;
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
            // Download gambar dari Google Drive
            $imageContent = $this->downloadImageFromDrive($bukti->drive_id);

            if ($imageContent) {
                // Deteksi ekstensi file asli
                $originalExtension = strtolower(pathinfo($bukti->nama_file, PATHINFO_EXTENSION));

                // Validasi ekstensi yang didukung
                $supportedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
                if (!in_array($originalExtension, $supportedExtensions)) {
                    $section->addText(
                        'Format gambar tidak didukung: ' . $originalExtension,
                        ['color' => 'FF0000', 'italic' => true]
                    );
                    return;
                }

                // Buat nama file temporary dengan ekstensi yang benar
                $tempImagePath = storage_path('app/temp/lampiran_' . time() . '_' . $bukti->id . '.' . $originalExtension);

                // Pastikan folder temp ada
                if (!file_exists(storage_path('app/temp'))) {
                    mkdir(storage_path('app/temp'), 0755, true);
                }

                // Simpan content ke file temporary
                $writeResult = file_put_contents($tempImagePath, $imageContent);

                if ($writeResult === false) {
                    throw new \Exception('Gagal menyimpan file temporary');
                }

                // Validasi file yang didownload
                if (!file_exists($tempImagePath) || filesize($tempImagePath) == 0) {
                    throw new \Exception('File temporary kosong atau tidak ada');
                }

                // Validasi apakah benar-benar file gambar
                $imageInfo = @getimagesize($tempImagePath);
                if ($imageInfo === false) {
                    throw new \Exception('File bukan gambar yang valid');
                }

                // Hitung dimensi gambar untuk fit ke halaman
                $maxWidth = Converter::cmToPixel(15);   // 15 cm max width
                $maxHeight = Converter::cmToPixel(20);  // 20 cm max height

                $imageWidth = $imageInfo[0];
                $imageHeight = $imageInfo[1];

                // Hitung ratio untuk maintain aspect ratio
                $widthRatio = $maxWidth / $imageWidth;
                $heightRatio = $maxHeight / $imageHeight;
                $ratio = min($widthRatio, $heightRatio, 1); // Tidak lebih besar dari ukuran asli

                $finalWidth = $imageWidth * $ratio;
                $finalHeight = $imageHeight * $ratio;

                // Tambahkan gambar ke dokumen dengan ukuran yang sesuai
                $section->addImage($tempImagePath, [
                    'width' => $finalWidth,
                    'height' => $finalHeight,
                    'wrappingStyle' => 'inline',
                    'positioning' => 'relative',
                    'alignment' => 'center'
                ]);

                // Tambahkan informasi gambar
                $section->addTextBreak();
                $section->addText(
                    'Ukuran asli: ' . $imageWidth . 'x' . $imageHeight . ' pixels',
                    ['size' => 9, 'italic' => true],
                    ['alignment' => 'center']
                );

                // Hapus file temporary setelah digunakan
                if (file_exists($tempImagePath)) {
                    unlink($tempImagePath);
                }
            } else {
                $section->addText(
                    'Gagal mengunduh gambar dari Google Drive',
                    ['color' => 'FF0000', 'italic' => true]
                );
            }
        } catch (\Exception $e) {
            // Log error untuk debugging
            Log::error('Error adding image to lampiran: ' . $e->getMessage(), [
                'bukti_id' => $bukti->id,
                'drive_id' => $bukti->drive_id,
                'nama_file' => $bukti->nama_file
            ]);

            $section->addText(
                'Error memuat gambar: ' . $e->getMessage(),
                ['color' => 'FF0000', 'italic' => true]
            );

            // Tambahkan fallback info
            $section->addTextBreak();
            $section->addText(
                'Gambar dapat diakses melalui: ' . $this->getGoogleDriveViewUrl($bukti->drive_id),
                ['size' => 9, 'underline' => 'single', 'color' => '0000FF']
            );
        }
    }

    private function addDocumentToLampiran($section, $bukti, $normalStyle)
    {
        // Untuk dokumen (PDF, Word, Excel, dll)
        $section->addText("Dokumen: {$bukti->nama_file}", $normalStyle);
        $section->addText("Tipe: {$bukti->file_type}", $normalStyle);
        $section->addText("Ukuran: " . $this->getFileSize($bukti), $normalStyle);

        // Info tambahan
        $section->addTextBreak();
        $section->addText(
            "Dokumen ini tersimpan di Google Drive dan dapat diakses melalui sistem.",
            ['italic' => true, 'size' => 10]
        );

        // Jika ingin embed preview dokumen (advanced)
        // $this->addDocumentPreview($section, $bukti);
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
