<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sim extends Model
{
    protected $fillable = [
        'company_id',
        'agreement_id',
        'connectwise_addition_id',
        'mobilemanager_sim_id',
        'mobilemanager_customer_id',
        'iccid',
        'msisdn',
        'mobile_number',
        'sim_number',
        'network',
        'tariff',
        'monthly_cost',
        'status',
        'raw_data',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'monthly_cost' => 'decimal:2',
            'raw_data' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function agreement()
    {
        return $this->belongsTo(Agreement::class);
    }
}
