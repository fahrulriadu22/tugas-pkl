<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('office_locations', function (Blueprint $table) {
            $table->id();

            // Nama lokasi kantor
            $table->string('name');

            // Titik koordinat kantor
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Radius absensi (meter)
            $table->integer('radius_meter')->default(50);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_locations');
    }
};
