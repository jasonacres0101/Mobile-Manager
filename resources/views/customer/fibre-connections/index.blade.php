@php
    $statusBadge = function (?string $status) {
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
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">My Fibre Connections</h2>
            <a href="{{ route('customer.dashboard') }}" class="text-sm text-[#020f40] hover:text-[#FFA500] hover:underline dark:text-orange-200">{{ $company->name }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-[#020f40]">
                            <tr>
                                <th class="px-4 py-3 text-left text-white">Service</th>
                                <th class="px-4 py-3 text-left text-white">Circuit</th>
                                <th class="px-4 py-3 text-left text-white">Access</th>
                                <th class="px-4 py-3 text-left text-white">Bandwidth</th>
                                <th class="px-4 py-3 text-right text-white">Monthly</th>
                                <th class="px-4 py-3 text-left text-white">Status</th>
                                <th class="px-4 py-3 text-left text-white">Last synced</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($connections as $connection)
                            <tr class="hover:bg-cyan-50/60 dark:hover:bg-gray-900">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $connection->service_identifier ?? '-' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $connection->agreement?->name ?? 'No agreement' }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $connection->circuit_reference ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $connection->access_type ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $connection->bandwidth ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">£{{ number_format($connection->monthly_cost, 2) }}</td>
                                <td class="px-4 py-3">{!! $statusBadge($connection->status) !!}</td>
                                <td class="px-4 py-3">{{ $connection->last_synced_at?->format('d M Y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No fibre connections found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $connections->links() }}</div>
        </div>
    </div>
</x-app-layout>
