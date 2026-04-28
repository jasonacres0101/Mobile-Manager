@php
    $statusBadge = function (?string $status) {
        $status = $status ?: 'not requested';
        $key = strtolower(str_replace([' ', '_'], '-', $status));
        $classes = match ($key) {
            'confirmed', 'paid', 'paid-out', 'active' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
            'pending-submission', 'submitted', 'created', 'new', 'open' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
            'cancelled', 'failed', 'charged-back', 'inactive' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-300',
        };

        return '<span class="inline-flex rounded-md px-2 py-1 text-xs font-medium '.$classes.'">'.e(str_replace('_', ' ', $status)).'</span>';
    };

    $matchBadge = function ($sim) {
        if ($sim->connectwise_addition_id && $sim->mobilemanager_sim_id) {
            return '<span class="inline-flex rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-900/40 dark:text-green-200">Matched</span>';
        }

        if ($sim->connectwise_addition_id) {
            return '<span class="inline-flex rounded-md bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">PSA only</span>';
        }

        if ($sim->mobilemanager_sim_id) {
            return '<span class="inline-flex rounded-md bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-200">Jola only</span>';
        }

        return '<span class="inline-flex rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-900 dark:text-gray-300">Unmatched</span>';
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">{{ $company->name }}</h2>
            <a href="{{ route('admin.companies.index') }}" class="text-sm font-medium text-[#020f40] hover:text-[#FFA500] hover:underline dark:text-orange-200">Companies</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="text-sm text-green-700 dark:text-green-300">{{ session('status') }}</div>
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
                                <div class="text-xs font-semibold uppercase tracking-widest text-[#FFA500]">Company detail</div>
                                <h3 class="mt-2 text-2xl font-semibold text-white">{{ $company->name }}</h3>
                                <p class="mt-1 text-sm text-slate-200">Agreement, SIM, fibre, Jola, invoice, and GoCardless information in one place.</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.companies.index') }}" class="inline-flex items-center justify-center rounded-md bg-[#FFA500] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[#020f40] shadow-sm transition hover:bg-[#ffb52e] focus:outline-none focus:ring-2 focus:ring-[#FFA500] focus:ring-offset-2 focus:ring-offset-[#020f40]">Companies</a>
                    </div>
                </div>
            </section>

            <div class="grid gap-4 sm:grid-cols-6">
                <div class="rounded-lg border border-[#020f40]/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-800"><div class="text-sm text-gray-500">ConnectWise ID</div><div class="mt-2 text-lg font-semibold text-[#020f40] dark:text-gray-100">{{ $company->connectwise_company_id }}</div><div class="mt-3 h-1 rounded-full bg-orange-100 dark:bg-gray-700"><div class="h-1 w-1/2 rounded-full bg-[#FFA500]"></div></div></div>
                <div class="rounded-lg border border-cyan-100 bg-cyan-50 p-5 shadow-sm dark:border-cyan-900/60 dark:bg-cyan-950/20"><div class="text-sm text-cyan-900 dark:text-cyan-200">Jola Client</div><div class="mt-2 text-lg font-semibold text-[#020f40] dark:text-cyan-100">
                    @if ($company->jolaCustomer)
                        <a href="{{ route('admin.jola-customers.show', $company->jolaCustomer) }}" class="text-[#020f40] hover:text-[#FFA500] hover:underline dark:text-cyan-100">{{ $company->jolaCustomer->name ?? $company->mobilemanager_customer_id }}</a>
                    @else
                        {{ $company->mobilemanager_customer_id ?? '-' }}
                    @endif
                </div></div>
                <div class="rounded-lg border border-[#020f40]/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-800"><div class="text-sm text-gray-500">Agreements</div><div class="mt-2 text-lg font-semibold text-[#020f40] dark:text-gray-100">{{ $company->agreements->count() }}</div><div class="mt-3 h-1 rounded-full bg-orange-100 dark:bg-gray-700"><div class="h-1 w-2/3 rounded-full bg-[#FFA500]"></div></div></div>
                <div class="rounded-lg border border-[#020f40]/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-800"><div class="text-sm text-gray-500">SIMs</div><div class="mt-2 text-lg font-semibold text-[#020f40] dark:text-gray-100">{{ $company->sims->count() }}</div><div class="mt-3 h-1 rounded-full bg-cyan-100 dark:bg-gray-700"><div class="h-1 w-2/3 rounded-full bg-cyan-500"></div></div></div>
                <div class="rounded-lg border border-[#020f40]/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-800"><div class="text-sm text-gray-500">Fibre</div><div class="mt-2 text-lg font-semibold text-[#020f40] dark:text-gray-100">{{ $company->fibreConnections->count() }}</div><div class="mt-3 h-1 rounded-full bg-sky-100 dark:bg-gray-700"><div class="h-1 w-2/3 rounded-full bg-sky-500"></div></div></div>
                <div class="rounded-lg border border-orange-100 bg-orange-50 p-5 shadow-sm dark:border-orange-900/60 dark:bg-orange-950/20"><div class="text-sm text-orange-900 dark:text-orange-200">Balance</div><div class="mt-2 text-lg font-semibold text-[#020f40] dark:text-orange-100">£{{ number_format($company->invoices->sum('balance'), 2) }}</div></div>
            </div>

            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="h-3 w-3 rounded-full bg-[#FFA500]"></span>
                    <h3 class="text-base font-semibold text-[#020f40] dark:text-gray-100">Auto Collection</h3>
                </div>
                <form method="POST" action="{{ route('admin.companies.auto-collect.update', $company) }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 space-y-5">
                    @csrf
                    @method('PUT')

                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="auto_collect_enabled" value="1" @checked(old('auto_collect_enabled', $company->auto_collect_enabled)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        Enable automatic GoCardless collection
                    </label>

                    <div class="grid gap-5 md:grid-cols-3">
                        <div>
                            <x-input-label for="auto_collect_days_before_due" value="Days before due date" />
                            <x-text-input id="auto_collect_days_before_due" name="auto_collect_days_before_due" type="number" min="-30" max="30" class="mt-1 block w-full" value="{{ old('auto_collect_days_before_due', $company->auto_collect_days_before_due) }}" />
                            <x-input-error :messages="$errors->get('auto_collect_days_before_due')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="auto_collect_min_balance" value="Minimum balance" />
                            <x-text-input id="auto_collect_min_balance" name="auto_collect_min_balance" type="number" min="0" step="0.01" class="mt-1 block w-full" value="{{ old('auto_collect_min_balance', $company->auto_collect_min_balance) }}" />
                            <x-input-error :messages="$errors->get('auto_collect_min_balance')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="auto_collect_max_amount" value="Maximum amount" />
                            <x-text-input id="auto_collect_max_amount" name="auto_collect_max_amount" type="number" min="0" step="0.01" class="mt-1 block w-full" value="{{ old('auto_collect_max_amount', $company->auto_collect_max_amount) }}" placeholder="No cap" />
                            <x-input-error :messages="$errors->get('auto_collect_max_amount')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Requires an active Direct Debit mandate and skips invoices already linked to a payment.</div>
                        <x-primary-button>Save auto collection</x-primary-button>
                    </div>
                </form>
            </section>

            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="h-3 w-3 rounded-full bg-[#FFA500]"></span>
                    <h3 class="text-base font-semibold text-[#020f40] dark:text-gray-100">Agreement Details</h3>
                </div>
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-[#020f40]"><tr><th class="px-4 py-3 text-left text-white">Name</th><th class="px-4 py-3 text-left text-white">CW Agreement ID</th><th class="px-4 py-3 text-left text-white">Type ID</th><th class="px-4 py-3 text-left text-white">Status</th><th class="px-4 py-3 text-left text-white">Start</th><th class="px-4 py-3 text-left text-white">End</th><th class="px-4 py-3 text-left text-white">Synced</th></tr></thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($company->agreements as $agreement)
                            <tr class="hover:bg-orange-50/60 dark:hover:bg-gray-900/50"><td class="px-4 py-3 font-medium text-[#020f40] dark:text-gray-100">{{ $agreement->name }}</td><td class="px-4 py-3">{{ $agreement->connectwise_agreement_id }}</td><td class="px-4 py-3">{{ $agreement->connectwise_agreement_type_id }}</td><td class="px-4 py-3">{{ $agreement->status ?? '-' }}</td><td class="px-4 py-3">{{ $agreement->start_date?->format('d M Y') ?? '-' }}</td><td class="px-4 py-3">{{ $agreement->end_date?->format('d M Y') ?? '-' }}</td><td class="px-4 py-3">{{ $agreement->last_synced_at?->format('d M Y H:i') ?? '-' }}</td></tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-4 text-gray-500">No agreements found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </section>

            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="h-3 w-3 rounded-full bg-cyan-500"></span>
                    <h3 class="text-base font-semibold text-[#020f40] dark:text-gray-100">SIM Details</h3>
                </div>
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-[#020f40]"><tr><th class="px-4 py-3 text-left text-white">Mobile</th><th class="px-4 py-3 text-left text-white">SIM</th><th class="px-4 py-3 text-left text-white">ICCID</th><th class="px-4 py-3 text-left text-white">MSISDN</th><th class="px-4 py-3 text-left text-white">Network</th><th class="px-4 py-3 text-left text-white">Tariff</th><th class="px-4 py-3 text-left text-white">Match</th><th class="px-4 py-3 text-right text-white">Monthly</th><th class="px-4 py-3 text-left text-white">Status</th></tr></thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($company->sims as $sim)
                            <tr class="hover:bg-cyan-50/60 dark:hover:bg-gray-900/50"><td class="px-4 py-3 font-medium text-[#020f40] dark:text-gray-100">{{ $sim->mobile_number ?? '-' }}</td><td class="px-4 py-3">{{ $sim->sim_number ?? '-' }}</td><td class="px-4 py-3 font-mono text-xs">{{ $sim->iccid ?? '-' }}</td><td class="px-4 py-3">{{ $sim->msisdn ?? '-' }}</td><td class="px-4 py-3">{{ $sim->network ?? '-' }}</td><td class="px-4 py-3">{{ $sim->tariff ?? '-' }}</td><td class="px-4 py-3">{!! $matchBadge($sim) !!}<div class="mt-1 text-xs text-gray-500">CW {{ $sim->connectwise_addition_id ?? '-' }} · Jola {{ $sim->mobilemanager_sim_id ?? '-' }}</div></td><td class="px-4 py-3 text-right font-medium">£{{ number_format($sim->monthly_cost, 2) }}</td><td class="px-4 py-3">{{ $sim->status ?? '-' }}</td></tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-4 text-gray-500">No SIMs found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </section>

            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="h-3 w-3 rounded-full bg-sky-500"></span>
                    <h3 class="text-base font-semibold text-[#020f40] dark:text-gray-100">Fibre Details</h3>
                </div>
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-[#020f40]"><tr><th class="px-4 py-3 text-left text-white">Service</th><th class="px-4 py-3 text-left text-white">Circuit</th><th class="px-4 py-3 text-left text-white">Access</th><th class="px-4 py-3 text-left text-white">Bandwidth</th><th class="px-4 py-3 text-left text-white">Address</th><th class="px-4 py-3 text-left text-white">Agreement</th><th class="px-4 py-3 text-right text-white">Monthly</th><th class="px-4 py-3 text-left text-white">Status</th></tr></thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($company->fibreConnections as $connection)
                            <tr class="hover:bg-sky-50/60 dark:hover:bg-gray-900/50"><td class="px-4 py-3 font-medium text-[#020f40] dark:text-gray-100">{{ $connection->service_identifier ?? '-' }}</td><td class="px-4 py-3">{{ $connection->circuit_reference ?? '-' }}</td><td class="px-4 py-3">{{ $connection->access_type ?? '-' }}</td><td class="px-4 py-3">{{ $connection->bandwidth ?? '-' }}</td><td class="px-4 py-3">{{ $connection->location_address ?? '-' }}</td><td class="px-4 py-3">{{ $connection->agreement?->name ?? '-' }}</td><td class="px-4 py-3 text-right font-medium">£{{ number_format($connection->monthly_cost, 2) }}</td><td class="px-4 py-3">{{ $connection->status ?? '-' }}</td></tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-4 text-gray-500">No fibre connections found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </section>

            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="h-3 w-3 rounded-full bg-cyan-500"></span>
                    <h3 class="text-base font-semibold text-[#020f40] dark:text-gray-100">Jola Details</h3>
                </div>
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-[#020f40]"><tr><th class="px-4 py-3 text-left text-white">Jola Client</th><th class="px-4 py-3 text-left text-white">Jola SIM ID</th><th class="px-4 py-3 text-left text-white">Jola Customer ID</th><th class="px-4 py-3 text-left text-white">ICCID</th><th class="px-4 py-3 text-left text-white">MSISDN</th><th class="px-4 py-3 text-left text-white">Last synced</th></tr></thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($jolaSims as $sim)
                            <tr class="hover:bg-cyan-50/60 dark:hover:bg-gray-900/50"><td class="px-4 py-3 font-medium text-[#020f40] dark:text-gray-100">{{ $company->jolaCustomer?->name ?? '-' }}</td><td class="px-4 py-3 font-mono text-xs">{{ $sim->mobilemanager_sim_id }}</td><td class="px-4 py-3">{{ $sim->mobilemanager_customer_id ?? '-' }}</td><td class="px-4 py-3 font-mono text-xs">{{ $sim->iccid ?? '-' }}</td><td class="px-4 py-3">{{ $sim->msisdn ?? '-' }}</td><td class="px-4 py-3">{{ $sim->last_synced_at?->format('d M Y H:i') ?? '-' }}</td></tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-4 text-gray-500">No Jola SIM details linked to this company.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </section>

            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="h-3 w-3 rounded-full bg-[#FFA500]"></span>
                    <h3 class="text-base font-semibold text-[#020f40] dark:text-gray-100">Invoices and Payments</h3>
                </div>
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-[#020f40]"><tr><th class="px-4 py-3 text-left text-white">Invoice</th><th class="px-4 py-3 text-left text-white">Due</th><th class="px-4 py-3 text-left text-white">Auto collect from</th><th class="px-4 py-3 text-right text-white">Total</th><th class="px-4 py-3 text-right text-white">Balance</th><th class="px-4 py-3 text-left text-white">Invoice</th><th class="px-4 py-3 text-left text-white">Payment</th><th class="px-4 py-3 text-left text-white">GoCardless</th></tr></thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($company->invoices as $invoice)
                            @php($payment = $invoice->payments->sortByDesc('created_at')->first())
                            <tr class="hover:bg-orange-50/60 dark:hover:bg-gray-900/50"><td class="px-4 py-3"><div class="font-medium text-[#020f40] dark:text-gray-100">{{ $invoice->invoice_number }}</div><div class="text-xs text-gray-500 dark:text-gray-400">{{ $invoice->invoice_date?->format('d M Y') ?? 'No invoice date' }}</div>@if ($invoice->items->isNotEmpty())<div class="mt-3 space-y-2 rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/50">@foreach ($invoice->items as $item)<div class="flex items-start justify-between gap-3 text-xs"><div><div class="font-medium text-gray-800 dark:text-gray-100">{{ $item->description ?? 'Invoice line' }}</div><div class="mt-1"><span class="inline-flex rounded-md px-2 py-1 text-xs font-medium {{ $item->service_type === 'fibre' ? 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-100' : ($item->service_type === 'mixed' ? 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-100' : 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-100') }}">{{ ucfirst($item->service_type ?? 'unknown') }}</span></div></div><div class="text-right text-gray-600 dark:text-gray-300">@if ($item->quantity)<div>Qty {{ rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.') }}</div>@endif<div>£{{ number_format((float) ($item->line_total ?? 0), 2) }}</div></div></div>@endforeach</div>@endif</td><td class="px-4 py-3">{{ $invoice->due_date?->format('d M Y') ?? '-' }}</td><td class="px-4 py-3">{{ $invoice->due_date?->copy()->subDays($company->auto_collect_days_before_due)->format('d M Y') ?? '-' }}</td><td class="px-4 py-3 text-right">£{{ number_format($invoice->total, 2) }}</td><td class="px-4 py-3 text-right font-medium text-[#020f40] dark:text-gray-100">£{{ number_format($invoice->balance, 2) }}</td><td class="px-4 py-3">{!! $statusBadge($invoice->status) !!}</td><td class="px-4 py-3">{!! $statusBadge($invoice->payment_status) !!}</td><td class="px-4 py-3">@if ($payment)<div class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $payment->gocardless_payment_id }}</div><div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Charge {{ $payment->charge_date?->format('d M Y') ?? '-' }}</div>@else<span class="text-gray-500 dark:text-gray-400">-</span>@endif</td></tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-4 text-gray-500">No invoices found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </section>

            <section class="space-y-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="h-3 w-3 rounded-full bg-[#FFA500]"></span>
                        <h3 class="text-base font-semibold text-[#020f40] dark:text-gray-100">Direct Debit and Payments</h3>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('admin.companies.gocardless.refresh-mandates', $company) }}">
                            @csrf
                            <x-secondary-button>Refresh mandates</x-secondary-button>
                        </form>
                        <form method="POST" action="{{ route('admin.companies.gocardless.refresh-payments', $company) }}">
                            @csrf
                            <x-secondary-button>Refresh payments</x-secondary-button>
                        </form>
                    </div>
                </div>
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-[#020f40]"><tr><th class="px-4 py-3 text-left text-white">Mandate ID</th><th class="px-4 py-3 text-left text-white">Status</th></tr></thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($company->mandates as $mandate)
                                <tr class="hover:bg-orange-50/60 dark:hover:bg-gray-900/50"><td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $mandate->mandate_id }}</td><td class="px-4 py-3">{!! $statusBadge($mandate->status) !!}</td></tr>
                            @empty
                                <tr><td colspan="2" class="px-4 py-4 text-gray-500">No mandates found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-[#020f40]"><tr><th class="px-4 py-3 text-left text-white">Payment</th><th class="px-4 py-3 text-right text-white">Amount</th><th class="px-4 py-3 text-left text-white">Status</th><th class="px-4 py-3 text-left text-white">Charge date</th></tr></thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($company->payments as $payment)
                                <tr class="hover:bg-orange-50/60 dark:hover:bg-gray-900/50"><td class="px-4 py-3"><div class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $payment->gocardless_payment_id }}</div><div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $payment->invoice?->invoice_number ?? 'No invoice link' }}</div></td><td class="px-4 py-3 text-right font-medium text-[#020f40] dark:text-gray-100">£{{ number_format($payment->amount, 2) }}</td><td class="px-4 py-3">{!! $statusBadge($payment->status) !!}</td><td class="px-4 py-3">{{ $payment->charge_date?->format('d M Y') ?? '-' }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-4 text-gray-500">No payments found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
