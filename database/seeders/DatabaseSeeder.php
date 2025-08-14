<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AppSetting;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seed default app settings
        if (AppSetting::count() === 0) {
            AppSetting::create([
                'android_min_version' => '1.0.0',
                'ios_min_version' => '1.0.0',
                'is_app_working' => true,
                'down_message' => null,
                'android_store_url' => 'https://play.google.com/store/apps/details?id=com.example.app',
                'ios_store_url' => 'https://apps.apple.com/app/id1234567890',
                'extra' => json_encode([]),
            ]);
        }
    }
}
