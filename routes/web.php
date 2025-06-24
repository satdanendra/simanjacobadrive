<?php

use App\Http\Controllers\BuktiDukungController;
use App\Http\Controllers\LaporanHarianController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProyekController;
use App\Http\Controllers\TimController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Routes untuk Tim
    Route::get('/tim', [TimController::class, 'index'])->name('tim.index');
    Route::get('/tim/import', [TimController::class, 'importForm'])->name('tim.import.form');
    Route::post('/tim/import', [TimController::class, 'import'])->name('tim.import');

    // Routes untuk Proyek
    Route::get('/tim/{tim}/proyek', [ProyekController::class, 'index'])->name('proyek.index');
    Route::get('/tim/{tim}/proyek/create', [ProyekController::class, 'create'])->name('proyek.create');
    Route::post('/tim/{tim}/proyek', [ProyekController::class, 'store'])->name('proyek.store');

    // Routes untuk Bukti Dukung
    Route::get('/proyek/{proyek}/bukti-dukung', [BuktiDukungController::class, 'index'])->name('bukti-dukung.index');
    Route::get('/proyek/{proyek}/bukti-dukung/create', [BuktiDukungController::class, 'create'])->name('bukti-dukung.create');
    Route::post('/proyek/{proyek}/bukti-dukung', [BuktiDukungController::class, 'store'])->name('bukti-dukung.store');
    Route::delete('/bukti-dukung/{buktiDukung}', [BuktiDukungController::class, 'destroy'])->name('bukti-dukung.destroy');
    Route::get('/bukti-dukung/{buktiDukung}/view', [BuktiDukungController::class, 'view'])->name('bukti-dukung.view');
    Route::get('/bukti-dukung/{buktiDukung}/download', [BuktiDukungController::class, 'download'])->name('bukti-dukung.download');

    // Routes untuk Laporan Harian
    Route::get('/laporan-harian', [LaporanHarianController::class, 'index'])->name('laporan-harian.index');
    Route::get('/proyek/{proyek}/laporan-harian/create', [LaporanHarianController::class, 'create'])->name('laporan-harian.create');
    Route::post('/proyek/{proyek}/laporan-harian', [LaporanHarianController::class, 'store'])->name('laporan-harian.store');
    Route::get('/laporan-harian/{laporan}', [LaporanHarianController::class, 'show'])->name('laporan-harian.show');
    Route::get('/laporan-harian/{laporan}/download', [LaporanHarianController::class, 'download'])->name('laporan-harian.download');
    Route::get('/laporan-harian/{laporan}/view', [LaporanHarianController::class, 'view'])->name('laporan-harian.view');
    Route::delete('/laporan-harian/{laporan}', [LaporanHarianController::class, 'destroy'])->name('laporan-harian.destroy');
});

// Route untuk Google OAuth callback
Route::get('/auth/google/callback', function (Request $request) {
    // Tampilkan kode auth untuk disalin
    if ($request->has('code')) {
        return view('google-callback', ['code' => $request->code]);
    }

    return 'Tidak ada kode authorization yang diterima.';
});

require __DIR__ . '/auth.php';
