@php
    $badge = function (?string $status) {
        $status = $status ?: 'unknown';
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
        $url = route('admin.payments.index', array_merge(request()->query(), ['sort' => $field, 'direction' => $direction]));

        return '<a href="'.$url.'" class="font-medium text-gray-700 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-300">'.e($label.$arrow).'</a>';
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Payments</h2>
            <form method="POST" action="{{ route('admin.payments.refresh-gocardless') }}">
                @csrf
                <x-secondary-button>Refresh GoCardless payments</x-secondary-button>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="text-sm text-green-700 dark:text-green-300">{{ session('status') }}</div>
            @endif

            <form method="GET" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="grid gap-3 lg:grid-cols-[1fr_auto_auto_auto]">
                    <x-text-input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search company, invoice, payment ID, status" class="w-full" />
                    <select name="status" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">All statuses</option>
                        @foreach (['created', 'pending_submission', 'submitted', 'confirmed', 'failed', 'cancelled'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                        @endforeach
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
                            <th class="px-4 py-3 text-left">Invoice</th>
                            <th class="px-4 py-3 text-left">GoCardless payment</th>
                            <th class="px-4 py-3 text-right">{!! $sortLink('amount', 'Amount') !!}</th>
                            <th class="px-4 py-3 text-left">{!! $sortLink('status', 'Status') !!}</th>
                            <th class="px-4 py-3 text-left">{!! $sortLink('charge_date', 'Charge date') !!}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($payments as $payment)
                        <tr class="hover:bg-indigo-50/60 dark:hover:bg-gray-900">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.companies.show', $payment->company) }}" class="font-medium text-indigo-600 hover:underline dark:text-indigo-300">{{ $payment->company->name }}</a>
                            </td>
                            <td class="px-4 py-3">{{ $payment->invoice?->invoice_number ?? '-' }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $payment->gocardless_payment_id }}</td>
                            <td class="px-4 py-3 text-right">£{{ number_format($payment->amount, 2) }}</td>
                            <td class="px-4 py-3">{!! $badge($payment->status) !!}</td>
                            <td class="px-4 py-3">{{ $payment->charge_date?->format('d M Y') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No GoCardless payments found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $payments->links() }}</div>
        </div>
    </div>
</x-app-layout>
