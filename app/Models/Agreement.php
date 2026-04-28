<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agreement extends Model
{
    protected $fillable = [
        'company_id',
        'connectwise_agreement_id',
        'connectwise_agreement_type_id',
        'name',
        'status',
        'start_date',
        'end_date',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'last_synced_at' => 'datetime',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function sims()
    {
        return $this->hasMany(Sim::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
