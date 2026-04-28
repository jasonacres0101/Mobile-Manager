<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GocardlessMandate extends Model
{
    protected $fillable = [
        'company_id',
        'mandate_id',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
