<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {

            // ✅ hapus kolom lama "photo"
            if (Schema::hasColumn('attendances', 'photo')) {
                $table->dropColumn('photo');
            }

            // ✅ tambah kolom baru
            $table->string('checkin_photo')->nullable()->after('longitude');
            $table->string('checkout_photo')->nullable()->after('checkin_photo');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {

            $table->dropColumn(['checkin_photo', 'checkout_photo']);

            // balikin kolom lama
            $table->string('photo')->nullable();
        });
    }
};
