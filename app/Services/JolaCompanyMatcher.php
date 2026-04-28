<?php

namespace App\Services;

use App\Models\Company;
use App\Models\JolaCustomer;
use Illuminate\Support\Str;

class JolaCompanyMatcher
{
    public function matchCustomer(string $mobileManagerCustomerId, ?string $name = null): ?Company
    {
        $company = Company::where('mobilemanager_customer_id', $mobileManagerCustomerId)->first();

        if (! $company && $name) {
            $normalizedName = $this->normalizeName($name);

            $company = Company::all()->first(
                fn (Company $company) => $this->normalizeName($company->name) === $normalizedName
            );
        }

        if ($company && $company->mobilemanager_customer_id !== $mobileManagerCustomerId) {
            $company->update(['mobilemanager_customer_id' => $mobileManagerCustomerId]);
        }

        return $company;
    }

    public function matchExistingCustomers(): int
    {
        $matched = 0;

        JolaCustomer::query()
            ->whereNull('company_id')
            ->orWhereDoesntHave('company')
            ->get()
            ->each(function (JolaCustomer $jolaCustomer) use (&$matched) {
                $company = $this->matchCustomer($jolaCustomer->mobilemanager_customer_id, $jolaCustomer->name);

                if ($company) {
                    $jolaCustomer->update(['company_id' => $company->id]);
                    $matched++;
                }
            });

        return $matched;
    }

    private function normalizeName(string $name): string
    {
        return Str::of($name)
            ->lower()
            ->replaceMatches('/\b(limited|ltd|plc|llp|uk|the)\b/', '')
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();
    }
}
