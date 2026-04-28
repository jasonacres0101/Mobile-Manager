@php
    $matchBadge = function ($sim) {
        if ($sim->connectwise_addition_id && $sim->mobilemanager_sim_id) {
            return '<span class="inline-flex rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-900/40 dark:text-green-200">Matched</span>';
        }

        if ($sim->connectwise_addition_id) {
            return '<span class="inline-flex rounded-md bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">PSA only</span>';
        }

        if ($sim->mobilemanager_sim_id) {
            return '<span class="inline-flex rounded-md bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-200">Jola only</span>';
        }

        return '<span class="inline-flex rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-900 dark:text-gray-300">Unmatched</span>';
    };

    $sortLink = function (string $field, string $label) use ($filters) {
        $active = ($filters['sort'] ?? 'created_at') === $field;
        $direction = $active && ($filters['direction'] ?? 'desc') === 'asc' ? 'desc' : 'asc';
        $arrow = $active ? (($filters['direction'] ?? 'desc') === 'asc' ? ' ↑' : ' ↓') : '';
        $url = route('admin.sims.index', array_merge(request()->query(), ['sort' => $field, 'direction' => $direction]));

        return '<a href="'.$url.'" class="font-medium text-gray-700 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-300">'.e($label.$arrow).'</a>';
    };
@endphp

<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">SIMs</h2></x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <form method="GET" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="grid gap-3 lg:grid-cols-[1fr_auto_auto_auto]">
                    <x-text-input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search company, mobile, ICCID, network, tariff" class="w-full" />
                    <select name="match" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">All matches</option>
                        <option value="matched" @selected(($filters['match'] ?? '') === 'matched')>Matched</option>
                        <option value="psa_only" @selected(($filters['match'] ?? '') === 'psa_only')>PSA only</option>
                        <option value="jola_only" @selected(($filters['match'] ?? '') === 'jola_only')>Jola only</option>
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
                            <th class="px-4 py-3 text-left">{!! $sortLink('mobile_number', 'Mobile') !!}</th>
                            <th class="px-4 py-3 text-left">{!! $sortLink('iccid', 'ICCID') !!}</th>
                            <th class="px-4 py-3 text-left">{!! $sortLink('network', 'Network') !!}</th>
                            <th class="px-4 py-3 text-left">{!! $sortLink('tariff', 'Tariff') !!}</th>
                            <th class="px-4 py-3 text-right">{!! $sortLink('monthly_cost', 'Monthly') !!}</th>
                            <th class="px-4 py-3 text-left">Match</th>
                            <th class="px-4 py-3 text-left">Source IDs</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($sims as $sim)
                        <tr class="hover:bg-indigo-50/60 dark:hover:bg-gray-900">
                            <td class="px-4 py-3">
                                @if ($sim->company)
                                    <a href="{{ route('admin.companies.show', $sim->company) }}" class="font-medium text-indigo-600 hover:underline dark:text-indigo-300">{{ $sim->company->name }}</a>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">No company</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $sim->mobile_number ?? $sim->msisdn ?? '-' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $sim->iccid ?? $sim->sim_number ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $sim->network ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $sim->tariff ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">£{{ number_format($sim->monthly_cost, 2) }}</td>
                            <td class="px-4 py-3">{!! $matchBadge($sim) !!}</td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                <div>CW: {{ $sim->connectwise_addition_id ?? '-' }}</div>
                                <div>Jola: {{ $sim->mobilemanager_sim_id ?? '-' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No SIMs found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $sims->links() }}</div>
        </div>
    </div>
</x-app-layout>
