<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestampsTz();
        });
        Schema::create('catalogs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->text('description');
            $table->string('preview_url', 2048)->nullable();
            $table->unsignedBigInteger('base_price');
            $table->unsignedInteger('estimated_days')->default(3);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();
        });
        Schema::create('catalog_category', function (Blueprint $table) {
            $table->foreignId('catalog_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_category_id')->constrained()->restrictOnDelete();
            $table->primary(['catalog_id', 'catalog_category_id']);
        });
        Schema::create('catalog_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->enum('type', ['percent', 'fixed']);
            $table->unsignedBigInteger('value');
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
        Schema::create('catalog_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_id')->constrained()->cascadeOnDelete();
            $table->string('external_url', 2048)->nullable();
            $table->string('disk', 50)->nullable();
            $table->string('path', 255)->nullable()->unique();
            $table->string('alt_text', 200)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestampsTz();
        });
        Schema::create('catalog_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_id')->constrained()->cascadeOnDelete();
            $table->string('label', 120);
            $table->string('value', 500);
            $table->unsignedInteger('sort_order')->default(0);
        });
        Schema::create('catalog_uploads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('path', 255)->unique();
            $table->string('disk', 50);
            $table->string('mime', 40);
            $table->unsignedInteger('bytes');
            $table->timestampTz('expires_at');
            $table->timestampTz('claimed_at')->nullable();
            $table->timestampsTz();
        });
        foreach (['Wedding' => 'wedding', 'Ulang Tahun' => 'ulang-tahun', 'Anniversary' => 'anniversary', 'Valentine' => 'valentine', 'Graduation' => 'graduation', 'Kartu Ucapan' => 'kartu-ucapan', 'Confession' => 'confession', 'Gift' => 'gift'] as $name => $slug) {
            DB::table('catalog_categories')->insert(['name' => $name, 'slug' => $slug, 'sort_order' => DB::table('catalog_categories')->count(), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (['catalog_uploads', 'catalog_specifications', 'catalog_images', 'catalog_discounts', 'catalog_category', 'catalogs', 'catalog_categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
