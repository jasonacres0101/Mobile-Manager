<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\GocardlessMandate;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sim;
use App\Models\User;
use App\Services\GoCardlessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_their_portal_pages(): void
    {
        $company = Company::create([
            'name' => 'Test Customer Ltd',
            'connectwise_company_id' => 900001,
        ]);
        $otherCompany = Company::create([
            'name' => 'Other Customer Ltd',
            'connectwise_company_id' => 900002,
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'customer',
        ]);
        Sim::create([
            'company_id' => $company->id,
            'connectwise_addition_id' => 800001,
            'mobile_number' => '883190602012644',
            'iccid' => '89444610709900073604',
            'network' => 'RoamNet 901',
            'tariff' => '20GB M2M',
            'monthly_cost' => 12.50,
            'status' => 'Active',
        ]);
        Sim::create([
            'company_id' => $otherCompany->id,
            'connectwise_addition_id' => 800002,
            'mobile_number' => '447700900999',
            'monthly_cost' => 20,
            'status' => 'Active',
        ]);
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'connectwise_invoice_id' => 600001,
            'invoice_number' => 'MS-1011100',
            'due_date' => now()->toDateString(),
            'total' => 34.25,
            'balance' => 34.25,
            'status' => 'New',
            'payment_status' => 'pending_submission',
            'gocardless_payment_id' => 'PM123',
        ]);
        Payment::create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'gocardless_payment_id' => 'PM123',
            'amount' => 34.25,
            'status' => 'pending_submission',
            'charge_date' => now()->addDays(5)->toDateString(),
        ]);
        GocardlessMandate::create([
            'company_id' => $company->id,
            'mandate_id' => 'MD123',
            'status' => 'pending_submission',
        ]);

        $this->actingAs($user)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Test Customer Ltd')
            ->assertSee('MS-1011100')
            ->assertSee('883190602012644')
            ->assertDontSee('Other Customer Ltd');

        $this->actingAs($user)
            ->get(route('customer.sims.index'))
            ->assertOk()
            ->assertSee('89444610709900073604')
            ->assertDontSee('447700900999');

        $this->actingAs($user)
            ->get(route('customer.invoices.index'))
            ->assertOk()
            ->assertSee('MS-1011100')
            ->assertSee('PM123');

        $this->actingAs($user)
            ->get(route('customer.direct-debit.setup'))
            ->assertOk()
            ->assertSee('MD123')
            ->assertSee('PM123');
    }

    public function test_direct_debit_callback_stores_customer_and_mandate_details(): void
    {
        $company = Company::create([
            'name' => 'Test Customer Ltd',
            'connectwise_company_id' => 900001,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'customer',
        ]);

        $mock = Mockery::mock(GoCardlessService::class);
        $mock->shouldReceive('billingRequestSummary')
            ->once()
            ->with('BRQ123')
            ->andReturn([
                'mandate_id' => 'MD999',
                'customer_id' => 'CU999',
            ]);

        $this->app->instance(GoCardlessService::class, $mock);

        $this->actingAs($user)
            ->withSession(['gocardless_billing_request_id' => 'BRQ123'])
            ->get(route('customer.direct-debit.callback'))
            ->assertRedirect(route('customer.direct-debit.setup'));

        $this->assertSame('CU999', $company->fresh()->gocardless_customer_id);
        $this->assertDatabaseHas('gocardless_mandates', [
            'company_id' => $company->id,
            'mandate_id' => 'MD999',
            'status' => 'created',
        ]);
    }
}
