<?php

namespace App\Http\Controllers;

use App\Models\BuktiDukung;
use App\Models\Proyek;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BuktiDukungController extends Controller
{
    protected $driveService;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    /**
     * Menampilkan daftar bukti dukung untuk proyek tertentu.
     */
    public function index($proyekId)
    {
        $proyek = Proyek::with('tim')->findOrFail($proyekId);
        $buktiDukungs = $proyek->buktiDukungs;

        return view('bukti_dukung.index', compact('proyek', 'buktiDukungs'));
    }

    /**
     * Menampilkan form untuk menambah bukti dukung.
     */
    public function create($proyekId)
    {
        $proyek = Proyek::with('tim')->findOrFail($proyekId);

        return view('bukti_dukung.create', compact('proyek'));
    }

    /**
     * Menyimpan bukti dukung baru.
     */
    public function store(Request $request, $proyekId)
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $proyek = Proyek::findOrFail($proyekId);

        $uploadedFiles = [];
        $failedFiles = [];

        DB::beginTransaction();

        try {
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    try {
                        $originalName = $file->getClientOriginalName();
                        $extension = $file->getClientOriginalExtension();
                        $fileType = $this->determineFileType($extension);
                        $mimeType = $file->getMimeType();

                        // Generate unique name untuk file
                        $filename = Str::slug($proyek->kode_proyek) . '_' . time() . '_' . uniqid() . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $extension;

                        // Upload ke Google Drive
                        $driveId = $this->driveService->uploadFile($file, $filename);

                        if ($driveId) {
                            // Simpan data bukti dukung ke database
                            BuktiDukung::create([
                                'proyek_id' => $proyek->id,
                                'nama_file' => $originalName,
                                'file_path' => 'google_drive',
                                'drive_id' => $driveId,
                                'file_type' => $fileType,
                                'extension' => $extension,
                                'mime_type' => $mimeType,
                                'keterangan' => $request->keterangan,
                            ]);

                            $uploadedFiles[] = $originalName;
                        } else {
                            $failedFiles[] = $originalName;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error uploading file: ' . $e->getMessage());
                        $failedFiles[] = $originalName;
                    }
                }
            }

            if (count($uploadedFiles) > 0) {
                DB::commit();

                $message = count($uploadedFiles) . ' file berhasil diunggah.';
                if (count($failedFiles) > 0) {
                    $message .= ' ' . count($failedFiles) . ' file gagal diunggah: ' . implode(', ', $failedFiles);
                }

                return redirect()->route('bukti-dukung.index', $proyek->id)
                    ->with('success', $message);
            } else {
                DB::rollback();
                return redirect()->back()
                    ->with('error', 'Semua file gagal diunggah ke Google Drive.')
                    ->withInput();
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error in bulk upload: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat mengunggah file.')
                ->withInput();
        }
    }

    /**
     * Menghapus bukti dukung.
     */
    public function destroy($id)
    {
        $buktiDukung = BuktiDukung::findOrFail($id);
        $proyekId = $buktiDukung->proyek_id;

        // Hapus file dari Google Drive jika ada drive_id
        if ($buktiDukung->drive_id) {
            $this->driveService->deleteFile($buktiDukung->drive_id);
        }

        $buktiDukung->delete();

        return redirect()->route('bukti-dukung.index', $proyekId)
            ->with('success', 'Bukti dukung berhasil dihapus.');
    }

    /**
     * Mendapatkan URL untuk melihat file di Google Drive.
     */
    public function view($id)
    {
        $buktiDukung = BuktiDukung::findOrFail($id);

        if ($buktiDukung->drive_id) {
            $url = $this->driveService->getViewUrl($buktiDukung->drive_id);
            return redirect()->away($url);
        }

        return redirect()->back()->with('error', 'File tidak ditemukan.');
    }

    /**
     * Mendapatkan URL untuk mendownload file dari Google Drive.
     */
    public function download($id)
    {
        $buktiDukung = BuktiDukung::findOrFail($id);

        if ($buktiDukung->drive_id) {
            $url = $this->driveService->getDownloadUrl($buktiDukung->drive_id);
            return redirect()->away($url);
        }

        return redirect()->back()->with('error', 'File tidak ditemukan.');
    }

    /**
     * Menentukan tipe file berdasarkan ekstensi.
     */
    private function determineFileType($extension)
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
        $documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];

        $extension = strtolower($extension);

        if (in_array($extension, $imageExtensions)) {
            return 'image';
        } elseif (in_array($extension, $documentExtensions)) {
            return 'document';
        } else {
            return 'other';
        }
    }
}
