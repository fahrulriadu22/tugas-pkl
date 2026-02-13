<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        // Hapus setting lokasi kantor dari tabel settings
        DB::table('settings')
            ->whereIn('key', [
                'office_lat',
                'office_long',
                'office_radius'
            ])
            ->delete();
    }

    public function down(): void
    {
        // Kalau rollback, bisa dikembalikan manual kalau perlu
    }
};
