@php
    $badge = function (?string $status) {
        $status = $status ?: 'not tested';
        $key = strtolower(str_replace([' ', '_'], '-', $status));
        $classes = match ($key) {
            'success', 'configured' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
            'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-300',
        };

        return '<span class="inline-flex rounded-md px-2 py-1 text-xs font-medium '.$classes.'">'.e($status).'</span>';
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Admin dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="text-sm text-green-700 dark:text-green-300">{{ session('status') }}</div>
            @endif
            @if ($errors->has('sync'))
                <div class="text-sm text-red-700 dark:text-red-300">{{ $errors->first('sync') }}</div>
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
                                <div class="text-xs font-semibold uppercase tracking-widest text-[#FFA500]">Admin portal</div>
                                <h3 class="mt-2 text-2xl font-semibold text-white">SIM operations dashboard</h3>
                                <p class="mt-1 text-sm text-slate-200">Monitor PSA sync, Jola matching, invoices, and GoCardless collection from one place.</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.settings.edit') }}" class="inline-flex items-center justify-center rounded-md bg-[#FFA500] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[#020f40] shadow-sm transition hover:bg-[#ffb52e] focus:outline-none focus:ring-2 focus:ring-[#FFA500] focus:ring-offset-2 focus:ring-offset-[#020f40]">Settings</a>
                    </div>
                </div>
            </section>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ([
                    'Companies' => $companyCount,
                    'Agreements' => $agreementCount,
                    'SIMs' => $simCount,
                    'Invoices' => $invoiceCount,
                    'Payments' => $paymentCount,
                ] as $label => $count)
                    <div class="rounded-lg border border-[#020f40]/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-800">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                        <div class="mt-2 text-3xl font-semibold text-[#020f40] dark:text-gray-100">{{ $count }}</div>
                        <div class="mt-3 h-1 rounded-full bg-orange-100 dark:bg-gray-700">
                            <div class="h-1 w-1/2 rounded-full bg-[#FFA500]"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-4 lg:grid-cols-4">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Open invoice balance</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">£{{ number_format($openBalance, 2) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Pending payments</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $pendingPayments }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Matched SIMs</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $matchedSims }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Needs SIM match</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $connectWiseOnlySims + $jolaOnlySims }}</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">CW only {{ $connectWiseOnlySims }} · Jola only {{ $jolaOnlySims }}</div>
                </div>
            </div>

            <section class="grid gap-4 lg:grid-cols-3">
                @foreach ($statuses as $provider => $status)
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 space-y-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $status['label'] }}</h3>
                                <div class="mt-2">{!! $badge($status['status']) !!}</div>
                            </div>
                            <a href="{{ route('admin.settings.edit', ['tab' => $provider === 'jola' ? 'jola' : $provider]) }}" class="text-sm text-indigo-600 hover:underline dark:text-indigo-300">Settings</a>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $status['sync_message'] ?? $status['message'] ?? 'No recent status.' }}</p>
                        @if ($status['synced_at'])
                            <p class="text-xs text-gray-500 dark:text-gray-500">Last run {{ \Illuminate\Support\Carbon::parse($status['synced_at'])->format('d M Y H:i') }}</p>
                        @endif
                    </div>
                @endforeach
            </section>

            <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 space-y-4">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Manual Sync</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Run local test syncs directly. Scheduled production syncs still run through Laravel commands and queues.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    @foreach ([
                        'connectwise_agreements' => 'Sync PSA agreements',
                        'connectwise_invoices' => 'Sync PSA invoices',
                        'jola_customers' => 'Sync Jola customers',
                        'jola_sims' => 'Sync Jola SIMs',
                        'jola_products' => 'Sync Jola products',
                        'gocardless_payments' => 'Refresh GoCardless',
                    ] as $type => $label)
                        <form method="POST" action="{{ route('admin.sync') }}">
                            @csrf
                            <input type="hidden" name="sync_type" value="{{ $type }}">
                            <x-secondary-button>{{ $label }}</x-secondary-button>
                        </form>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
