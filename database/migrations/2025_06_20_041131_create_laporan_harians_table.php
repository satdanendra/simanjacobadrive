<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('laporan_harians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('proyek_id')->constrained('proyeks')->onDelete('cascade');
            $table->string('judul_laporan');
            $table->enum('tipe_waktu', ['satu_hari', 'rentang_hari']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->text('kegiatan');
            $table->text('capaian');
            $table->text('kendala')->nullable();
            $table->text('solusi')->nullable();
            $table->text('catatan')->nullable();
            $table->string('file_path')->nullable(); // Path file di Google Drive
            $table->string('drive_id')->nullable(); // Google Drive file ID
            $table->timestamps();
        });

        Schema::create('laporan_dasar_pelaksanaans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_harian_id')->constrained('laporan_harians')->onDelete('cascade');
            $table->text('deskripsi');
            $table->foreignId('bukti_dukung_id')->nullable()->constrained('bukti_dukungs')->onDelete('set null');
            $table->boolean('is_terlampir')->default(false);
            $table->integer('urutan')->default(1);
            $table->timestamps();
        });

        Schema::create('laporan_bukti_dukungs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_harian_id')->constrained('laporan_harians')->onDelete('cascade');
            $table->foreignId('bukti_dukung_id')->constrained('bukti_dukungs')->onDelete('cascade');
            $table->integer('urutan')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_bukti_dukungs');
        Schema::dropIfExists('laporan_dasar_pelaksanaans');
        Schema::dropIfExists('laporan_harians');
    }
};