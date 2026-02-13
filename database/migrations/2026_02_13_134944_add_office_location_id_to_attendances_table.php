<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {

            $table->foreignId('office_location_id')
                ->nullable()
                ->after('user_id')
                ->constrained('office_locations')
                ->onDelete('set null');

        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {

            $table->dropForeign(['office_location_id']);
            $table->dropColumn('office_location_id');

        });
    }
};
