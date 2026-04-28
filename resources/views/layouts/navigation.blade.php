<nav x-data="{ open: false }" class="border-b border-[#020f40]/10 bg-white/95 shadow-sm dark:border-white/10 dark:bg-gray-900/95">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('admin.companies.index')" :active="request()->routeIs('admin.companies.*')">Companies</x-nav-link>
                        <x-nav-link :href="route('admin.agreements.index')" :active="request()->routeIs('admin.agreements.*')">Agreements</x-nav-link>
                        <x-nav-link :href="route('admin.sims.index')" :active="request()->routeIs('admin.sims.index')">SIMs</x-nav-link>
                        <div class="hidden sm:flex sm:items-center">
                            <x-dropdown align="left" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('admin.sims.jola') || request()->routeIs('admin.jola-customers.*') || request()->routeIs('admin.jola-products.*') ? 'border-[#FFA500] text-[#020f40] dark:text-white' : 'border-transparent text-slate-600 dark:text-gray-400 hover:text-[#020f40] dark:hover:text-white hover:border-[#FFA500]/70' }} text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out">
                                        <span>Jola</span>
                                        <svg class="ms-1 h-4 w-4 fill-current" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link :href="route('admin.sims.jola')">Jola SIMs</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.jola-customers.index')">Jola Customers</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.jola-products.index')">Jola Products</x-dropdown-link>
                                </x-slot>
                            </x-dropdown>
                        </div>
                        <x-nav-link :href="route('admin.invoices.index')" :active="request()->routeIs('admin.invoices.*')">Invoices</x-nav-link>
                        <x-nav-link :href="route('admin.payments.index')" :active="request()->routeIs('admin.payments.*')">Payments</x-nav-link>
                        <x-nav-link :href="route('admin.settings.edit')" :active="request()->routeIs('admin.settings.*')">Settings</x-nav-link>
                    @else
                        <x-nav-link :href="route('customer.sims.index')" :active="request()->routeIs('customer.sims.*')">My SIMs</x-nav-link>
                        <x-nav-link :href="route('customer.invoices.index')" :active="request()->routeIs('customer.invoices.*')">My Invoices</x-nav-link>
                        <x-nav-link :href="route('customer.direct-debit.setup')" :active="request()->routeIs('customer.direct-debit.*')">Direct Debit</x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center rounded-md border border-[#020f40]/10 bg-slate-50 px-3 py-2 text-sm font-medium leading-4 text-[#020f40] transition hover:border-[#FFA500] hover:bg-orange-50 focus:outline-none dark:border-white/10 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-[#020f40] transition hover:bg-orange-50 hover:text-[#FFA500] focus:bg-orange-50 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-800">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            @if (Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('admin.companies.index')" :active="request()->routeIs('admin.companies.*')">Companies</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.agreements.index')" :active="request()->routeIs('admin.agreements.*')">Agreements</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.sims.index')" :active="request()->routeIs('admin.sims.index')">SIMs</x-responsive-nav-link>
                <div class="px-4 pt-3 text-xs font-semibold uppercase tracking-wide text-[#020f40] dark:text-orange-200">Jola</div>
                <x-responsive-nav-link :href="route('admin.sims.jola')" :active="request()->routeIs('admin.sims.jola')">Jola SIMs</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.jola-customers.index')" :active="request()->routeIs('admin.jola-customers.*')">Jola Customers</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.jola-products.index')" :active="request()->routeIs('admin.jola-products.*')">Jola Products</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.invoices.index')" :active="request()->routeIs('admin.invoices.*')">Invoices</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.payments.index')" :active="request()->routeIs('admin.payments.*')">Payments</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.settings.edit')" :active="request()->routeIs('admin.settings.*')">Settings</x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('customer.sims.index')" :active="request()->routeIs('customer.sims.*')">My SIMs</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('customer.invoices.index')" :active="request()->routeIs('customer.invoices.*')">My Invoices</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('customer.direct-debit.setup')" :active="request()->routeIs('customer.direct-debit.*')">Direct Debit</x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="border-t border-[#020f40]/10 pt-4 pb-1 dark:border-white/10">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
