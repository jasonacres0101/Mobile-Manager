@php
    $field = function (array $keys, $fallback = '-') use ($customer, $liveCustomer) {
        foreach ($keys as $key) {
            $value = data_get($liveCustomer, $key, data_get($customer->raw_data, $key));

            if ($value !== null && $value !== '') {
                if (is_bool($value)) {
                    return $value ? 'Active' : 'Inactive';
                }

                return is_scalar($value) ? (string) $value : json_encode($value);
            }
        }

        return $fallback;
    };

    $simField = function (array $sim, array $keys, $fallback = '-') {
        foreach ($keys as $key) {
            $value = data_get($sim, $key);

            if ($value !== null && $value !== '') {
                if (is_bool($value)) {
                    return $value ? 'Yes' : 'No';
                }

                return is_scalar($value) ? (string) $value : json_encode($value);
            }
        }

        return $fallback;
    };

    $displayName = $customer->name ?? $field(['Name', 'name', 'CompanyName', 'companyName'], 'Unknown Jola customer');
    $status = $customer->status ?? $field(['Status.Name', 'status.name', 'Status', 'status', 'Active', 'active']);
    $activeSims = $customerSims->filter(function ($sim) use ($simField) {
        return str_contains(strtolower($simField($sim, ['Status.Name', 'status.name', 'Status', 'status'])), 'active');
    })->count();
    $networks = $customerSims
        ->map(fn ($sim) => $simField($sim, ['Operator', 'operator', 'Network.Name', 'network.name', 'Network', 'network'], null))
        ->filter()
        ->unique()
        ->values();
    $tariffs = $customerSims
        ->map(fn ($sim) => $simField($sim, ['Tariff.Name', 'tariff.name', 'Tariff', 'tariff'], null))
        ->filter()
        ->unique()
        ->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Jola Customer</h2>
            <a href="{{ route('admin.jola-customers.index') }}" class="text-sm text-indigo-600 dark:text-indigo-300 hover:underline">Back to Jola customers</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($error)
                <div class="text-sm text-amber-700 dark:text-amber-300">{{ $error }}</div>
            @endif

            <section class="overflow-hidden rounded-lg border border-[#020f40]/10 bg-[#020f40] shadow-sm dark:border-white/10">
                <div class="relative px-6 py-7">
                    <div class="absolute inset-x-0 bottom-0 h-1 bg-[#FFA500]"></div>
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-md bg-white p-3 shadow-sm">
                                <img src="{{ asset('images/micronet-logo.svg') }}" alt="Micronet" class="max-h-11 w-auto">
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-[#FFA500]">Read-only Jola customer</div>
                                <h3 class="mt-2 text-2xl font-semibold text-white">{{ $displayName }}</h3>
                                <p class="mt-1 text-sm text-slate-200">{{ $customer->mobilemanager_customer_id }}</p>
                            </div>
                        </div>
                    <div class="flex flex-wrap gap-2 text-sm">
                        @if ($customer->company)
                                <a href="{{ route('admin.companies.show', $customer->company) }}" class="rounded-md bg-[#FFA500] px-3 py-2 font-medium text-[#020f40] hover:bg-[#ffb52e]">Portal: {{ $customer->company->name }}</a>
                        @endif
                            <span class="rounded-md bg-white/10 px-3 py-2 font-medium text-white">{{ $status }}</span>
                            <span class="rounded-md bg-white/10 px-3 py-2 font-medium text-white">Synced {{ $customer->last_synced_at?->format('d M Y H:i') ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-[#020f40]/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total SIMs</div>
                    <div class="mt-2 text-3xl font-semibold text-[#020f40] dark:text-gray-100">{{ $customerSims->count() }}</div>
                </div>
                <div class="rounded-lg border border-cyan-100 bg-cyan-50 p-5 shadow-sm dark:border-cyan-900/60 dark:bg-cyan-950/20">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Active SIMs</div>
                    <div class="mt-2 text-3xl font-semibold text-[#020f40] dark:text-cyan-100">{{ $activeSims }}</div>
                </div>
                <div class="rounded-lg border border-[#020f40]/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Networks</div>
                    <div class="mt-2 text-3xl font-semibold text-[#020f40] dark:text-gray-100">{{ $networks->count() }}</div>
                </div>
                <div class="rounded-lg border border-orange-100 bg-orange-50 p-5 shadow-sm dark:border-orange-900/60 dark:bg-orange-950/20">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Tariffs</div>
                    <div class="mt-2 text-3xl font-semibold text-[#020f40] dark:text-orange-100">{{ $tariffs->count() }}</div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:col-span-2">
                    <h3 class="text-base font-semibold text-[#020f40] dark:text-gray-100">Customer summary</h3>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Account number</dt>
                            <dd class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ $customer->account_number ?? $field(['AccountNumber', 'accountNumber', 'Reference', 'reference']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Billing email</dt>
                            <dd class="mt-1 font-medium text-gray-900 dark:text-gray-100 break-words">{{ $customer->email ?? $field(['BillingEmail', 'billingEmail', 'Email', 'email', 'ContactEmail', 'contactEmail']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Phone</dt>
                            <dd class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ $customer->phone ?? $field(['Phone', 'phone', 'Telephone', 'telephone', 'Mobile', 'mobile']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Source</dt>
                            <dd class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ $liveCustomer ? 'Live + cached' : 'Cached only' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-lg border border-cyan-100 bg-cyan-50 p-6 shadow-sm dark:border-cyan-900/60 dark:bg-cyan-950/20">
                    <h3 class="text-base font-semibold text-[#020f40] dark:text-gray-100">SIM mix</h3>
                    <div class="mt-4 space-y-4 text-sm">
                        <div>
                            <div class="text-gray-500 dark:text-gray-400">Networks</div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @forelse ($networks as $network)
                                    <span class="rounded-md bg-white px-2 py-1 text-[#020f40] shadow-sm dark:bg-gray-900 dark:text-gray-300">{{ $network }}</span>
                                @empty
                                    <span class="text-gray-500 dark:text-gray-400">-</span>
                                @endforelse
                            </div>
                        </div>
                        <div>
                            <div class="text-gray-500 dark:text-gray-400">Tariffs</div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @forelse ($tariffs as $tariff)
                                    <span class="rounded-md bg-white px-2 py-1 text-[#020f40] shadow-sm dark:bg-gray-900 dark:text-gray-300">{{ $tariff }}</span>
                                @empty
                                    <span class="text-gray-500 dark:text-gray-400">-</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-[#020f40] dark:text-gray-100">SIMs</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-[#020f40]">
                            <tr>
                                <th class="px-4 py-3 text-left text-white">Mobile</th>
                                <th class="px-4 py-3 text-left text-white">ICCID</th>
                                <th class="px-4 py-3 text-left text-white">Network</th>
                                <th class="px-4 py-3 text-left text-white">Tariff</th>
                                <th class="px-4 py-3 text-left text-white">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($customerSims as $sim)
                                @php
                                    $simId = $simField($sim, ['Id', 'id', 'simId'], null);
                                    $mobile = $simField($sim, ['Msisdn', 'MSISDN', 'msisdn', 'MobileNumber', 'mobileNumber']);
                                @endphp
                                <tr class="hover:bg-cyan-50/60 dark:hover:bg-gray-900/50">
                                    <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                                        @if ($simId)
                                            <a href="{{ route('admin.jola-customers.sims.show', [$customer, $simId]) }}" class="font-medium text-[#020f40] hover:text-[#FFA500] hover:underline dark:text-cyan-200">{{ $mobile }}</a>
                                        @else
                                            {{ $mobile }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $simField($sim, ['Iccid', 'ICCID', 'iccid', 'IccId', 'iccId']) }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $simField($sim, ['Operator', 'operator', 'Network.Name', 'network.name', 'Network', 'network']) }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $simField($sim, ['Tariff.Name', 'tariff.name', 'Tariff', 'tariff']) }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $simField($sim, ['Status.Name', 'status.name', 'Status', 'status']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No live Jola SIMs found for this customer.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <details>
                    <summary class="cursor-pointer text-sm font-medium text-[#020f40] hover:text-[#FFA500] dark:text-cyan-200">Troubleshooting data</summary>
                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                        <pre class="max-h-[24rem] overflow-auto rounded bg-gray-50 dark:bg-gray-900 p-4 text-xs text-gray-700 dark:text-gray-300">{{ json_encode($customer->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        <pre class="max-h-[24rem] overflow-auto rounded bg-gray-50 dark:bg-gray-900 p-4 text-xs text-gray-700 dark:text-gray-300">{{ $liveCustomer ? json_encode($liveCustomer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : 'Not available' }}</pre>
                    </div>
                </details>
            </div>
        </div>
    </div>
</x-app-layout>
