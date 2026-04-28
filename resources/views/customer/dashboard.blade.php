@php
    $badge = function (?string $status) {
        $status = $status ?: 'not requested';
        $key = strtolower(str_replace([' ', '_'], '-', $status));
        $classes = match ($key) {
            'confirmed', 'paid', 'paid-out', 'active' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
            'pending-submission', 'submitted', 'created' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
            'cancelled', 'failed', 'charged-back' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-300',
        };

        return '<span class="inline-flex rounded-md px-2 py-1 text-xs font-medium '.$classes.'">'.e(str_replace('_', ' ', $status)).'</span>';
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">{{ $company->name }}</h2>
            <a href="{{ route('customer.direct-debit.setup') }}" class="text-sm text-indigo-600 hover:underline dark:text-indigo-300">Direct Debit</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <section class="overflow-hidden rounded-lg border border-[#020f40]/10 bg-[#020f40] shadow-sm dark:border-white/10">
                <div class="relative px-6 py-7">
                    <div class="absolute inset-x-0 bottom-0 h-1 bg-[#FFA500]"></div>
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-md bg-white p-3 shadow-sm">
                                <img src="{{ asset('images/micronet-logo.svg') }}" alt="Micronet" class="max-h-11 w-auto">
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-[#FFA500]">Customer portal</div>
                                <h3 class="mt-2 text-2xl font-semibold text-white">{{ $company->name }}</h3>
                                <p class="mt-1 text-sm text-slate-200">View SIMs, invoices, balances, and Direct Debit status in your portal.</p>
                            </div>
                        </div>
                        <a href="{{ route('customer.direct-debit.setup') }}" class="inline-flex items-center justify-center rounded-md bg-[#FFA500] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[#020f40] shadow-sm transition hover:bg-[#ffb52e] focus:outline-none focus:ring-2 focus:ring-[#FFA500] focus:ring-offset-2 focus:ring-offset-[#020f40]">Direct Debit</a>
                    </div>
                </div>
            </section>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('customer.sims.index') }}" class="rounded-lg border border-[#020f40]/10 bg-white p-5 shadow-sm transition hover:border-[#FFA500] hover:bg-orange-50 dark:border-white/10 dark:bg-gray-800 dark:hover:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Active SIM estate</div>
                    <div class="mt-2 text-3xl font-semibold text-[#020f40] dark:text-gray-100">{{ $simCount }}</div>
                </a>
                <a href="{{ route('customer.invoices.index') }}" class="rounded-lg border border-[#020f40]/10 bg-white p-5 shadow-sm transition hover:border-[#FFA500] hover:bg-orange-50 dark:border-white/10 dark:bg-gray-800 dark:hover:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Invoices</div>
                    <div class="mt-2 text-3xl font-semibold text-[#020f40] dark:text-gray-100">{{ $invoiceCount }}</div>
                </a>
                <div class="rounded-lg border border-[#020f40]/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Open balance</div>
                    <div class="mt-2 text-3xl font-semibold text-[#020f40] dark:text-gray-100">£{{ number_format($openBalance, 2) }}</div>
                </div>
                <div class="rounded-lg border border-orange-100 bg-orange-50 p-5 shadow-sm dark:border-orange-900/60 dark:bg-orange-950/20">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Direct Debit</div>
                    <div class="mt-3">{!! $badge($mandate?->status) !!}</div>
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $nextPayment ? 'Next charge '.$nextPayment->charge_date?->format('d M Y') : 'No charge scheduled' }}</div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Recent Invoices</h3>
                        <a href="{{ route('customer.invoices.index') }}" class="text-sm text-indigo-600 hover:underline dark:text-indigo-300">View all</a>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($recentInvoices as $invoice)
                            @php($payment = $invoice->payments->sortByDesc('created_at')->first())
                            <div class="flex items-center justify-between gap-4 px-5 py-4">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $invoice->invoice_number }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $invoice->due_date?->format('d M Y') ?? 'No due date' }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">£{{ number_format($invoice->balance, 2) }}</div>
                                    <div class="mt-1">{!! $badge($invoice->payment_status) !!}</div>
                                    @if ($payment)
                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Charge {{ $payment->charge_date?->format('d M Y') ?? '-' }}</div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="px-5 py-6 text-sm text-gray-500 dark:text-gray-400">No invoices found.</div>
                        @endforelse
                    </div>
                </section>

                <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Recent SIMs</h3>
                        <a href="{{ route('customer.sims.index') }}" class="text-sm text-indigo-600 hover:underline dark:text-indigo-300">View all</a>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($recentSims as $sim)
                            <div class="flex items-center justify-between gap-4 px-5 py-4">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $sim->mobile_number ?? $sim->msisdn ?? 'No mobile number' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $sim->network ?? '-' }} · {{ $sim->tariff ?? '-' }}</div>
                                </div>
                                <div class="text-right text-sm text-gray-600 dark:text-gray-300">£{{ number_format($sim->monthly_cost, 2) }}</div>
                            </div>
                        @empty
                            <div class="px-5 py-6 text-sm text-gray-500 dark:text-gray-400">No SIMs found.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
