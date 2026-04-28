<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'company_id',
        'agreement_id',
        'source_key',
        'connectwise_invoice_line_id',
        'connectwise_addition_id',
        'description',
        'service_type',
        'quantity',
        'unit_price',
        'line_total',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'raw_data' => 'array',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
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
