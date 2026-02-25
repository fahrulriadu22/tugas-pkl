<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;


    protected $fillable = [
        'name',
        'email',
        'password',
        'role',

        // foto profile biasa
        'photo',

        // wajah referensi untuk absensi
        'face_photo',
        'face_verified',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',

            // Laravel auto hash password
            'password' => 'hashed',

            // boolean untuk face verification
            'face_verified' => 'boolean',
        ];
    }


    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }


    public function getPhotoUrlAttribute()
    {
        return $this->photo
            ? asset('storage/' . $this->photo)
            : null;
    }

    public function getFacePhotoUrlAttribute()
    {
        return $this->face_photo
            ? asset('storage/' . $this->face_photo)
            : null;
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
