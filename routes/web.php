<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProyekController;
use App\Http\Controllers\TimController;
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
});

require __DIR__ . '/auth.php';
