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
            $documentPath = $this->wordService->generateLaporan($laporan);

            if ($documentPath) {
                // Upload ke Google Drive
                $filename = $this->generateFilename($laporan);
                $driveId = $this->driveService->uploadFile($documentPath, $filename);

                if ($driveId) {
                    $laporan->update([
                        'file_path' => $filename,
                        'drive_id' => $driveId,
                    ]);

                    // Hapus file temporary
                    unlink($documentPath);
                }
            }

            DB::commit();

            return redirect()->route('laporan-harian.show', $laporan->id)
                ->with('success', 'Laporan harian berhasil dibuat dan disimpan ke Google Drive.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat membuat laporan: ' . $e->getMessage())
                ->withInput();
        }
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