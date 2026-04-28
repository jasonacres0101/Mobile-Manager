@php
    $field = function (array $keys, $fallback = '-') use ($liveSim) {
        foreach ($keys as $key) {
            $value = data_get($liveSim, $key);

            if ($value !== null && $value !== '') {
                if (is_bool($value)) {
                    return $value ? 'Yes' : 'No';
                }

                return is_scalar($value) ? (string) $value : json_encode($value);
            }
        }

        return $fallback;
    };

    $allowance = function ($value) {
        if ($value === null || $value === '' || $value === '-') {
            return '-';
        }

        if (! is_numeric($value)) {
            return (string) $value;
        }

        $kilobytes = (float) $value;

        if ($kilobytes >= 1048576) {
            return rtrim(rtrim(number_format($kilobytes / 1048576, 2), '0'), '.').' GB';
        }

        if ($kilobytes >= 1024) {
            return rtrim(rtrim(number_format($kilobytes / 1024, 2), '0'), '.').' MB';
        }

        return number_format($kilobytes, 0).' KB';
    };

    $dateTime = function ($value) {
        if ($value === null || $value === '' || $value === '-') {
            return '-';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    };

    $mobile = $field(['Msisdn', 'MSISDN', 'msisdn', 'MobileNumber', 'mobileNumber']);
    $iccid = $field(['Iccid', 'ICCID', 'iccid', 'IccId', 'iccId']);
    $network = $field(['Operator', 'operator', 'Network.Name', 'network.name', 'Network', 'network']);
    $tariff = $field(['Tariff.Name', 'tariff.name', 'Tariff', 'tariff']);
    $status = $field(['State', 'state', 'Status.Name', 'status.name', 'Status', 'status']);
    $customerName = $customer->name ?? data_get($customer->raw_data, 'Name', 'Jola customer');
    $simAllowance = $allowance($field(['Allownace', 'Allowance', 'allowance'], null));
    $tariffAllowance = $allowance($field(['TariffAllowance', 'tariffAllowance'], null));
    $usage = $allowance($field(['Usage', 'usage'], null));
    $usagePercent = $field(['AllowanceUsedPercent', 'allowanceUsedPercent'], '0');
    $cdrUrl = $cdrExportFolder && $mobile !== '-'
        ? 'https://s3-eu-west-1.amazonaws.com/jolacdr/'.$cdrExportFolder.'/'.$mobile.'-monthly-'.now()->year.'-'.now()->month.'.csv'
        : null;
    $lastSeenNetworkCode = $field(['LastSeenNetwork', 'lastSeenNetwork'], null);
    $lastSeenNetwork = \App\Models\MobileNetwork::lookup($lastSeenNetworkCode);
    [$lastSeenMcc, $lastSeenMnc, $lastSeenPlmn] = \App\Models\MobileNetwork::splitMccMnc($lastSeenNetworkCode);
    $lastSeenCountryCode = $field(['LastSeenCountry', 'lastSeenCountry'], null);
    $lastSeenCountryNetwork = \App\Models\MobileNetwork::lookup($lastSeenCountryCode);
    $lastSeenDisplay = $lastSeenNetwork
        ? "{$lastSeenNetwork->name} ({$lastSeenNetwork->plmn})"
        : ($lastSeenNetworkCode ?: '-');
    $lastSeenCountryDisplay = $lastSeenCountryNetwork
        ? "{$lastSeenCountryNetwork->country} / {$lastSeenCountryNetwork->name} ({$lastSeenCountryNetwork->tadig})"
        : ($lastSeenCountryCode ?: '-');

    $sections = [
        'Identifiers' => [
            'Jola SIM ID' => $jolaSimId,
            'Mobile number' => $mobile,
            'ICCID' => $iccid,
            'IMSI' => $field(['Imsi', 'IMSI', 'imsi']),
            'IMEI' => $field(['IMEI', 'Imei', 'imei']),
        ],
        'Service' => [
            'Network' => $network,
            'Tariff' => $tariff,
            'Status' => $status,
            'Barred' => $field(['Barred', 'barred']),
            'Activated date' => $dateTime($field(['ActivatedDate', 'activatedDate', 'ActivationDate', 'activationDate', 'ActivatedAt', 'activatedAt'], null)),
        ],
        'Customer' => [
            'Jola customer' => $customerName,
            'Jola customer ID' => $customer->mobilemanager_customer_id,
            'SIM customer ID' => $field(['CustomerId', 'customerId']),
            'Account number' => $customer->account_number ?? '-',
        ],
        'Usage' => [
            'SIM allowance' => $simAllowance,
            'Tariff allowance' => $tariffAllowance,
            'Usage' => $usage,
            'Usage percent' => $usagePercent.'%',
            'Bolt-on allowance' => $allowance($field(['BoltonAllowance', 'boltonAllowance'], null)),
            'Has bolt-ons' => $field(['HasBoltons', 'hasBoltons']),
        ],
        'Last seen' => [
            'Country / TADIG' => $lastSeenCountryDisplay,
            'Network' => $lastSeenDisplay,
            'MCC' => $lastSeenMcc ?? '-',
            'MNC' => $lastSeenMnc ?? '-',
            'PLMN' => $lastSeenPlmn ?? '-',
            'Seen at' => $dateTime($field(['LastSeenAt', 'lastSeenAt'], null)),
        ],
        'Contact' => [
            'Contact email' => $field(['ContactEmail', 'contactEmail']),
            'Contact mobile' => $field(['ContactMobileNumber', 'contactMobileNumber']),
            'SIM tag' => $field(['SimTag', 'simTag']),
        ],
        'eSIM' => [
            'QR payload' => $field(['QRPayload', 'qrPayload']),
        ],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Jola SIM Details</h2>
            <a href="{{ route('admin.jola-customers.show', $customer) }}" class="text-sm text-indigo-600 dark:text-indigo-300 hover:underline">Back to Jola customer</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($error)
                <div class="text-sm text-amber-700 dark:text-amber-300">{{ $error }}</div>
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
                                <div class="text-xs font-semibold uppercase tracking-widest text-[#FFA500]">Read-only Jola SIM</div>
                                <h3 class="mt-2 text-2xl font-semibold text-white">{{ $mobile }}</h3>
                                <p class="mt-1 text-sm text-slate-200">{{ $jolaSimId }}</p>
                            </div>
                        </div>
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        @if ($cdrUrl)
                                <a href="{{ $cdrUrl }}" class="inline-flex items-center rounded-md bg-[#FFA500] px-3 py-2 font-semibold text-[#020f40] hover:bg-[#ffb52e]" target="_blank" rel="noopener">Download CDR</a>
                        @endif
                            <span class="rounded-md bg-white/10 px-3 py-2 font-medium text-white">{{ $status }}</span>
                            <span class="rounded-md bg-white/10 px-3 py-2 font-medium text-white">Read-only Jola data</span>
                        </div>
                    </div>
                </div>
            </section>
                @unless ($cdrUrl)
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Add the Jola CDR export folder ID in Settings to enable monthly CDR downloads.</p>
                @endunless

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-[#020f40]/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Network</div>
                    <div class="mt-2 text-2xl font-semibold text-[#020f40] dark:text-gray-100">{{ $network }}</div>
                </div>
                <div class="rounded-lg border border-cyan-100 bg-cyan-50 p-5 shadow-sm dark:border-cyan-900/60 dark:bg-cyan-950/20">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Tariff</div>
                    <div class="mt-2 text-2xl font-semibold text-[#020f40] dark:text-cyan-100">{{ $tariff }}</div>
                </div>
                <div class="rounded-lg border border-[#020f40]/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">ICCID</div>
                    <div class="mt-2 break-words text-xl font-semibold text-[#020f40] dark:text-gray-100">{{ $iccid }}</div>
                </div>
                <div class="rounded-lg border border-orange-100 bg-orange-50 p-5 shadow-sm dark:border-orange-900/60 dark:bg-orange-950/20">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Customer</div>
                    <div class="mt-2 text-2xl font-semibold text-[#020f40] dark:text-orange-100">{{ $customerName }}</div>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-[#020f40]/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Usage</div>
                    <div class="mt-2 text-2xl font-semibold text-[#020f40] dark:text-gray-100">{{ $usage }}</div>
                </div>
                <div class="rounded-lg border border-cyan-100 bg-cyan-50 p-5 shadow-sm dark:border-cyan-900/60 dark:bg-cyan-950/20">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Tariff allowance</div>
                    <div class="mt-2 text-2xl font-semibold text-[#020f40] dark:text-cyan-100">{{ $tariffAllowance }}</div>
                </div>
                <div class="rounded-lg border border-orange-100 bg-orange-50 p-5 shadow-sm dark:border-orange-900/60 dark:bg-orange-950/20">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Usage percent</div>
                    <div class="mt-2 text-2xl font-semibold text-[#020f40] dark:text-orange-100">{{ $usagePercent }}%</div>
                </div>
                <div class="rounded-lg border border-[#020f40]/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Last seen</div>
                    <div class="mt-2 text-2xl font-semibold text-[#020f40] dark:text-gray-100">{{ $dateTime($field(['LastSeenAt', 'lastSeenAt'], null)) }}</div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                @foreach ($sections as $title => $items)
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="text-base font-semibold text-[#020f40] dark:text-gray-100">{{ $title }}</h3>
                        <dl class="mt-4 space-y-4 text-sm">
                            @foreach ($items as $label => $value)
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                                    <dd class="mt-1 font-medium text-gray-900 dark:text-gray-100 break-words">{{ $value ?: '-' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endforeach
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <details>
                    <summary class="cursor-pointer text-sm font-medium text-[#020f40] hover:text-[#FFA500] dark:text-cyan-200">Troubleshooting data</summary>
                    <pre class="mt-4 max-h-[28rem] overflow-auto rounded bg-gray-50 dark:bg-gray-900 p-4 text-xs text-gray-700 dark:text-gray-300">{{ $liveSim ? json_encode($liveSim, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : 'Not available' }}</pre>
                </details>
            </div>
        </div>
    </div>
</x-app-layout>
