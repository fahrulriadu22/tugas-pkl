<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'key'   => 'work_start_time',
                'value' => '09:00'
            ],
            [
                'key'   => 'late_limit_time',
                'value' => '09:10'
            ],
            [
                'key'   => 'checkin_close_time',
                'value' => '18:00'
            ],
            [
                'key'   => 'work_end_time',
                'value' => '18:00'
            ],
        ];

        foreach ($data as $item) {
            Setting::updateOrCreate(
                ['key' => $item['key']],
                ['value' => $item['value']]
            );
        }
    }
}
