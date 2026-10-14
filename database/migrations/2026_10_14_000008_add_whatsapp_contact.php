<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')->updateOrInsert(['key' => 'support_whatsapp'], ['value' => '+6289616366135', 'updated_at' => now()]);
        Cache::forget('membership.public-settings');
    }

    public function down(): void
    {
        DB::table('site_settings')->where('key', 'support_whatsapp')->where('value', '+6289616366135')->delete();
        Cache::forget('membership.public-settings');
    }
};
