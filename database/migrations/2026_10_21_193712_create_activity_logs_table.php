<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->string('actor_email', 254);
            $table->string('actor_role', 20);
            $table->string('area', 40);
            $table->string('action', 40);
            $table->string('route_name', 120);
            $table->json('targets')->nullable();
            $table->timestampTz('occurred_at');
            $table->index(['area', 'occurred_at']);
            $table->index(['actor_id', 'occurred_at']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
