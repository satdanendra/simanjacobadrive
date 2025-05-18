<?php

namespace App\Http\Controllers;

use App\Models\Tim;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TimController extends Controller
{
    /**
     * Menampilkan daftar tim.
     */
    public function index()
    {
        $tims = Tim::all();
        
        return view('tim.index', compact('tims'));
    }

    /**
     * Halaman untuk mengunggah file Excel.
     */
    public function importForm()
    {
        return view('tim.import');
    }

    /**
     * Import data tim dari file Excel.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);

        $file = $request->file('file');
        
        // Baca file Excel
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Abaikan baris header (jika ada)
        // Asumsikan baris pertama adalah header
        array_shift($rows);
        
        $importCount = 0;
        
        foreach ($rows as $row) {
            if (empty($row[0]) || empty($row[1])) {
                continue; // Lewati baris kosong
            }
            
            Tim::updateOrCreate(
                ['kode_tim' => $row[0]], // Kunci pencarian
                ['nama_tim' => $row[1]]  // Data yang akan diupdate/dibuat
            );
            
            $importCount++;
        }
        
        return redirect()->route('tim.index')
            ->with('success', "Berhasil mengimpor $importCount data tim.");
    }
}