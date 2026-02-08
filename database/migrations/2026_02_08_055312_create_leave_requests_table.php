<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->enum('type', ['izin', 'sakit', 'cuti']);
            $table->date('date');

            $table->text('reason')->nullable();

            $table->string('attachment')->nullable(); // surat dokter opsional

            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
