<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Holiday;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        Holiday::insert([
            // Libur Nasional Tetap (Fix)

            [
                'date' => '2026-01-01',
                'name' => 'Tahun Baru Masehi'
            ],
            [
                'date' => '2026-05-01',
                'name' => 'Hari Buruh Internasional'
            ],
            [
                'date' => '2026-06-01',
                'name' => 'Hari Lahir Pancasila'
            ],
            [
                'date' => '2026-08-17',
                'name' => 'Hari Kemerdekaan Republik Indonesia'
            ],
            [
                'date' => '2026-12-25',
                'name' => 'Hari Raya Natal'
            ],

            // Libur Besar Keagamaan (tanggal bisa berubah)
            // ⚠️ Admin bisa update jika pemerintah sudah resmi

            [
                'date' => '2026-03-20',
                'name' => 'Hari Raya Nyepi (perkiraan)'
            ],
            [
                'date' => '2026-04-10',
                'name' => 'Wafat Isa Almasih (perkiraan)'
            ],
            [
                'date' => '2026-04-22',
                'name' => 'Idul Fitri (perkiraan)'
            ],
            [
                'date' => '2026-06-17',
                'name' => 'Idul Adha (perkiraan)'
            ],
            [
                'date' => '2026-07-07',
                'name' => 'Tahun Baru Islam (perkiraan)'
            ],
            [
                'date' => '2026-09-26',
                'name' => 'Maulid Nabi Muhammad SAW (perkiraan)'
            ],
        ]);
    }
}
