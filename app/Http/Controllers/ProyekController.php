<?php

namespace App\Http\Controllers;

use App\Models\Proyek;
use App\Models\Tim;
use Illuminate\Http\Request;

class ProyekController extends Controller
{
    /**
     * Menampilkan daftar proyek untuk tim tertentu.
     */
    public function index($timId)
    {
        $tim = Tim::findOrFail($timId);
        $proyeks = $tim->proyeks;
        
        return view('proyek.index', compact('tim', 'proyeks'));
    }

    /**
     * Menampilkan form untuk membuat proyek baru.
     */
    public function create($timId)
    {
        $tim = Tim::findOrFail($timId);
        
        return view('proyek.create', compact('tim'));
    }

    /**
     * Menyimpan proyek baru.
     */
    public function store(Request $request, $timId)
    {
        $request->validate([
            'kode_proyek' => 'required|string|unique:proyeks',
            'nama_proyek' => 'required|string|max:255',
        ]);

        $tim = Tim::findOrFail($timId);
        
        $proyek = new Proyek([
            'kode_proyek' => $request->kode_proyek,
            'nama_proyek' => $request->nama_proyek,
        ]);
        
        $tim->proyeks()->save($proyek);
        
        return redirect()->route('proyek.index', $tim->id)
            ->with('success', 'Proyek berhasil ditambahkan');
    }
}