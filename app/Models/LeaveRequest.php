<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'date',
        'reason',
        'attachment',
        'status'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

