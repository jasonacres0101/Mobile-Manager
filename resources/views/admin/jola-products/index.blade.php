<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Jola Products</h2></x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="text-sm text-green-700 dark:text-green-300">{{ session('status') }}</div>
            @endif

            @if ($errors->has('jola_products'))
                <div class="text-sm text-red-700 dark:text-red-300">{{ $errors->first('jola_products') }}</div>
            @endif

            <section class="overflow-hidden rounded-lg border border-[#020f40]/10 bg-[#020f40] shadow-sm dark:border-white/10">
                <div class="relative px-6 py-7">
                    <div class="absolute inset-x-0 bottom-0 h-1 bg-[#FFA500]"></div>
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-md bg-white p-3 shadow-sm">
                                <img src="{{ asset('images/micronet-logo.svg') }}" alt="Micronet" class="max-h-11 w-auto">
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-[#FFA500]">Read-only Jola</div>
                                <h3 class="mt-2 text-2xl font-semibold text-white">Jola Products</h3>
                                <p class="mt-1 text-sm text-slate-200">Tariffs from Mobile Manager. These products are display-only and not linked to billing records.</p>
                            </div>
                        </div>
                <form method="POST" action="{{ route('admin.jola-products.sync') }}">
                    @csrf
                            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-[#FFA500] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[#020f40] shadow-sm transition hover:bg-[#ffb52e] focus:outline-none focus:ring-2 focus:ring-[#FFA500] focus:ring-offset-2 focus:ring-offset-[#020f40]">Sync Jola products</button>
                </form>
                    </div>
                </div>
            </section>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-[#020f40]">
                        <tr>
                            <th class="px-4 py-3 text-left text-white">ID</th>
                            <th class="px-4 py-3 text-left text-white">Name</th>
                            <th class="px-4 py-3 text-left text-white">Network</th>
                            <th class="px-4 py-3 text-left text-white">Type</th>
                            <th class="px-4 py-3 text-left text-white">Allowance</th>
                            <th class="px-4 py-3 text-left text-white">Cost</th>
                            <th class="px-4 py-3 text-left text-white">Status</th>
                            <th class="px-4 py-3 text-left text-white">Raw data</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($products as $product)
                            <tr class="hover:bg-cyan-50/60 dark:hover:bg-gray-900/50">
                                <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $product->mobilemanager_product_id }}</td>
                                <td class="px-4 py-3 font-medium text-[#020f40] dark:text-gray-100">{{ $product->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $product->network ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $product->type ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $product->allowance ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $product->monthly_cost !== null ? '£'.number_format((float) $product->monthly_cost, 2) : '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $product->status ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    <details>
                                        <summary class="cursor-pointer font-medium text-[#020f40] hover:text-[#FFA500] dark:text-cyan-200">View</summary>
                                        <pre class="mt-2 max-w-md overflow-x-auto rounded bg-gray-50 dark:bg-gray-900 p-3 text-xs">{{ json_encode($product->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No Jola products found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div>{{ $products->links() }}</div>
        </div>
    </div>
</x-app-layout>
