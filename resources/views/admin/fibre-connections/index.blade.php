@php
    $statusBadge = function (?string $status) {
        $status = $status ?: 'unknown';
        $key = strtolower($status);
        $classes = str_contains($key, 'active')
            ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200'
            : 'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-300';

        return '<span class="inline-flex rounded-md px-2 py-1 text-xs font-medium '.$classes.'">'.e($status).'</span>';
    };

    $sortLink = function (string $field, string $label) use ($filters) {
        $active = ($filters['sort'] ?? 'created_at') === $field;
        $direction = $active && ($filters['direction'] ?? 'desc') === 'asc' ? 'desc' : 'asc';
        $arrow = $active ? (($filters['direction'] ?? 'desc') === 'asc' ? ' ↑' : ' ↓') : '';
        $url = route('admin.fibre-connections.index', array_merge(request()->query(), ['sort' => $field, 'direction' => $direction]));

        return '<a href="'.$url.'" class="font-medium text-white hover:text-[#FFA500]">'.e($label.$arrow).'</a>';
    };
@endphp

<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Fibre Connections</h2></x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <section class="overflow-hidden rounded-lg border border-[#020f40]/10 bg-[#020f40] shadow-sm dark:border-white/10">
                <div class="relative px-6 py-7">
                    <div class="absolute inset-x-0 bottom-0 h-1 bg-[#FFA500]"></div>
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-md bg-white p-3 shadow-sm">
                            <img src="{{ asset('images/micronet-logo.svg') }}" alt="Micronet" class="max-h-11 w-auto">
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-[#FFA500]">ConnectWise PSA</div>
                            <h3 class="mt-2 text-2xl font-semibold text-white">Fibre Connections</h3>
                            <p class="mt-1 text-sm text-slate-200">Configured fibre agreement types synced from PSA and billed alongside SIM services.</p>
                        </div>
                    </div>
                </div>
            </section>

            <form method="GET" class="rounded-lg border border-cyan-100 bg-cyan-50 p-4 shadow-sm dark:border-cyan-900/60 dark:bg-cyan-950/20">
                <div class="grid gap-3 xl:grid-cols-[1fr_auto_auto_auto]">
                    <x-text-input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search company, agreement, circuit, service, bandwidth" class="w-full" />
                    <select name="status" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
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

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-[#020f40]">
                            <tr>
                                <th class="px-4 py-3 text-left text-white">Company</th>
                                <th class="px-4 py-3 text-left">{!! $sortLink('service_identifier', 'Service') !!}</th>
                                <th class="px-4 py-3 text-left">{!! $sortLink('circuit_reference', 'Circuit') !!}</th>
                                <th class="px-4 py-3 text-left">{!! $sortLink('access_type', 'Access') !!}</th>
                                <th class="px-4 py-3 text-left">{!! $sortLink('bandwidth', 'Bandwidth') !!}</th>
                                <th class="px-4 py-3 text-left text-white">Agreement</th>
                                <th class="px-4 py-3 text-right">{!! $sortLink('monthly_cost', 'Monthly') !!}</th>
                                <th class="px-4 py-3 text-left">{!! $sortLink('status', 'Status') !!}</th>
                                <th class="px-4 py-3 text-left">{!! $sortLink('last_synced_at', 'Synced') !!}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($connections as $connection)
                            <tr class="hover:bg-cyan-50/60 dark:hover:bg-gray-900">
                                <td class="px-4 py-3">
                                    @if ($connection->company)
                                        <a href="{{ route('admin.companies.show', $connection->company) }}" class="font-medium text-[#020f40] hover:text-[#FFA500] hover:underline dark:text-cyan-200">{{ $connection->company->name }}</a>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">No company</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium text-[#020f40] dark:text-gray-100">{{ $connection->service_identifier ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $connection->circuit_reference ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $connection->access_type ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $connection->bandwidth ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                    <div>{{ $connection->agreement?->name ?? 'No agreement' }}</div>
                                    <div class="mt-1">CW {{ $connection->connectwise_addition_id ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-right font-medium">£{{ number_format($connection->monthly_cost, 2) }}</td>
                                <td class="px-4 py-3">{!! $statusBadge($connection->status) !!}</td>
                                <td class="px-4 py-3">{{ $connection->last_synced_at?->diffForHumans() ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No fibre connections found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $connections->links() }}</div>
        </div>
    </div>
</x-app-layout>
