#!/bin/bash
echo "🔧 Fixing User.php syntax"

cd ~/api-backend

cat > app/Models/User.php << 'PHP'
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
PHP

echo "✅ User.php fixed"
echo "Now test with new email!"
