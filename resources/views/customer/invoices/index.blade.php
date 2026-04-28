@php
    $badge = function (?string $status) {
        $status = $status ?: 'not requested';
        $key = strtolower(str_replace([' ', '_'], '-', $status));
        $classes = match ($key) {
            'confirmed', 'paid', 'paid-out' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
            'pending-submission', 'submitted', 'created' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
            'cancelled', 'failed', 'charged-back' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-300',
        };

        return '<span class="inline-flex rounded-md px-2 py-1 text-xs font-medium '.$classes.'">'.e(str_replace('_', ' ', $status)).'</span>';
    };
@endphp

<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">My Invoices</h2></x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="overflow-x-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left">Invoice</th>
                            <th class="px-4 py-3 text-left">Due</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Balance</th>
                            <th class="px-4 py-3 text-left">Payment</th>
                            <th class="px-4 py-3 text-left">Collection</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($invoices as $invoice)
                        @php($payment = $invoice->payments->sortByDesc('created_at')->first())
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $invoice->invoice_number }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $invoice->invoice_date?->format('d M Y') ?? 'No invoice date' }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $invoice->due_date?->format('d M Y') ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">£{{ number_format($invoice->total, 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium {{ (float) $invoice->balance > 0 ? 'text-gray-900 dark:text-gray-100' : 'text-green-700 dark:text-green-300' }}">£{{ number_format($invoice->balance, 2) }}</td>
                            <td class="px-4 py-3">{!! $badge($invoice->payment_status) !!}</td>
                            <td class="px-4 py-3">
                                @if ($payment)
                                    <div class="text-sm text-gray-700 dark:text-gray-300">Charge {{ $payment->charge_date?->format('d M Y') ?? '-' }}</div>
                                    <div class="mt-1 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $payment->gocardless_payment_id }}</div>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">Not scheduled</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No invoices found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $invoices->links() }}</div>
        </div>
    </div>
</x-app-layout>
