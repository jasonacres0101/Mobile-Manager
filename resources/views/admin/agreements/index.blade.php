@php
    $badge = function (?string $status) {
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
        $url = route('admin.agreements.index', array_merge(request()->query(), ['sort' => $field, 'direction' => $direction]));

        return '<a href="'.$url.'" class="font-medium text-white hover:text-[#FFA500]">'.e($label.$arrow).'</a>';
    };
@endphp

<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Agreements</h2></x-slot>

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
                            <h3 class="mt-2 text-2xl font-semibold text-white">SIM Agreements</h3>
                            <p class="mt-1 text-sm text-slate-200">Only configured SIM agreement type IDs are synced from PSA into this portal.</p>
                        </div>
                    </div>
                </div>
            </section>

            <form method="GET" class="rounded-lg border border-orange-100 bg-orange-50 p-4 shadow-sm dark:border-orange-900/60 dark:bg-orange-950/20">
                <div class="grid gap-3 xl:grid-cols-[1fr_auto_auto_auto_auto]">
                    <x-text-input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search company, agreement, CW ID, type ID" class="w-full" />
                    <select name="status" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    <select name="type_id" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">All types</option>
                        @foreach ($typeIds as $typeId)
                            <option value="{{ $typeId }}" @selected((string) ($filters['type_id'] ?? '') === (string) $typeId)>Type {{ $typeId }}</option>
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
                            <th class="px-4 py-3 text-left">{!! $sortLink('name', 'Agreement') !!}</th>
                            <th class="px-4 py-3 text-left">{!! $sortLink('connectwise_agreement_id', 'CW ID') !!}</th>
                            <th class="px-4 py-3 text-left">{!! $sortLink('connectwise_agreement_type_id', 'Type ID') !!}</th>
                            <th class="px-4 py-3 text-left">{!! $sortLink('status', 'Status') !!}</th>
                            <th class="px-4 py-3 text-left">{!! $sortLink('start_date', 'Start') !!}</th>
                            <th class="px-4 py-3 text-left">{!! $sortLink('end_date', 'End') !!}</th>
                            <th class="px-4 py-3 text-left">{!! $sortLink('last_synced_at', 'Synced') !!}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($agreements as $agreement)
                        <tr class="hover:bg-orange-50/60 dark:hover:bg-gray-900">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.companies.show', $agreement->company) }}" class="font-medium text-[#020f40] hover:text-[#FFA500] hover:underline dark:text-cyan-200">{{ $agreement->company->name }}</a>
                            </td>
                            <td class="px-4 py-3 font-medium text-[#020f40] dark:text-gray-100">{{ $agreement->name }}</td>
                            <td class="px-4 py-3">{{ $agreement->connectwise_agreement_id }}</td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full bg-orange-100 px-2.5 py-1 text-xs font-medium text-orange-800 dark:bg-orange-900/50 dark:text-orange-100">{{ $agreement->connectwise_agreement_type_id }}</span></td>
                            <td class="px-4 py-3">{!! $badge($agreement->status) !!}</td>
                            <td class="px-4 py-3">{{ $agreement->start_date?->format('d M Y') ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $agreement->end_date?->format('d M Y') ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $agreement->last_synced_at?->diffForHumans() ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No agreements found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div>{{ $agreements->links() }}</div>
        </div>
    </div>
</x-app-layout>
