<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JolaProduct extends Model
{
    protected $fillable = [
        'mobilemanager_product_id',
        'name',
        'network',
        'type',
        'allowance',
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
}
