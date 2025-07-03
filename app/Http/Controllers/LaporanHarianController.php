<?php

namespace App\Http\Controllers;

use App\Models\LaporanHarian;
use App\Models\LaporanDasarPelaksanaan;
use App\Models\Proyek;
use App\Services\GoogleDriveService;
use App\Services\LaporanWordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LaporanHarianController extends Controller
{
    protected $driveService;
    protected $wordService;

    public function __construct(GoogleDriveService $driveService, LaporanWordService $wordService)
    {
        $this->driveService = $driveService;
        $this->wordService = $wordService;
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
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'jam_mulai' => 'nullable|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i|after:jam_mulai',
            'kegiatan' => 'required|string',
            'capaian' => 'required|string',
            'kendala' => 'nullable|string',
            'solusi' => 'nullable|string',
            'catatan' => 'nullable|string',
            'dasar_pelaksanaan' => 'required|array',
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

            // Generate dokumen Word
            try {
                $documentPath = $this->wordService->generateLaporan($laporan);

                if (!$documentPath || !file_exists($documentPath)) {
                    throw new \Exception('Gagal membuat dokumen Word. File tidak ditemukan.');
                }
            } catch (\Exception $e) {
                throw new \Exception('Error saat generate dokumen Word: ' . $e->getMessage());
            }

            // Upload ke Google Drive
            try {
                $filename = $this->generateFilename($laporan);
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
            } catch (\Exception $e) {
                // Jika error upload, tetap simpan laporan tapi tanpa file
                Log::error('Error upload to Google Drive: ' . $e->getMessage());

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
                ->with('popup_message', 'Laporan harian telah berhasil dibuat dan disimpan ke Google Drive.');
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
                ->with('error', 'Terjadi kesalahan saat membuat laporan.')
                ->with('popup_type', 'error')
                ->with('popup_title', 'Error!')
                ->with('popup_message', $this->getErrorMessage($e))
                ->withInput();
        }
    }

    /**
     * Get user-friendly error message based on exception type
     */
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

        // PhpWord errors
        if (str_contains($message, 'PhpWord') || str_contains($message, 'Word')) {
            return 'Terjadi masalah saat membuat dokumen. Coba periksa data yang diinput.';
        }

        // Memory errors
        if (str_contains($message, 'memory') || str_contains($message, 'Memory')) {
            return 'File terlalu besar untuk diproses. Coba kurangi jumlah bukti dukung.';
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

        if ($laporan->drive_id) {
            $url = $this->driveService->getDownloadUrl($laporan->drive_id);
            return redirect()->away($url);
        }

        return redirect()->back()->with('error', 'File laporan tidak ditemukan.');
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
     * Generate nama file laporan.
     */
    private function generateFilename($laporan)
    {
        $proyek = Str::slug($laporan->proyek->nama_proyek);
        $tanggal = $laporan->tanggal_mulai->format('Y-m-d');
        $user = Str::slug($laporan->user->name);

        return "Laporan_Harian_{$proyek}_{$tanggal}_{$user}.docx";
    }
}