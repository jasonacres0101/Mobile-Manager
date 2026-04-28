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
        Schema::table('sims', function (Blueprint $table) {
            $table->string('mobilemanager_sim_id')->nullable()->unique()->after('connectwise_addition_id');
            $table->string('mobilemanager_customer_id')->nullable()->index()->after('mobilemanager_sim_id');
            $table->string('iccid')->nullable()->index()->after('mobilemanager_customer_id');
            $table->string('msisdn')->nullable()->index()->after('iccid');
            $table->json('raw_data')->nullable()->after('status');
            $table->timestamp('last_synced_at')->nullable()->after('raw_data');
        });

        Schema::table('sims', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sims', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();

            $table->dropColumn([
                'mobilemanager_sim_id',
                'mobilemanager_customer_id',
                'iccid',
                'msisdn',
                'raw_data',
                'last_synced_at',
            ]);
        });
    }
};
