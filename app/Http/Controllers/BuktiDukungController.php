<?php

namespace App\Http\Controllers;

use App\Models\BuktiDukung;
use App\Models\Proyek;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'file' => 'required|file|max:10240', // max 10MB
            'keterangan' => 'nullable|string|max:255',
        ]);

        $proyek = Proyek::findOrFail($proyekId);
        
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileType = $this->determineFileType($extension);
            $mimeType = $file->getMimeType();
            
            // Generate unique name untuk file
            $filename = Str::slug($proyek->kode_proyek) . '_' . time() . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $extension;
            
            // Upload ke Google Drive
            $driveId = $this->driveService->uploadFile($file, $filename);
            
            if ($driveId) {
                // Simpan data bukti dukung ke database
                BuktiDukung::create([
                    'proyek_id' => $proyek->id,
                    'nama_file' => $originalName,
                    'file_path' => null, // Tidak perlu path lokal karena disimpan di Drive
                    'drive_id' => $driveId,
                    'file_type' => $fileType,
                    'extension' => $extension,
                    'mime_type' => $mimeType,
                    'keterangan' => $request->keterangan,
                ]);
                
                return redirect()->route('bukti-dukung.index', $proyek->id)
                    ->with('success', 'Bukti dukung berhasil diunggah ke Google Drive.');
            } else {
                return redirect()->back()->with('error', 'Gagal mengunggah file ke Google Drive.')->withInput();
            }
        }
        
        return redirect()->back()->with('error', 'Tidak ada file yang diunggah.')->withInput();
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