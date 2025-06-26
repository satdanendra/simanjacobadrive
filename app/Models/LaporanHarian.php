<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanHarian extends Model
{
    use HasFactory;

    protected $table = 'laporan_harians';

    protected $fillable = [
        'user_id',
        'proyek_id',
        'judul_laporan',
        'tipe_waktu',
        'tanggal_mulai',
        'tanggal_selesai',
        'jam_mulai',
        'jam_selesai',
        'kegiatan',
        'capaian',
        'kendala',
        'solusi',
        'catatan',
        'file_path',
        'drive_id',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'jam_mulai' => 'datetime:H:i',
        'jam_selesai' => 'datetime:H:i',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function proyek()
    {
        return $this->belongsTo(Proyek::class);
    }

    public function dasarPelaksanaans()
    {
        return $this->hasMany(LaporanDasarPelaksanaan::class);
    }

    public function buktiDukungs()
    {
        return $this->belongsToMany(BuktiDukung::class, 'laporan_bukti_dukungs')
                    ->withPivot('urutan')
                    ->orderBy('pivot_urutan');
    }

    public function getFormattedWaktuAttribute()
    {
        if ($this->tipe_waktu === 'satu_hari') {
            $tanggal = $this->tanggal_mulai->isoFormat('D MMMM Y');
            if ($this->jam_mulai && $this->jam_selesai) {
                return $tanggal . ', ' . $this->jam_mulai->format('H:i') . ' - ' . $this->jam_selesai->format('H:i');
            }
            return $tanggal;
        } else {
            return $this->tanggal_mulai->isoFormat('D MMMM Y') . ' - ' . $this->tanggal_selesai->isoFormat('D MMMM Y');
        }
    }
}

class LaporanDasarPelaksanaan extends Model
{
    use HasFactory;

    protected $table = 'laporan_dasar_pelaksanaans';

    protected $fillable = [
        'laporan_harian_id',
        'deskripsi',
        'bukti_dukung_id',
        'is_terlampir',
        'urutan',
    ];

    public function laporanHarian()
    {
        return $this->belongsTo(LaporanHarian::class);
    }

    public function buktiDukung()
    {
        return $this->belongsTo(BuktiDukung::class);
    }

    public function getFormattedDeskripsiAttribute()
    {
        return $this->deskripsi . ($this->is_terlampir ? ' (terlampir)' : '');
    }
}