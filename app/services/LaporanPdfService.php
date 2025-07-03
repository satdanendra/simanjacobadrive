<?php

namespace App\Services;

use App\Models\LaporanHarian;
use Dompdf\Dompdf;
use Dompdf\Options;
use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\GoogleDriveService;

class LaporanPdfService
{
    protected $driveService;
    protected $tempPath;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
        $this->tempPath = storage_path('app/temp/');
        
        // Buat folder temp jika belum ada
        if (!file_exists($this->tempPath)) {
            mkdir($this->tempPath, 0755, true);
        }
    }

    public function generateLaporanWithAttachments(LaporanHarian $laporan)
    {
        try {
            Log::info("Mulai generate laporan PDF", ['laporan_id' => $laporan->id]);

            // Step 1: Generate PDF laporan utama
            $mainPdfPath = $this->generateMainReport($laporan);
            
            // Step 2: Download dan convert lampiran ke PDF
            $attachmentPdfs = $this->prepareAttachments($laporan);
            
            // Step 3: Merge semua PDF
            $mergedPdfPath = $this->mergePdfs($mainPdfPath, $attachmentPdfs, $laporan);
            
            // Step 4: Cleanup temporary files
            $this->cleanupTempFiles(array_merge([$mainPdfPath], $attachmentPdfs));
            
            Log::info("Laporan PDF berhasil dibuat", ['laporan_id' => $laporan->id, 'file' => $mergedPdfPath]);
            
            return $mergedPdfPath;
            
        } catch (\Exception $e) {
            Log::error("Error generate laporan PDF: " . $e->getMessage(), [
                'laporan_id' => $laporan->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Cleanup jika terjadi error
            $this->cleanupTempFiles();
            
            throw $e;
        }
    }

    private function generateMainReport(LaporanHarian $laporan)
    {
        // Setup DomPDF
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        // Generate HTML content
        $html = $this->generateHtmlContent($laporan);
        
        // Load HTML ke DomPDF
        $dompdf->loadHtml($html);
        
        // Set paper size dan orientation
        $dompdf->setPaper('A4', 'portrait');
        
        // Render PDF
        $dompdf->render();
        
        // Save ke temporary file
        $filename = 'laporan_main_' . $laporan->id . '_' . time() . '.pdf';
        $filepath = $this->tempPath . $filename;
        
        file_put_contents($filepath, $dompdf->output());
        
        return $filepath;
    }

    private function generateHtmlContent(LaporanHarian $laporan)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 2cm;
                    font-size: 10pt;
                }
                .header {
                    text-align: center;
                    font-size: 14pt;
                    font-weight: bold;
                    margin-bottom: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 15px;
                }
                table, th, td {
                    border: 1px solid black;
                }
                th, td {
                    padding: 8px;
                    vertical-align: top;
                }
                .section-header {
                    background-color: #7CD440;
                    font-weight: bold;
                    text-align: center;
                }
                .label-cell {
                    width: 30%;
                    font-weight: normal;
                }
                .value-cell {
                    font-weight: bold;
                }
                .full-width {
                    width: 100%;
                }
                .center {
                    text-align: center;
                }
                .page-break {
                    page-break-after: always;
                }
            </style>
        </head>
        <body>
            <div class="header">Laporan Harian Pelaksanaan Pekerjaan</div>
            
            <!-- Identitas Pegawai -->
            <table>
                <tr>
                    <th colspan="2" class="section-header">Identitas Pegawai</th>
                </tr>
                <tr>
                    <td class="label-cell">Nama Pegawai</td>
                    <td class="value-cell">' . htmlspecialchars($laporan->user->name) . '</td>
                </tr>
                <tr>
                    <td class="label-cell">Email</td>
                    <td class="value-cell">' . htmlspecialchars($laporan->user->email) . '</td>
                </tr>
            </table>

            <!-- Rencana Kerja dan Kegiatan -->
            <table>
                <tr>
                    <th colspan="2" class="section-header">Rencana Kerja dan Kegiatan</th>
                </tr>
                <tr>
                    <td class="label-cell">Tim Kegiatan</td>
                    <td class="value-cell">' . htmlspecialchars($laporan->proyek->tim->nama_tim) . '</td>
                </tr>
                <tr>
                    <td class="label-cell">Proyek</td>
                    <td class="value-cell">' . htmlspecialchars($laporan->proyek->nama_proyek) . '</td>
                </tr>
                <tr>
                    <td class="label-cell">Waktu</td>
                    <td class="value-cell">' . htmlspecialchars($laporan->formatted_waktu) . '</td>
                </tr>
                <tr>
                    <td class="label-cell">Kegiatan</td>
                    <td class="value-cell">' . nl2br(htmlspecialchars($laporan->kegiatan)) . '</td>
                </tr>
                <tr>
                    <td class="label-cell">Capaian</td>
                    <td class="value-cell">' . nl2br(htmlspecialchars($laporan->capaian)) . '</td>
                </tr>
            </table>

            <!-- Dasar Pelaksanaan Kegiatan -->
            <table>
                <tr>
                    <th colspan="2" class="section-header">Dasar Pelaksanaan Kegiatan</th>
                </tr>';
        
        if ($laporan->dasarPelaksanaans->count() > 0) {
            foreach ($laporan->dasarPelaksanaans as $index => $dasar) {
                $number = $index + 1;
                $html .= '
                <tr>
                    <td style="width: 10%;">' . $number . '.</td>
                    <td>' . nl2br(htmlspecialchars($dasar->formatted_deskripsi)) . '</td>
                </tr>';
            }
        } else {
            $html .= '
                <tr>
                    <td colspan="2">1. -</td>
                </tr>';
        }
        
        $html .= '</table>';

        // Bukti Pelaksanaan Pekerjaan
        $html .= '
            <table>
                <tr>
                    <th class="section-header">Bukti Pelaksanaan Pekerjaan (Foto/Dokumentasi/Screenshot/Print Screen, dll)</th>
                </tr>';
        
        if ($laporan->buktiDukungs->count() > 0) {
            foreach ($laporan->buktiDukungs as $index => $bukti) {
                $number = $index + 1;
                $text = $number . '. ' . $bukti->nama_file . ' (Lihat Lampiran ' . $number . ')';
                
                if ($bukti->keterangan) {
                    $text .= ' - ' . $bukti->keterangan;
                }
                
                $html .= '
                <tr>
                    <td>' . htmlspecialchars($text) . '</td>
                </tr>';
            }
        } else {
            $html .= '
                <tr>
                    <td>1. -</td>
                </tr>';
        }
        
        $html .= '</table>';

        // Kendala dan Solusi
        $html .= '
            <table>
                <tr>
                    <th class="section-header center">Kendala</th>
                    <th class="section-header center">Solusi</th>
                </tr>
                <tr>
                    <td>' . nl2br(htmlspecialchars($laporan->kendala ?: '-')) . '</td>
                    <td>' . nl2br(htmlspecialchars($laporan->solusi ?: '-')) . '</td>
                </tr>
            </table>';

        // Catatan
        $html .= '
            <table>
                <tr>
                    <th class="section-header">Catatan</th>
                </tr>
                <tr>
                    <td>' . nl2br(htmlspecialchars($laporan->catatan ?: '-')) . '</td>
                </tr>
            </table>';

        // Pengesahan
        $html .= '
            <table style="margin-top: 30px;">
                <tr>
                    <th colspan="3" class="section-header">Pengesahan</th>
                </tr>
                <tr>
                    <td colspan="3" class="center">Mengetahui,</td>
                </tr>
                <tr>
                    <td class="center">Pegawai yang Melaporkan,</td>
                    <td class="center">Penanggung Jawab Kegiatan,</td>
                    <td class="center">Atasan Langsung,</td>
                </tr>
                <tr style="height: 80px;">
                    <td class="center">(_________________)</td>
                    <td class="center">(_________________)</td>
                    <td class="center">(_________________)</td>
                </tr>
            </table>

        </body>
        </html>';
        
        return $html;
    }

    private function prepareAttachments(LaporanHarian $laporan)
    {
        $attachmentPdfs = [];
        
        if ($laporan->buktiDukungs->count() == 0) {
            return $attachmentPdfs;
        }
        
        foreach ($laporan->buktiDukungs as $index => $bukti) {
            try {
                Log::info("Processing attachment", ['bukti_id' => $bukti->id, 'filename' => $bukti->nama_file]);
                
                // Download file dari Google Drive
                $localPath = $this->downloadFromDrive($bukti);
                
                if (!$localPath) {
                    Log::warning("Skip attachment, download failed", ['bukti_id' => $bukti->id]);
                    continue;
                }
                
                // Convert ke PDF jika bukan PDF
                $pdfPath = $this->convertToPdf($localPath, $bukti, $index + 1);
                
                if ($pdfPath) {
                    $attachmentPdfs[] = $pdfPath;
                }
                
                // Hapus file download temporary
                if (file_exists($localPath)) {
                    unlink($localPath);
                }
                
            } catch (\Exception $e) {
                Log::error("Error processing attachment", [
                    'bukti_id' => $bukti->id,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }
        
        return $attachmentPdfs;
    }

    private function downloadFromDrive($bukti)
    {
        try {
            $filename = 'download_' . $bukti->id . '_' . time() . '.' . pathinfo($bukti->nama_file, PATHINFO_EXTENSION);
            $localPath = $this->tempPath . $filename;
            
            $content = $this->driveService->downloadFile($bukti->drive_id);
            
            if ($content) {
                file_put_contents($localPath, $content);
                return $localPath;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error("Error downloading from drive", [
                'bukti_id' => $bukti->id,
                'drive_id' => $bukti->drive_id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function convertToPdf($filePath, $bukti, $lampiran_number)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $outputPath = $this->tempPath . 'lampiran_' . $lampiran_number . '_' . time() . '.pdf';
        
        try {
            switch ($extension) {
                case 'pdf':
                    // Sudah PDF, langsung copy
                    copy($filePath, $outputPath);
                    return $outputPath;
                    
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif':
                    return $this->convertImageToPdf($filePath, $outputPath, $bukti, $lampiran_number);
                    
                case 'txt':
                    return $this->convertTextToPdf($filePath, $outputPath, $bukti, $lampiran_number);
                    
                default:
                    // Untuk file lain (doc, xls, dll), buat halaman placeholder
                    return $this->createPlaceholderPdf($outputPath, $bukti, $lampiran_number);
            }
            
        } catch (\Exception $e) {
            Log::error("Error converting to PDF", [
                'file' => $filePath,
                'extension' => $extension,
                'error' => $e->getMessage()
            ]);
            
            // Buat placeholder jika conversion gagal
            return $this->createPlaceholderPdf($outputPath, $bukti, $lampiran_number);
        }
    }

    private function convertImageToPdf($imagePath, $outputPath, $bukti, $lampiran_number)
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        // Encode image to base64
        $imageData = base64_encode(file_get_contents($imagePath));
        $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
        $mimeType = 'image/' . ($extension == 'jpg' ? 'jpeg' : $extension);
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { margin: 0; padding: 20px; font-family: Arial, sans-serif; }
                .header { text-align: center; margin-bottom: 20px; font-weight: bold; }
                .image-container { text-align: center; }
                img { max-width: 100%; max-height: 80vh; }
            </style>
        </head>
        <body>
            <div class="header">Lampiran ' . $lampiran_number . ': ' . htmlspecialchars($bukti->nama_file) . '</div>
            <div class="image-container">
                <img src="data:' . $mimeType . ';base64,' . $imageData . '" alt="' . htmlspecialchars($bukti->nama_file) . '">
            </div>
        </body>
        </html>';
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        file_put_contents($outputPath, $dompdf->output());
        
        return $outputPath;
    }

    private function convertTextToPdf($textPath, $outputPath, $bukti, $lampiran_number)
    {
        $content = file_get_contents($textPath);
        
        $options = new Options();
        $dompdf = new Dompdf($options);
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { margin: 20px; font-family: Arial, sans-serif; font-size: 12pt; }
                .header { text-align: center; margin-bottom: 20px; font-weight: bold; }
                .content { white-space: pre-wrap; }
            </style>
        </head>
        <body>
            <div class="header">Lampiran ' . $lampiran_number . ': ' . htmlspecialchars($bukti->nama_file) . '</div>
            <div class="content">' . htmlspecialchars($content) . '</div>
        </body>
        </html>';
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        file_put_contents($outputPath, $dompdf->output());
        
        return $outputPath;
    }

    private function createPlaceholderPdf($outputPath, $bukti, $lampiran_number)
    {
        $options = new Options();
        $dompdf = new Dompdf($options);
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { margin: 20px; font-family: Arial, sans-serif; text-align: center; }
                .placeholder { margin-top: 200px; }
                .filename { font-size: 18pt; font-weight: bold; margin-bottom: 20px; }
                .message { font-size: 14pt; color: #666; }
            </style>
        </head>
        <body>
            <div class="placeholder">
                <div class="filename">Lampiran ' . $lampiran_number . '</div>
                <div class="filename">' . htmlspecialchars($bukti->nama_file) . '</div>
                <div class="message">File lampiran tersedia di Google Drive<br/>Silakan akses melalui sistem untuk melihat file asli</div>
            </div>
        </body>
        </html>';
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        file_put_contents($outputPath, $dompdf->output());
        
        return $outputPath;
    }

    private function mergePdfs($mainPdf, $attachmentPdfs, $laporan)
    {
        try {
            $pdf = new Fpdi();
            
            // Add main report
            $this->addPdfToMerger($pdf, $mainPdf);
            
            // Add attachments
            foreach ($attachmentPdfs as $attachmentPdf) {
                $this->addPdfToMerger($pdf, $attachmentPdf);
            }
            
            // Save merged PDF
            $filename = 'laporan_merged_' . $laporan->id . '_' . time() . '.pdf';
            $filepath = $this->tempPath . $filename;
            
            $pdf->Output($filepath, 'F');
            
            return $filepath;
            
        } catch (\Exception $e) {
            Log::error("Error merging PDFs", [
                'laporan_id' => $laporan->id,
                'error' => $e->getMessage()
            ]);
            
            // Fallback: return main PDF only
            return $mainPdf;
        }
    }

    private function addPdfToMerger($pdf, $pdfPath)
    {
        try {
            $pageCount = $pdf->setSourceFile($pdfPath);
            
            for ($pageIndex = 1; $pageIndex <= $pageCount; $pageIndex++) {
                $pdf->AddPage();
                $tplId = $pdf->importPage($pageIndex);
                $pdf->useTemplate($tplId);
            }
            
        } catch (\Exception $e) {
            Log::error("Error adding PDF to merger", [
                'pdf_path' => $pdfPath,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function cleanupTempFiles($files = null)
    {
        try {
            if ($files) {
                foreach ($files as $file) {
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
            } else {
                // Clean all temp files older than 1 hour
                $tempFiles = glob($this->tempPath . '*');
                foreach ($tempFiles as $file) {
                    if (is_file($file) && filemtime($file) < (time() - 3600)) {
                        unlink($file);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error cleaning temp files", ['error' => $e->getMessage()]);
        }
    }
}