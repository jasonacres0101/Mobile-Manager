<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\GocardlessMandate;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\GoCardlessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AutoCollectPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_collects_eligible_invoice(): void
    {
        $company = Company::create([
            'name' => 'Demo Telecom Customer Ltd',
            'connectwise_company_id' => 900001,
            'auto_collect_enabled' => true,
            'auto_collect_days_before_due' => 3,
            'auto_collect_min_balance' => 1,
            'auto_collect_max_amount' => 100,
        ]);
        GocardlessMandate::create([
            'company_id' => $company->id,
            'mandate_id' => 'MD123',
            'status' => 'active',
        ]);
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'connectwise_invoice_id' => 600001,
            'invoice_number' => 'INV-DEMO-1001',
            'due_date' => now()->addDays(2)->toDateString(),
            'total' => 34.50,
            'balance' => 34.50,
            'status' => 'Open',
        ]);

        $mock = Mockery::mock(GoCardlessService::class);
        $mock->shouldReceive('createPaymentForInvoice')
            ->once()
            ->with(Mockery::on(fn ($actual) => $actual->is($invoice)))
            ->andReturn(new Payment);
        $this->app->instance(GoCardlessService::class, $mock);

        $this->artisan('payments:collect-due-gocardless')->assertSuccessful();
    }

    public function test_command_skips_invoice_above_company_cap(): void
    {
        $company = Company::create([
            'name' => 'Demo Telecom Customer Ltd',
            'connectwise_company_id' => 900001,
            'auto_collect_enabled' => true,
            'auto_collect_days_before_due' => 3,
            'auto_collect_min_balance' => 1,
            'auto_collect_max_amount' => 10,
        ]);
        GocardlessMandate::create([
            'company_id' => $company->id,
            'mandate_id' => 'MD123',
            'status' => 'active',
        ]);
        Invoice::create([
            'company_id' => $company->id,
            'connectwise_invoice_id' => 600001,
            'invoice_number' => 'INV-DEMO-1001',
            'due_date' => now()->addDays(2)->toDateString(),
            'total' => 34.50,
            'balance' => 34.50,
            'status' => 'Open',
        ]);

        $mock = Mockery::mock(GoCardlessService::class);
        $mock->shouldNotReceive('createPaymentForInvoice');
        $this->app->instance(GoCardlessService::class, $mock);

        $this->artisan('payments:collect-due-gocardless')->assertSuccessful();
    }
}
