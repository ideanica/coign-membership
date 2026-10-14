<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('reviewer_name', 80);
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->boolean('is_visible')->default(false);
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('moderated_at')->nullable();
            $table->timestampsTz();
            $table->unique(['catalog_id', 'user_id']);
            $table->index(['catalog_id', 'is_visible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
