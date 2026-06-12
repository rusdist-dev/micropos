<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\AppSettings;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (AppSettings::DEFAULTS as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        app(AppSettings::class)->flush();
    }
}
