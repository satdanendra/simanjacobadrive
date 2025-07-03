<?php

namespace App\Http\Controllers;

use App\Models\LaporanHarian;
use App\Models\LaporanDasarPelaksanaan;
use App\Models\Proyek;
use App\Services\GoogleDriveService;
use App\Services\LaporanPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LaporanHarianController extends Controller
{
    protected $driveService;
    protected $pdfService;

    public function __construct(GoogleDriveService $driveService, LaporanPdfService $pdfService)
    {
        $this->driveService = $driveService;
        $this->pdfService = $pdfService;
    }

    /**
     * Menampilkan daftar laporan harian user.
     */
    public function index()
    {
        $laporans = LaporanHarian::with(['proyek.tim', 'user'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('laporan-harian.index', compact('laporans'));
    }

    /**
     * Menampilkan form untuk membuat laporan baru.
     */
    public function create($proyekId)
    {
        $proyek = Proyek::with(['tim', 'buktiDukungs'])->findOrFail($proyekId);

        // Pastikan user adalah bagian dari tim proyek ini
        // Asumsi: ada relasi user dengan tim (perlu disesuaikan dengan struktur data Anda)

        return view('laporan-harian.create', compact('proyek'));
    }

    /**
     * Menyimpan laporan baru.
     */
    public function store(Request $request, $proyekId)
    {
        $request->validate([
            'judul_laporan' => 'required|string|max:255',
            'tipe_waktu' => 'required|in:satu_hari,rentang_hari',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required_if:tipe_waktu,rentang_hari|nullable|date|after_or_equal:tanggal_mulai',
            'jam_mulai' => 'required_if:tipe_waktu,satu_hari|nullable|date_format:H:i',
            'jam_selesai' => 'required_if:tipe_waktu,satu_hari|nullable|date_format:H:i|after:jam_mulai',
            'kegiatan' => 'required|string',
            'capaian' => 'required|string',
            'kendala' => 'nullable|string',
            'solusi' => 'nullable|string',
            'catatan' => 'nullable|string',
            'dasar_pelaksanaan' => 'required|array|min:1',
            'dasar_pelaksanaan.*.deskripsi' => 'required|string',
            'dasar_pelaksanaan.*.bukti_dukung_id' => 'nullable|exists:bukti_dukungs,id',
            'bukti_dukung_ids' => 'array',
            'bukti_dukung_ids.*' => 'exists:bukti_dukungs,id',
        ]);

        $proyek = Proyek::findOrFail($proyekId);

        DB::beginTransaction();

        try {
            // Buat laporan harian
            $laporan = LaporanHarian::create([
                'user_id' => Auth::id(),
                'proyek_id' => $proyek->id,
                'judul_laporan' => $request->judul_laporan,
                'tipe_waktu' => $request->tipe_waktu,
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_selesai' => $request->tipe_waktu === 'rentang_hari' ? $request->tanggal_selesai : null,
                'jam_mulai' => $request->tipe_waktu === 'satu_hari' ? $request->jam_mulai : null,
                'jam_selesai' => $request->tipe_waktu === 'satu_hari' ? $request->jam_selesai : null,
                'kegiatan' => $request->kegiatan,
                'capaian' => $request->capaian,
                'kendala' => $request->kendala,
                'solusi' => $request->solusi,
                'catatan' => $request->catatan,
            ]);

            // Simpan dasar pelaksanaan
            foreach ($request->dasar_pelaksanaan as $index => $dasar) {
                LaporanDasarPelaksanaan::create([
                    'laporan_harian_id' => $laporan->id,
                    'deskripsi' => $dasar['deskripsi'],
                    'bukti_dukung_id' => $dasar['bukti_dukung_id'] ?? null,
                    'is_terlampir' => !empty($dasar['bukti_dukung_id']),
                    'urutan' => $index + 1,
                ]);
            }

            // Simpan relasi bukti dukung
            if (!empty($request->bukti_dukung_ids)) {
                foreach ($request->bukti_dukung_ids as $index => $buktiDukungId) {
                    $laporan->buktiDukungs()->attach($buktiDukungId, ['urutan' => $index + 1]);
                }
            }

            // Generate dokumen PDF dengan lampiran
            try {
                Log::info("Mulai generate PDF dengan lampiran", ['laporan_id' => $laporan->id]);

                $documentPath = $this->pdfService->generateLaporanWithAttachments($laporan);

                if (!$documentPath || !file_exists($documentPath)) {
                    throw new \Exception('Gagal membuat dokumen PDF. File tidak ditemukan.');
                }

                Log::info("PDF berhasil dibuat", ['laporan_id' => $laporan->id, 'path' => $documentPath]);
            } catch (\Exception $e) {
                Log::error("Error generate PDF: " . $e->getMessage(), [
                    'laporan_id' => $laporan->id,
                    'trace' => $e->getTraceAsString()
                ]);
                throw new \Exception('Error saat generate dokumen PDF: ' . $e->getMessage());
            }

            // Upload ke Google Drive
            try {
                $filename = $this->generateFilename($laporan, 'pdf');  // Update extension

                Log::info("Mulai upload ke Google Drive", [
                    'laporan_id' => $laporan->id,
                    'filename' => $filename
                ]);

                $driveId = $this->driveService->uploadFile($documentPath, $filename);

                if (!$driveId) {
                    throw new \Exception('Gagal mengupload file ke Google Drive. Silakan coba lagi.');
                }

                $laporan->update([
                    'file_path' => $filename,
                    'drive_id' => $driveId,
                ]);

                // Hapus file temporary
                if (file_exists($documentPath)) {
                    unlink($documentPath);
                }

                Log::info("Upload ke Google Drive berhasil", [
                    'laporan_id' => $laporan->id,
                    'drive_id' => $driveId
                ]);
            } catch (\Exception $e) {
                // Jika error upload, tetap simpan laporan tapi tanpa file
                Log::error('Error upload to Google Drive: ' . $e->getMessage(), [
                    'laporan_id' => $laporan->id
                ]);

                // Hapus file temporary jika ada
                if (isset($documentPath) && file_exists($documentPath)) {
                    unlink($documentPath);
                }

                DB::commit();

                return redirect()->route('laporan-harian.show', $laporan->id)
                    ->with('warning', 'Laporan berhasil dibuat, tetapi gagal mengupload ke Google Drive: ' . $e->getMessage())
                    ->with('popup_type', 'warning')
                    ->with('popup_title', 'Upload Gagal')
                    ->with('popup_message', 'Laporan sudah tersimpan di database, namun tidak bisa diupload ke Google Drive. Anda bisa mencoba generate ulang nanti.');
            }

            DB::commit();

            return redirect()->route('laporan-harian.show', $laporan->id)
                ->with('success', 'Laporan harian berhasil dibuat dan disimpan ke Google Drive.')
                ->with('popup_type', 'success')
                ->with('popup_title', 'Berhasil!')
                ->with('popup_message', 'Laporan harian telah berhasil dibuat dengan lampiran dan disimpan ke Google Drive.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            throw $e; // Re-throw validation errors

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error creating laporan harian: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'proyek_id' => $proyekId,
                'error' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat membuat laporan: ' . $e->getMessage())
                ->with('popup_type', 'error')
                ->with('popup_title', 'Error!')
                ->with('popup_message', 'Gagal membuat laporan. Silakan coba lagi.')
                ->withInput();
        }
    }

    // Update method getErrorMessage() untuk menangani PDF errors
    private function getErrorMessage(\Exception $e)
    {
        $message = $e->getMessage();

        // Database errors
        if (str_contains($message, 'Connection refused') || str_contains($message, 'SQLSTATE')) {
            return 'Terjadi masalah koneksi database. Silakan coba lagi dalam beberapa saat.';
        }

        // Google Drive errors
        if (str_contains($message, 'Google') || str_contains($message, 'Drive') || str_contains($message, 'OAuth')) {
            return 'Terjadi masalah saat mengakses Google Drive. Periksa koneksi internet dan coba lagi.';
        }

        // File system errors
        if (str_contains($message, 'Permission denied') || str_contains($message, 'mkdir')) {
            return 'Terjadi masalah izin file sistem. Hubungi administrator.';
        }

        // PDF Generation errors (NEW)
        if (str_contains($message, 'DomPDF') || str_contains($message, 'dompdf')) {
            return 'Terjadi masalah saat membuat PDF. Periksa format data yang diinput.';
        }

        // PDF Merge errors (NEW)
        if (str_contains($message, 'FPDI') || str_contains($message, 'merge') || str_contains($message, 'PDF')) {
            return 'Terjadi masalah saat menggabung lampiran PDF. Periksa format file lampiran.';
        }

        // File conversion errors (NEW)
        if (str_contains($message, 'convert') || str_contains($message, 'image') || str_contains($message, 'attachment')) {
            return 'Terjadi masalah saat memproses lampiran. Periksa format dan ukuran file lampiran.';
        }

        // PhpWord errors (Keep for backward compatibility)
        if (str_contains($message, 'PhpWord') || str_contains($message, 'Word')) {
            return 'Terjadi masalah saat membuat dokumen. Coba periksa data yang diinput.';
        }

        // Memory errors
        if (str_contains($message, 'memory') || str_contains($message, 'Memory')) {
            return 'File terlalu besar untuk diproses. Coba kurangi jumlah atau ukuran lampiran.';
        }

        // Timeout errors (NEW for PDF processing)
        if (str_contains($message, 'timeout') || str_contains($message, 'Timeout')) {
            return 'Proses pembuatan PDF membutuhkan waktu terlalu lama. Coba kurangi jumlah lampiran.';
        }

        // Generic error for unknown issues
        return 'Terjadi kesalahan sistem yang tidak diketahui. Silakan coba lagi atau hubungi administrator jika masalah berlanjut.';
    }

    /**
     * Menampilkan detail laporan.
     */
    public function show($id)
    {
        $laporan = LaporanHarian::with([
            'proyek.tim',
            'user',
            'dasarPelaksanaans.buktiDukung',
            'buktiDukungs'
        ])->findOrFail($id);

        // Pastikan user hanya bisa melihat laporan miliknya
        if ($laporan->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }

        return view('laporan-harian.show', compact('laporan'));
    }

    /**
     * Download laporan dari Google Drive.
     */
    public function download($id)
    {
        $laporan = LaporanHarian::findOrFail($id);

        if ($laporan->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }

        try {
            if (!$laporan->drive_id) {
                return redirect()->back()->with('error', 'File laporan tidak ditemukan.');
            }

            // Download direct content dari Google Drive
            $fileContent = $this->driveService->downloadFile($laporan->drive_id);

            if (!$fileContent) {
                return redirect()->back()->with('error', 'Gagal mengunduh file dari Google Drive.');
            }

            $filename = $laporan->file_path ?: $this->generateFilename($laporan, 'pdf');

            return response($fileContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Exception $e) {
            Log::error('Error downloading laporan: ' . $e->getMessage(), [
                'laporan_id' => $laporan->id
            ]);

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat mengunduh laporan: ' . $this->getErrorMessage($e));
        }
    }

    /**
     * View laporan di Google Drive.
     */
    public function view($id)
    {
        $laporan = LaporanHarian::findOrFail($id);

        if ($laporan->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }

        if ($laporan->drive_id) {
            $url = $this->driveService->getViewUrl($laporan->drive_id);
            return redirect()->away($url);
        }

        return redirect()->back()->with('error', 'File laporan tidak ditemukan.');
    }

    /**
     * Hapus laporan.
     */
    public function destroy($id)
    {
        $laporan = LaporanHarian::findOrFail($id);

        if ($laporan->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }

        // Hapus file dari Google Drive
        if ($laporan->drive_id) {
            $this->driveService->deleteFile($laporan->drive_id);
        }

        $laporan->delete();

        return redirect()->route('laporan-harian.index')
            ->with('success', 'Laporan berhasil dihapus.');
    }

    /**
     * Generate filename untuk PDF
     */
    private function generateFilename($laporan, $extension = 'pdf')
    {
        $timestamp = now()->format('Ymd_His');
        $proyekCode = Str::slug($laporan->proyek->kode_proyek ?? 'PROJ');
        $userSlug = Str::slug($laporan->user->name);

        return "Laporan_Harian_{$proyekCode}_{$userSlug}_{$timestamp}.{$extension}";
    }

    /**
     * Regenerate laporan PDF (untuk laporan yang sudah ada)
     */
    public function regenerate(LaporanHarian $laporan)
    {
        try {
            DB::beginTransaction();
            
            // Generate ulang PDF
            $documentPath = $this->pdfService->generateLaporanWithAttachments($laporan);
            
            if (!$documentPath || !file_exists($documentPath)) {
                throw new \Exception('Gagal membuat ulang dokumen PDF.');
            }
            
            // Upload ke Google Drive (replace existing)
            $filename = $this->generateFilename($laporan, 'pdf');
            
            // Hapus file lama jika ada
            if ($laporan->drive_id) {
                try {
                    $this->driveService->deleteFile($laporan->drive_id);
                } catch (\Exception $e) {
                    Log::warning('Failed to delete old file from Drive', [
                        'laporan_id' => $laporan->id,
                        'old_drive_id' => $laporan->drive_id
                    ]);
                }
            }
            
            $driveId = $this->driveService->uploadFile($documentPath, $filename);
            
            if (!$driveId) {
                throw new \Exception('Gagal mengupload file baru ke Google Drive.');
            }
            
            $laporan->update([
                'file_path' => $filename,
                'drive_id' => $driveId,
            ]);
            
            // Hapus file temporary
            if (file_exists($documentPath)) {
                unlink($documentPath);
            }
            
            DB::commit();
            
            return redirect()->back()
                ->with('success', 'Laporan berhasil di-generate ulang.')
                ->with('popup_type', 'success')
                ->with('popup_title', 'Berhasil!')
                ->with('popup_message', 'Laporan PDF telah berhasil di-generate ulang dengan lampiran terbaru.');
                
        } catch (\Exception $e) {
            DB::rollback();
            
            // Cleanup file temporary
            if (isset($documentPath) && file_exists($documentPath)) {
                unlink($documentPath);
            }
            
            Log::error('Error regenerating laporan: ' . $e->getMessage(), [
                'laporan_id' => $laporan->id
            ]);
            
            return redirect()->back()
                ->with('error', 'Gagal generate ulang laporan: ' . $e->getMessage())
                ->with('popup_type', 'error')
                ->with('popup_title', 'Error!')
                ->with('popup_message', 'Terjadi kesalahan saat generate ulang laporan.');
        }
    }
}
