<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('member_number', 64)->nullable()->unique();
            $table->string('member_tier', 64)->default('Member');
            $table->string('photo_path')->nullable();
        });
        Schema::create('membership_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 64)->unique();
            $table->string('title');
            $table->string('category', 64);
            $table->unsignedBigInteger('amount');
            $table->string('payment', 100);
            $table->timestampTz('occurred_at');
            $table->timestampsTz();
            $table->index(['user_id', 'occurred_at']);
        });
        Schema::create('point_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->unique()->constrained('membership_transactions')->restrictOnDelete();
            $table->string('reference', 64)->unique();
            $table->enum('type', ['earned', 'redeemed']);
            $table->unsignedBigInteger('points');
            $table->string('title');
            $table->string('category', 64);
            $table->text('description')->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampsTz();
            $table->index(['user_id', 'type', 'occurred_at']);
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE membership_transactions ADD CONSTRAINT membership_amount_positive CHECK (amount > 0)');
            DB::statement('ALTER TABLE point_entries ADD CONSTRAINT membership_points_positive CHECK (points > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('point_entries');
        Schema::dropIfExists('membership_transactions');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['member_number', 'member_tier', 'photo_path']));
    }
};
