<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuktiDukung extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bukti_dukungs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'proyek_id',
        'nama_file',
        'file_path',
        'drive_id',
        'file_type',
        'extension',
        'mime_type',
        'keterangan',
    ];

    /**
     * Get the proyek that owns the bukti dukung.
     */
    public function proyek()
    {
        return $this->belongsTo(Proyek::class);
    }

    /**
     * Cek apakah file adalah gambar.
     *
     * @return bool
     */
    public function isImage()
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
        return in_array(strtolower($this->extension), $imageExtensions);
    }

    /**
     * Cek apakah file adalah dokumen.
     *
     * @return bool
     */
    public function isDocument()
    {
        $documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
        return in_array(strtolower($this->extension), $documentExtensions);
    }
}