<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_number')->nullable();
            $table->string('service_name')->nullable();
            $table->string('issuer_name')->nullable();
            $table->string('issuer_email')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropConstrainedForeignId('issued_by');
            $table->dropColumn(['customer_name', 'customer_email', 'customer_number', 'service_name', 'issuer_name', 'issuer_email']);
        });
    }
};
