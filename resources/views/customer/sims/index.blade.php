@php
    $simStatus = function (?string $status) {
        $status = $status ?: 'unknown';
        $key = strtolower($status);
        $classes = str_contains($key, 'active')
            ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200'
            : 'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-300';

        return '<span class="inline-flex rounded-md px-2 py-1 text-xs font-medium '.$classes.'">'.e($status).'</span>';
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">My SIMs</h2>
            <a href="{{ route('customer.dashboard') }}" class="text-sm text-indigo-600 hover:underline dark:text-indigo-300">{{ $company->name }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="overflow-x-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left">Mobile</th>
                            <th class="px-4 py-3 text-left">ICCID</th>
                            <th class="px-4 py-3 text-left">Network</th>
                            <th class="px-4 py-3 text-left">Tariff</th>
                            <th class="px-4 py-3 text-right">Monthly</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Last synced</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($sims as $sim)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $sim->mobile_number ?? $sim->msisdn ?? '-' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $sim->agreement?->name ?? 'No agreement' }}</div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $sim->iccid ?? $sim->sim_number ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $sim->network ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $sim->tariff ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">£{{ number_format($sim->monthly_cost, 2) }}</td>
                            <td class="px-4 py-3">{!! $simStatus($sim->status) !!}</td>
                            <td class="px-4 py-3">{{ $sim->last_synced_at?->format('d M Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No SIMs found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $sims->links() }}</div>
        </div>
    </div>
</x-app-layout>
