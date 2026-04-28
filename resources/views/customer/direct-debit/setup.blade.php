@php
    $badge = function (?string $status) {
        $status = $status ?: 'not set up';
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
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Direct Debit</h2>
            <a href="{{ route('customer.dashboard') }}" class="text-sm text-indigo-600 hover:underline dark:text-indigo-300">{{ $company->name }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="text-sm text-green-700 dark:text-green-300">{{ session('status') }}</div>
            @endif

            <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Mandate status</div>
                        <div class="mt-2">{!! $badge($mandate?->status) !!}</div>
                        @if ($mandate)
                            <div class="mt-2 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $mandate->mandate_id }}</div>
                        @endif
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <form method="POST" action="{{ route('customer.direct-debit.refresh') }}">
                            @csrf
                            <x-secondary-button>Refresh status</x-secondary-button>
                        </form>
                        <a href="{{ route('customer.direct-debit.setup', ['start' => 1]) }}" class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gray-700 focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-gray-900 dark:bg-gray-200 dark:text-gray-800 dark:hover:bg-white dark:focus:bg-white dark:active:bg-gray-300 dark:focus:ring-offset-gray-800">
                            {{ $mandate ? 'Update Direct Debit' : 'Set up Direct Debit' }}
                        </a>
                    </div>
                </div>
            </section>

            <section class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Recent Collections</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left">Invoice</th>
                                <th class="px-4 py-3 text-left">Payment</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Charge date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="px-4 py-3">{{ $payment->invoice?->invoice_number ?? '-' }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $payment->gocardless_payment_id }}</td>
                                <td class="px-4 py-3 text-right">£{{ number_format($payment->amount, 2) }}</td>
                                <td class="px-4 py-3">{!! $badge($payment->status) !!}</td>
                                <td class="px-4 py-3">{{ $payment->charge_date?->format('d M Y') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No collections found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
