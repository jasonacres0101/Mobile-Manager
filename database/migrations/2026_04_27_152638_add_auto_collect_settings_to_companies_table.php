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
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('auto_collect_enabled')->default(false)->after('gocardless_customer_id');
            $table->integer('auto_collect_days_before_due')->default(3)->after('auto_collect_enabled');
            $table->decimal('auto_collect_min_balance', 10, 2)->default(0)->after('auto_collect_days_before_due');
            $table->decimal('auto_collect_max_amount', 10, 2)->nullable()->after('auto_collect_min_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'auto_collect_enabled',
                'auto_collect_days_before_due',
                'auto_collect_min_balance',
                'auto_collect_max_amount',
            ]);
        });
    }
};
