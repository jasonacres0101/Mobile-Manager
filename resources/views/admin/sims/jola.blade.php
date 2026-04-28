<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Jola SIMs</h2></x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <section class="overflow-hidden rounded-lg border border-[#020f40]/10 bg-[#020f40] shadow-sm dark:border-white/10">
            <div class="relative px-6 py-7">
                <div class="absolute inset-x-0 bottom-0 h-1 bg-[#FFA500]"></div>
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-md bg-white p-3 shadow-sm">
                        <img src="{{ asset('images/micronet-logo.svg') }}" alt="Micronet" class="max-h-11 w-auto">
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-widest text-[#FFA500]">Read-only Jola</div>
                        <h3 class="mt-2 text-2xl font-semibold text-white">Synced Jola SIMs</h3>
                        <p class="mt-1 text-sm text-slate-200">Display-only SIM data synced from Mobile Manager and matched back to PSA where possible.</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-[#020f40]"><tr>
                    <th class="px-4 py-3 text-left text-white">Company</th><th class="px-4 py-3 text-left text-white">Mobile number</th><th class="px-4 py-3 text-left text-white">ICCID</th><th class="px-4 py-3 text-left text-white">Network</th><th class="px-4 py-3 text-left text-white">Tariff</th><th class="px-4 py-3 text-left text-white">Match</th><th class="px-4 py-3 text-left text-white">Last synced</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($sims as $sim)
                    <tr class="hover:bg-cyan-50/60 dark:hover:bg-gray-900/50"><td class="px-4 py-3">@if ($sim->company)<a href="{{ route('admin.companies.show', $sim->company) }}" class="font-medium text-[#020f40] hover:text-[#FFA500] hover:underline dark:text-cyan-200">{{ $sim->company->name }}</a>@else<span class="text-gray-500">No company</span>@endif</td><td class="px-4 py-3 font-medium text-[#020f40] dark:text-gray-100">{{ $sim->mobile_number ?? $sim->msisdn ?? '-' }}</td><td class="px-4 py-3 font-mono text-xs">{{ $sim->iccid ?? '-' }}</td><td class="px-4 py-3">{{ $sim->network ?? '-' }}</td><td class="px-4 py-3">{{ $sim->tariff ?? '-' }}</td><td class="px-4 py-3"><span class="inline-flex rounded-full {{ $sim->connectwise_addition_id ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-100' : 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/50 dark:text-cyan-100' }} px-2.5 py-1 text-xs font-medium">{{ $sim->connectwise_addition_id ? 'Matched to PSA' : 'Jola only' }}</span></td><td class="px-4 py-3">{{ $sim->last_synced_at?->format('d M Y H:i') ?? '-' }}</td></tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>
        <div class="mt-4">{{ $sims->links() }}</div>
    </div></div>
</x-app-layout>
