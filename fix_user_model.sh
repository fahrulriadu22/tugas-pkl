#!/bin/bash
echo "🔧 Fixing User Model for Sanctum"

cd ~/api-backend

echo "Updating User model..."
cat > app/Models/User.php << 'MODEL'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected \$fillable = [
        'name',
        'email',
        'password',
    ];

    protected \$hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
MODEL

echo "✅ User model updated with HasApiTokens trait"
echo ""
echo "Now test API again!"
