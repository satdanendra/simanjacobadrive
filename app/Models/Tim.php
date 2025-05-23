<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tim extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tims';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'kode_tim',
        'nama_tim',
    ];

    /**
     * Get the proyeks for the tim.
     */
    public function proyeks()
    {
        return $this->hasMany(Proyek::class);
    }
}
