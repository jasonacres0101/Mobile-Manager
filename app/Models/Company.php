<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Company extends Model
{
    protected $fillable = [
        'name',
        'connectwise_company_id',
        'mobilemanager_customer_id',
        'gocardless_customer_id',
        'gocardless_billing_request_id',
        'auto_collect_enabled',
        'auto_collect_days_before_due',
        'auto_collect_min_balance',
        'auto_collect_max_amount',
    ];

    protected function casts(): array
    {
        return [
            'auto_collect_enabled' => 'boolean',
            'auto_collect_min_balance' => 'decimal:2',
            'auto_collect_max_amount' => 'decimal:2',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function agreements()
    {
        return $this->hasMany(Agreement::class);
    }

    public function sims()
    {
        return $this->hasMany(Sim::class);
    }

    public function fibreConnections()
    {
        return $this->hasMany(FibreConnection::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function mandates()
    {
        return $this->hasMany(GocardlessMandate::class);
    }

    public function currentMandate(): ?GocardlessMandate
    {
        return $this->mandates()
            ->orderByRaw("
                case status
                    when 'active' then 1
                    when 'submitted' then 2
                    when 'pending_submission' then 3
                    when 'created' then 4
                    else 5
                end
            ")
            ->latest('updated_at')
            ->first();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function jolaCustomer()
    {
        return $this->hasOne(JolaCustomer::class);
    }
}
