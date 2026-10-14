<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['support_email' => 'coignitive@gmail.com', 'support_x' => '@coignitive'] as $key => $value) {
            DB::table('site_settings')->updateOrInsert(['key' => $key], ['value' => $value, 'updated_at' => now()]);
        }
        Cache::forget('membership.public-settings');
    }

    public function down(): void
    {
        DB::table('site_settings')->where('key', 'support_x')->where('value', '@coignitive')->delete();
        DB::table('site_settings')->where('key', 'support_email')->where('value', 'coignitive@gmail.com')->update(['value' => '']);
        Cache::forget('membership.public-settings');
    }
};
