<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('point_rules', function (Blueprint $table): void {
            $table->unsignedBigInteger('maximum_amount')->nullable();
            $table->string('reward_type', 20)->default('fixed');
            $table->unsignedBigInteger('step_amount')->nullable();
            $table->unsignedInteger('maximum_points')->nullable();
        });
        $activeRules = DB::table('point_rules')->where('is_active', true)->orderBy('minimum_amount')->get();
        foreach ($activeRules as $index => $rule) {
            if (isset($activeRules[$index + 1])) {
                DB::table('point_rules')->where('id', $rule->id)->update(['maximum_amount' => $activeRules[$index + 1]->minimum_amount - 1]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('point_rules', function (Blueprint $table): void {
            $table->dropColumn(['maximum_amount', 'reward_type', 'step_amount', 'maximum_points']);
        });
    }
};
