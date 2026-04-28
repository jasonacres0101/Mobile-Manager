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
        Schema::create('mobile_networks', function (Blueprint $table) {
            $table->id();
            $table->string('mcc', 3);
            $table->string('mnc', 3);
            $table->string('plmn', 6)->unique();
            $table->string('name');
            $table->string('country_code', 2)->nullable();
            $table->string('country')->nullable();
            $table->string('tadig', 8)->nullable()->index();
            $table->timestamps();
        });

        DB::table('mobile_networks')->insert([
            ['mcc' => '234', 'mnc' => '02', 'plmn' => '23402', 'name' => 'O2 UK', 'country_code' => 'GB', 'country' => 'United Kingdom', 'tadig' => null, 'created_at' => now(), 'updated_at' => now()],
            ['mcc' => '234', 'mnc' => '10', 'plmn' => '23410', 'name' => 'O2 UK', 'country_code' => 'GB', 'country' => 'United Kingdom', 'tadig' => 'GBRCN', 'created_at' => now(), 'updated_at' => now()],
            ['mcc' => '234', 'mnc' => '11', 'plmn' => '23411', 'name' => 'O2 UK', 'country_code' => 'GB', 'country' => 'United Kingdom', 'tadig' => null, 'created_at' => now(), 'updated_at' => now()],
            ['mcc' => '234', 'mnc' => '15', 'plmn' => '23415', 'name' => 'Vodafone UK', 'country_code' => 'GB', 'country' => 'United Kingdom', 'tadig' => 'GBRVF', 'created_at' => now(), 'updated_at' => now()],
            ['mcc' => '234', 'mnc' => '20', 'plmn' => '23420', 'name' => 'Three UK', 'country_code' => 'GB', 'country' => 'United Kingdom', 'tadig' => null, 'created_at' => now(), 'updated_at' => now()],
            ['mcc' => '234', 'mnc' => '30', 'plmn' => '23430', 'name' => 'EE UK', 'country_code' => 'GB', 'country' => 'United Kingdom', 'tadig' => null, 'created_at' => now(), 'updated_at' => now()],
            ['mcc' => '234', 'mnc' => '31', 'plmn' => '23431', 'name' => 'EE UK', 'country_code' => 'GB', 'country' => 'United Kingdom', 'tadig' => null, 'created_at' => now(), 'updated_at' => now()],
            ['mcc' => '234', 'mnc' => '32', 'plmn' => '23432', 'name' => 'EE UK', 'country_code' => 'GB', 'country' => 'United Kingdom', 'tadig' => null, 'created_at' => now(), 'updated_at' => now()],
            ['mcc' => '234', 'mnc' => '33', 'plmn' => '23433', 'name' => 'EE UK', 'country_code' => 'GB', 'country' => 'United Kingdom', 'tadig' => null, 'created_at' => now(), 'updated_at' => now()],
            ['mcc' => '235', 'mnc' => '91', 'plmn' => '23591', 'name' => 'Vodafone UK', 'country_code' => 'GB', 'country' => 'United Kingdom', 'tadig' => null, 'created_at' => now(), 'updated_at' => now()],
            ['mcc' => '235', 'mnc' => '92', 'plmn' => '23592', 'name' => 'Vodafone UK', 'country_code' => 'GB', 'country' => 'United Kingdom', 'tadig' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_networks');
    }
};
