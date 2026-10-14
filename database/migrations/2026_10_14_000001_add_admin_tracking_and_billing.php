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
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_active')->default(true);
        });
        Schema::table('point_entries', function (Blueprint $table) {
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 64)->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('delivery_days')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
        Schema::create('tracking_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100)->unique();
            $table->string('color', 7)->default('#5294ff');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_final')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
        Schema::create('member_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('tracking_status_id')->constrained()->restrictOnDelete();
            $table->string('reference', 64)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->timestampTz('closed_at')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'tracking_status_id']);
        });
        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_tracking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status_label', 100);
            $table->string('status_color', 7);
            $table->text('note')->nullable();
            $table->timestampsTz();
        });
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('member_tracking_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('transaction_id')->nullable()->unique()->constrained('membership_transactions')->restrictOnDelete();
            $table->string('reference', 64)->unique();
            $table->string('title');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('total');
            $table->enum('status', ['unpaid', 'paid', 'cancelled'])->default('unpaid');
            $table->date('due_date')->nullable();
            $table->string('payment', 100)->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['user_id', 'status']);
        });
        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value');
            $table->timestampsTz();
        });
        DB::table('site_settings')->insert([
            ['key' => 'brand_name', 'value' => 'Coignitive', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'support_email', 'value' => '', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'rupiah_per_point', 'value' => '1000', 'created_at' => now(), 'updated_at' => now()],
        ]);
        foreach ([['Dalam antrean', '#5294ff', false], ['Sedang diproses', '#facc00', false], ['Menunggu konfirmasi', '#7a83ff', false], ['Selesai', '#05e17a', true], ['Dibatalkan', '#ff4d50', true]] as $order => [$label, $color, $final]) {
            DB::table('tracking_statuses')->insert(['label' => $label, 'color' => $color, 'sort_order' => $order, 'is_final' => $final, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE bills ADD CONSTRAINT bill_amounts_valid CHECK (quantity > 0 AND unit_price > 0 AND discount >= 0 AND total > 0 AND total = quantity * unit_price - discount)');
            DB::statement('ALTER TABLE services ADD CONSTRAINT service_amounts_valid CHECK (price >= 0 AND delivery_days >= 0)');
        }
    }

    public function down(): void
    {
        foreach (['site_settings', 'bills', 'tracking_events', 'member_trackings', 'tracking_statuses', 'services'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('point_entries', fn (Blueprint $table) => $table->dropConstrainedForeignId('actor_id'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['is_admin', 'is_active']));
    }
};
