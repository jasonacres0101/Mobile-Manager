<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Company;
use App\Models\GocardlessMandate;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCompanyDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_consolidated_company_detail_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::create([
            'name' => 'Demo Telecom Customer Ltd',
            'connectwise_company_id' => 900001,
            'gocardless_customer_id' => 'CU123',
        ]);
        $agreement = Agreement::create([
            'company_id' => $company->id,
            'connectwise_agreement_id' => 700001,
            'connectwise_agreement_type_id' => 12,
            'name' => 'Demo SIM Airtime Agreement',
            'status' => 'Active',
        ]);
        Sim::create([
            'company_id' => $company->id,
            'agreement_id' => $agreement->id,
            'connectwise_addition_id' => 800001,
            'mobilemanager_sim_id' => 'SIM-123',
            'mobile_number' => '447700900101',
            'iccid' => '8944111122223333444',
            'msisdn' => '447700900101',
            'network' => 'O2',
            'tariff' => '10GB Business SIM',
            'monthly_cost' => 12.50,
            'status' => 'Active',
        ]);
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'agreement_id' => $agreement->id,
            'connectwise_invoice_id' => 600001,
            'invoice_number' => 'INV-DEMO-1001',
            'total' => 34.50,
            'balance' => 34.50,
            'status' => 'Open',
        ]);
        Payment::create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'gocardless_payment_id' => 'PM123',
            'amount' => 34.50,
            'status' => 'pending_submission',
        ]);
        GocardlessMandate::create([
            'company_id' => $company->id,
            'mandate_id' => 'MD123',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.companies.show', $company))
            ->assertOk()
            ->assertSee('Demo Telecom Customer Ltd')
            ->assertSee('Demo SIM Airtime Agreement')
            ->assertSee('447700900101')
            ->assertSee('SIM-123')
            ->assertSee('INV-DEMO-1001')
            ->assertSee('PM123')
            ->assertSee('MD123');
    }

    public function test_admin_can_update_company_auto_collection_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::create([
            'name' => 'Demo Telecom Customer Ltd',
            'connectwise_company_id' => 900001,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.companies.auto-collect.update', $company), [
                'auto_collect_enabled' => '1',
                'auto_collect_days_before_due' => 5,
                'auto_collect_min_balance' => 10,
                'auto_collect_max_amount' => 250,
            ])
            ->assertRedirect(route('admin.companies.show', $company));

        $company->refresh();

        $this->assertTrue($company->auto_collect_enabled);
        $this->assertSame(5, $company->auto_collect_days_before_due);
        $this->assertSame('10.00', $company->auto_collect_min_balance);
        $this->assertSame('250.00', $company->auto_collect_max_amount);
    }
}
