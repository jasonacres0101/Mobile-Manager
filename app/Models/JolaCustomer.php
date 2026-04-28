<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JolaCustomer extends Model
{
    protected $fillable = [
        'company_id',
        'mobilemanager_customer_id',
        'name',
        'account_number',
        'email',
        'phone',
        'status',
        'raw_data',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
