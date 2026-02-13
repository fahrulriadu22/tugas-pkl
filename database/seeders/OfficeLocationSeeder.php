<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OfficeLocation;

class OfficeLocationSeeder extends Seeder
{
    public function run(): void
    {
        OfficeLocation::create([
            'name' => 'ION Network Branch Yogyakarta',
            'latitude' => -7.7762992,
            'longitude' => 110.4100740,
            'radius_meter' => 50
        ]);
    }
}
