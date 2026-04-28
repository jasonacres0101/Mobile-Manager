@php
    $sortLink = function (string $field, string $label) use ($filters) {
        $active = ($filters['sort'] ?? 'name') === $field;
        $direction = $active && ($filters['direction'] ?? 'asc') === 'asc' ? 'desc' : 'asc';
        $arrow = $active ? (($filters['direction'] ?? 'asc') === 'asc' ? ' ↑' : ' ↓') : '';
        $url = route('admin.companies.index', array_merge(request()->query(), ['sort' => $field, 'direction' => $direction]));

        return '<a href="'.$url.'" class="font-medium text-white hover:text-[#FFA500]">'.e($label.$arrow).'</a>';
    };
@endphp

<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Companies</h2></x-slot>

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
                            <div class="text-xs font-semibold uppercase tracking-widest text-[#FFA500]">Admin portal</div>
                            <h3 class="mt-2 text-2xl font-semibold text-white">Companies</h3>
                            <p class="mt-1 text-sm text-slate-200">Customer records synced from ConnectWise PSA with SIM, fibre, invoice, and payment links.</p>
                        </div>
                    </div>
                </div>
            </section>

            <form method="GET" class="rounded-lg border border-cyan-100 bg-cyan-50 p-4 shadow-sm dark:border-cyan-900/60 dark:bg-cyan-950/20">
                <div class="grid gap-3 md:grid-cols-[1fr_auto_auto]">
                    <x-text-input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search company, ConnectWise ID, GoCardless ID" class="w-full" />
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
                    <thead class="bg-[#020f40]"><tr>
                        <th class="px-4 py-3 text-left">{!! $sortLink('name', 'Name') !!}</th>
                        <th class="px-4 py-3 text-left">{!! $sortLink('connectwise_company_id', 'CW ID') !!}</th>
                        <th class="px-4 py-3 text-left text-white">GoCardless</th>
                        <th class="px-4 py-3 text-left">{!! $sortLink('agreements_count', 'Agreements') !!}</th>
                        <th class="px-4 py-3 text-left">{!! $sortLink('sims_count', 'SIMs') !!}</th>
                        <th class="px-4 py-3 text-left">{!! $sortLink('fibre_connections_count', 'Fibre') !!}</th>
                        <th class="px-4 py-3 text-left">{!! $sortLink('invoices_count', 'Invoices') !!}</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($companies as $company)
                        <tr class="hover:bg-cyan-50/60 dark:hover:bg-gray-900">
                            <td class="px-4 py-3"><a href="{{ route('admin.companies.show', $company) }}" class="font-medium text-[#020f40] hover:text-[#FFA500] hover:underline dark:text-cyan-200">{{ $company->name }}</a></td>
                            <td class="px-4 py-3">{{ $company->connectwise_company_id }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $company->gocardless_customer_id ?? '-' }}</td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full bg-orange-100 px-2.5 py-1 text-xs font-medium text-orange-800 dark:bg-orange-900/50 dark:text-orange-100">{{ $company->agreements_count }}</span></td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full bg-cyan-100 px-2.5 py-1 text-xs font-medium text-cyan-800 dark:bg-cyan-900/50 dark:text-cyan-100">{{ $company->sims_count }}</span></td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-800 dark:bg-sky-900/50 dark:text-sky-100">{{ $company->fibre_connections_count }}</span></td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-[#020f40] dark:bg-gray-900 dark:text-gray-100">{{ $company->invoices_count }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">No companies found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div>{{ $companies->links() }}</div>
        </div>
    </div>
</x-app-layout>
