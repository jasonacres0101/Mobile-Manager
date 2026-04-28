<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->string('service_type')->default('sim')->after('connectwise_agreement_type_id');
        });

        DB::table('agreements')->update(['service_type' => 'sim']);

        Schema::create('fibre_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agreement_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('connectwise_addition_id')->nullable()->unique();
            $table->string('service_identifier')->nullable();
            $table->string('circuit_reference')->nullable();
            $table->string('access_type')->nullable();
            $table->string('bandwidth')->nullable();
            $table->string('location_address')->nullable();
            $table->decimal('monthly_cost', 10, 2)->default(0);
            $table->string('status')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fibre_connections');

        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn('service_type');
        });
    }
};
