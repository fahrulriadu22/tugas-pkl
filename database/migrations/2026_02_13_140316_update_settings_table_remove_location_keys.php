<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        // Hapus setting lokasi kantor dari tabel settings
        DB::table('settings')
            ->whereIn('key', [
                'office_latitude',
                'office_longitude'
                
            ])
            ->delete();
    }

    public function down(): void
    {
        // Kalau rollback, bisa dikembalikan manual kalau perlu
    }
};
