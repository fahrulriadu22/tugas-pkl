<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('face_id')->nullable();
            $table->boolean('face_verified')->default(false);

            $table->string('ktp_photo')->nullable();
            $table->string('selfie_photo')->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'face_id',
                'face_verified',
                'ktp_photo',
                'selfie_photo'
            ]);
        });
    }

};
