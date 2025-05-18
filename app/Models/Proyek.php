<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proyek extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'proyeks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tim_id',
        'kode_proyek',
        'nama_proyek',
    ];

    /**
     * Get the tim that owns the proyek.
     */
    public function tim()
    {
        return $this->belongsTo(Tim::class);
    }

    /**
     * Get the bukti dukungs for the proyek.
     */
    public function buktiDukungs()
    {
        return $this->hasMany(BuktiDukung::class);
    }
}