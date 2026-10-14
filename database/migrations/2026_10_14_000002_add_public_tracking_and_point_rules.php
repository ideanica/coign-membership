<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_trackings', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('public_code', 24)->nullable()->unique();
            $table->boolean('public_enabled')->default(false);
        });
        DB::table('member_trackings')->orderBy('id')->each(function ($tracking): void {
            DB::table('member_trackings')->where('id', $tracking->id)->update(['public_code' => 'ORD-'.strtoupper(Str::random(16))]);
        });
        Schema::create('point_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->unsignedBigInteger('minimum_amount')->unique();
            $table->unsignedInteger('points');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
        DB::table('site_settings')->insert(['key' => 'points_mode', 'value' => 'ratio', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('point_rules');
        DB::table('site_settings')->where('key', 'points_mode')->delete();
        Schema::table('member_trackings', fn (Blueprint $table) => $table->dropColumn(['public_code', 'public_enabled']));
        // Guest orders remain nullable so rolling back never discards customer data.
    }
};
