<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius_meter',
    ];

    /**
     * Relasi: satu kantor punya banyak attendance
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
