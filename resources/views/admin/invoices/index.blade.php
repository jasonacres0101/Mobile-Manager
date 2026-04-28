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

    $sortLink = function (string $field, string $label) use ($filters) {
        $active = ($filters['sort'] ?? 'created_at') === $field;
        $direction = $active && ($filters['direction'] ?? 'desc') === 'asc' ? 'desc' : 'asc';
        $arrow = $active ? (($filters['direction'] ?? 'desc') === 'asc' ? ' ↑' : ' ↓') : '';
        $url = route('admin.invoices.index', array_merge(request()->query(), ['sort' => $field, 'direction' => $direction]));

        return '<a href="'.$url.'" class="font-medium text-gray-700 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-300">'.e($label.$arrow).'</a>';
    };
@endphp

<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Invoices</h2></x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="text-sm text-green-700 dark:text-green-300">{{ session('status') }}</div>
            @endif
            @if ($errors->has('collect'))
                <div class="text-sm text-red-700 dark:text-red-300">{{ $errors->first('collect') }}</div>
            @endif

            <form method="GET" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="grid gap-3 xl:grid-cols-[1fr_auto_auto_auto]">
                    <x-text-input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search company, invoice, status, payment ID" class="w-full" />
                    <select name="balance" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">All balances</option>
                        <option value="open" @selected(($filters['balance'] ?? '') === 'open')>Open balance</option>
                        <option value="paid" @selected(($filters['balance'] ?? '') === 'paid')>No balance</option>
                    </select>
                    <select name="per_page" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        @foreach ([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 25) === $size)>{{ $size }} per page</option>
                        @endforeach
                    </select>
                    <x-primary-button>Filter</x-primary-button>
                </div>
            </form>

            <div class="overflow-x-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left">Company</th>
                            <th class="px-4 py-3 text-left">{!! $sortLink('invoice_number', 'Invoice') !!}</th>
                            <th class="px-4 py-3 text-left">{!! $sortLink('due_date', 'Due') !!}</th>
                            <th class="px-4 py-3 text-right">{!! $sortLink('total', 'Total') !!}</th>
                            <th class="px-4 py-3 text-right">{!! $sortLink('balance', 'Balance') !!}</th>
                            <th class="px-4 py-3 text-left">{!! $sortLink('status', 'Invoice') !!}</th>
                            <th class="px-4 py-3 text-left">{!! $sortLink('payment_status', 'Payment') !!}</th>
                            <th class="px-4 py-3 text-left">GoCardless</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($invoices as $invoice)
                        @php($payment = $invoice->payments->sortByDesc('created_at')->first())
                        <tr class="hover:bg-indigo-50/60 dark:hover:bg-gray-900">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.companies.show', $invoice->company) }}" class="font-medium text-indigo-600 hover:underline dark:text-indigo-300">{{ $invoice->company->name }}</a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $invoice->invoice_number }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $invoice->invoice_date?->format('d M Y') ?? 'No invoice date' }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $invoice->due_date?->format('d M Y') ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">£{{ number_format($invoice->total, 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium {{ (float) $invoice->balance > 0 ? 'text-gray-900 dark:text-gray-100' : 'text-green-700 dark:text-green-300' }}">£{{ number_format($invoice->balance, 2) }}</td>
                            <td class="px-4 py-3">{!! $badge($invoice->status) !!}</td>
                            <td class="px-4 py-3">{!! $badge($invoice->payment_status) !!}</td>
                            <td class="px-4 py-3">
                                @if ($payment)
                                    <div class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $payment->gocardless_payment_id }}</div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Charge {{ $payment->charge_date?->format('d M Y') ?? '-' }}</div>
                                @elseif ($invoice->gocardless_payment_id)
                                    <div class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $invoice->gocardless_payment_id }}</div>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($invoice->collect_block_reason)
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $invoice->collect_block_reason }}</span>
                                @else
                                    <form method="POST" action="{{ route('admin.invoices.collect', $invoice) }}">
                                        @csrf
                                        <button class="font-medium text-indigo-600 hover:underline dark:text-indigo-300">Collect</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No invoices found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $invoices->links() }}</div>
        </div>
    </div>
</x-app-layout>
