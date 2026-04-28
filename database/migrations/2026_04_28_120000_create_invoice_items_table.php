<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agreement_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_key')->unique();
            $table->unsignedBigInteger('connectwise_invoice_line_id')->nullable();
            $table->unsignedBigInteger('connectwise_addition_id')->nullable();
            $table->string('description')->nullable();
            $table->string('service_type')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('line_total', 10, 2)->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
