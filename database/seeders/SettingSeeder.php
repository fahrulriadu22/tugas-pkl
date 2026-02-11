<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Setting::updateOrCreate(
            ['key' => 'office_latitude'],
            ['value' => '-7.7762992']
        );

        Setting::updateOrCreate(
            ['key' => 'office_longitude'],
            ['value' => '110.4100740']
        );

        Setting::updateOrCreate(
            ['key' => 'office_radius'],
            ['value' => '50']
        );
    }
}
