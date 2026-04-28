<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'company_id',
        'invoice_id',
        'gocardless_payment_id',
        'amount',
        'currency',
        'status',
        'charge_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'charge_date' => 'date',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
