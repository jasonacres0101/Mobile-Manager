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

    $serviceBadge = function (string $serviceType) {
        $classes = match ($serviceType) {
            'fibre' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-100',
            'mixed' => 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-100',
            'unknown' => 'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-300',
            default => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-100',
        };

        return '<span class="inline-flex rounded-md px-2 py-1 text-xs font-medium '.$classes.'">'.e(ucfirst($serviceType)).'</span>';
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
                                @if ($invoice->items->isNotEmpty())
                                    <div class="mt-3 space-y-2 rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/50">
                                        @foreach ($invoice->items as $item)
                                            <div class="flex items-start justify-between gap-3 text-xs">
                                                <div>
                                                    <div class="font-medium text-gray-800 dark:text-gray-100">{{ $item->description ?? 'Invoice line' }}</div>
                                                    <div class="mt-1">{!! $serviceBadge($item->service_type ?? 'unknown') !!}</div>
                                                </div>
                                                <div class="text-right text-gray-600 dark:text-gray-300">
                                                    @if ($item->quantity)
                                                        <div>Qty {{ rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.') }}</div>
                                                    @endif
                                                    <div>£{{ number_format((float) ($item->line_total ?? 0), 2) }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
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
