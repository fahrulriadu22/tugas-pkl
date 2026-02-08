<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            // relasi ke user
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // tanggal absensi
            $table->date('date');

            // jam masuk & pulang
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();

            $table->timestamps();

            // supaya tidak bisa absen masuk 2x sehari
            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
