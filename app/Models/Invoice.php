<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'company_id',
        'agreement_id',
        'connectwise_invoice_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'total',
        'balance',
        'status',
        'gocardless_payment_id',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'total' => 'decimal:2',
            'balance' => 'decimal:2',
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

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
