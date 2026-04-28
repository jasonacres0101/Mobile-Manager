<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Settings</h2></x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 text-sm text-green-700 dark:text-green-300">{{ session('status') }}</div>
            @endif

            <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex gap-6" aria-label="Settings sections">
                    @foreach ([
                        'gocardless' => 'GoCardless',
                        'connectwise' => 'ConnectWise PSA',
                        'jola' => 'Jola',
                        'microsoft365' => 'Microsoft 365',
                        'help' => 'Help',
                        'users' => 'Users',
                    ] as $tab => $label)
                        <a href="{{ route('admin.settings.edit', ['tab' => $tab]) }}" class="border-b-2 px-1 pb-3 text-sm font-medium {{ $activeTab === $tab ? 'border-indigo-500 text-indigo-600 dark:text-indigo-300' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">{{ $label }}</a>
                    @endforeach
                </nav>
            </div>

            @if ($errors->has('welcome_email'))
                <div class="mb-4 text-sm text-red-700 dark:text-red-300">{{ $errors->first('welcome_email') }}</div>
            @endif

            @if ($errors->has('welcome_email_test'))
                <div class="mb-4 text-sm text-red-700 dark:text-red-300">{{ $errors->first('welcome_email_test') }}</div>
            @endif

            @if ($errors->has('connectwise_test'))
                <div class="mb-4 text-sm text-red-700 dark:text-red-300">{{ $errors->first('connectwise_test') }}</div>
            @endif

            @if ($errors->has('connectwise_sync'))
                <div class="mb-4 text-sm text-red-700 dark:text-red-300">{{ $errors->first('connectwise_sync') }}</div>
            @endif

            @if ($errors->has('mobilemanager_test'))
                <div class="mb-4 text-sm text-red-700 dark:text-red-300">{{ $errors->first('mobilemanager_test') }}</div>
            @endif

            @if ($errors->has('microsoft365_test'))
                <div class="mb-4 text-sm text-red-700 dark:text-red-300">{{ $errors->first('microsoft365_test') }}</div>
            @endif

            @if ($activeTab === 'gocardless')
                <form method="POST" action="{{ route('admin.settings.gocardless.update') }}" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="gocardless_environment" value="Environment" />
                        <select id="gocardless_environment" name="gocardless_environment" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="sandbox" @selected(old('gocardless_environment', $gocardlessEnvironment) === 'sandbox')>Sandbox</option>
                            <option value="live" @selected(old('gocardless_environment', $gocardlessEnvironment) === 'live')>Live</option>
                        </select>
                        <x-input-error :messages="$errors->get('gocardless_environment')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="gocardless_access_token" value="Access token" />
                        <x-text-input id="gocardless_access_token" name="gocardless_access_token" type="password" class="mt-1 block w-full" autocomplete="new-password" placeholder="{{ $hasAccessToken ? 'Saved - leave blank to keep current token' : 'Paste GoCardless access token' }}" />
                        <x-input-error :messages="$errors->get('gocardless_access_token')" class="mt-2" />
                        @if ($hasAccessToken)
                            <label class="mt-3 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <input type="checkbox" name="clear_access_token" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                Clear saved access token
                            </label>
                        @endif
                    </div>

                    <div>
                        <x-input-label for="gocardless_webhook_secret" value="Webhook secret" />
                        <x-text-input id="gocardless_webhook_secret" name="gocardless_webhook_secret" type="password" class="mt-1 block w-full" autocomplete="new-password" placeholder="{{ $hasWebhookSecret ? 'Saved - leave blank to keep current secret' : 'Paste GoCardless webhook secret' }}" />
                        <x-input-error :messages="$errors->get('gocardless_webhook_secret')" class="mt-2" />
                        @if ($hasWebhookSecret)
                            <label class="mt-3 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <input type="checkbox" name="clear_webhook_secret" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                Clear saved webhook secret
                            </label>
                        @endif
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>Save GoCardless</x-primary-button>
                    </div>
                </form>
            @elseif ($activeTab === 'connectwise')
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Connection status</h3>
                                @if ($connectwiseLastTestStatus === 'success')
                                    <p class="mt-1 text-sm font-medium text-green-700 dark:text-green-300">Successful</p>
                                @elseif ($connectwiseLastTestStatus === 'failed')
                                    <p class="mt-1 text-sm font-medium text-red-700 dark:text-red-300">Failed</p>
                                @else
                                    <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">Not tested yet</p>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('admin.settings.connectwise.test') }}">
                                @csrf
                                <x-primary-button>Test PSA connection</x-primary-button>
                            </form>
                        </div>

                        @if ($connectwiseLastTestMessage)
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $connectwiseLastTestMessage }}</p>
                        @endif

                        @if ($connectwiseLastTestedAt)
                            <p class="text-xs text-gray-500 dark:text-gray-500">Last tested {{ \Illuminate\Support\Carbon::parse($connectwiseLastTestedAt)->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Manual sync</h3>
                            @if ($connectwiseLastManualSyncStatus === 'success')
                                <p class="mt-1 text-sm font-medium text-green-700 dark:text-green-300">Last sync completed successfully</p>
                            @elseif ($connectwiseLastManualSyncStatus === 'failed')
                                <p class="mt-1 text-sm font-medium text-red-700 dark:text-red-300">Last sync failed</p>
                            @else
                                <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">No manual sync run yet</p>
                            @endif
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <form method="POST" action="{{ route('admin.settings.connectwise.sync') }}">
                                @csrf
                                <input type="hidden" name="sync_type" value="agreements">
                                <x-primary-button>Sync SIM agreements</x-primary-button>
                            </form>
                            <form method="POST" action="{{ route('admin.settings.connectwise.sync') }}">
                                @csrf
                                <input type="hidden" name="sync_type" value="invoices">
                                <x-secondary-button>Sync invoices</x-secondary-button>
                            </form>
                        </div>

                        <p class="text-sm text-gray-600 dark:text-gray-400">The SIM agreement sync still only pulls configured SIM agreement type IDs. Manual buttons run immediately; scheduled syncs continue to use queued jobs.</p>

                        @if ($connectwiseLastManualSyncMessage)
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $connectwiseLastManualSyncMessage }}</p>
                        @endif

                        @if ($connectwiseLastManualSyncedAt)
                            <p class="text-xs text-gray-500 dark:text-gray-500">Last manual sync {{ \Illuminate\Support\Carbon::parse($connectwiseLastManualSyncedAt)->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.settings.connectwise.update') }}" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="connectwise_base_url" value="Base URL" />
                            <x-text-input id="connectwise_base_url" name="connectwise_base_url" type="url" class="mt-1 block w-full" value="{{ old('connectwise_base_url', $connectwiseBaseUrl) }}" />
                            <x-input-error :messages="$errors->get('connectwise_base_url')" class="mt-2" />
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <x-input-label for="connectwise_company_id" value="Company ID" />
                                <x-text-input id="connectwise_company_id" name="connectwise_company_id" type="text" class="mt-1 block w-full" value="{{ old('connectwise_company_id', $connectwiseCompanyId) }}" />
                                <x-input-error :messages="$errors->get('connectwise_company_id')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="connectwise_public_key" value="Public key" />
                                <x-text-input id="connectwise_public_key" name="connectwise_public_key" type="text" class="mt-1 block w-full" value="{{ old('connectwise_public_key', $connectwisePublicKey) }}" />
                                <x-input-error :messages="$errors->get('connectwise_public_key')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <x-input-label for="connectwise_private_key" value="Private key" />
                                <x-text-input id="connectwise_private_key" name="connectwise_private_key" type="password" class="mt-1 block w-full" autocomplete="new-password" placeholder="{{ $hasConnectWisePrivateKey ? 'Saved - leave blank to keep current key' : 'Paste private key' }}" />
                                <x-input-error :messages="$errors->get('connectwise_private_key')" class="mt-2" />
                                @if ($hasConnectWisePrivateKey)
                                    <label class="mt-3 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <input type="checkbox" name="clear_connectwise_private_key" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        Clear saved private key
                                    </label>
                                @endif
                            </div>
                            <div>
                                <x-input-label for="connectwise_client_id" value="Client ID" />
                                <x-text-input id="connectwise_client_id" name="connectwise_client_id" type="password" class="mt-1 block w-full" autocomplete="new-password" placeholder="{{ $hasConnectWiseClientId ? 'Saved - leave blank to keep current client ID' : 'Paste client ID' }}" />
                                <x-input-error :messages="$errors->get('connectwise_client_id')" class="mt-2" />
                                @if ($hasConnectWiseClientId)
                                    <label class="mt-3 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <input type="checkbox" name="clear_connectwise_client_id" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        Clear saved client ID
                                    </label>
                                @endif
                            </div>
                        </div>

                        <div>
                            <x-input-label for="connectwise_sim_agreement_type_ids" value="SIM agreement type IDs" />
                            <x-text-input id="connectwise_sim_agreement_type_ids" name="connectwise_sim_agreement_type_ids" type="text" class="mt-1 block w-full" value="{{ old('connectwise_sim_agreement_type_ids', $connectwiseSimAgreementTypeIds) }}" placeholder="12,18" />
                            <x-input-error :messages="$errors->get('connectwise_sim_agreement_type_ids')" class="mt-2" />
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button>Save ConnectWise</x-primary-button>
                        </div>
                    </form>
                </div>
            @elseif ($activeTab === 'jola')
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Connection status</h3>
                                @if ($mobileManagerLastTestStatus === 'success')
                                    <p class="mt-1 text-sm font-medium text-green-700 dark:text-green-300">Successful</p>
                                @elseif ($mobileManagerLastTestStatus === 'failed')
                                    <p class="mt-1 text-sm font-medium text-red-700 dark:text-red-300">Failed</p>
                                @else
                                    <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">Not tested yet</p>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('admin.settings.jola.test') }}">
                                @csrf
                                <x-primary-button>Test Jola connection</x-primary-button>
                            </form>
                        </div>

                        @if ($mobileManagerLastTestMessage)
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $mobileManagerLastTestMessage }}</p>
                        @endif

                        @if ($mobileManagerLastTestedAt)
                            <p class="text-xs text-gray-500 dark:text-gray-500">Last tested {{ \Illuminate\Support\Carbon::parse($mobileManagerLastTestedAt)->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.settings.jola.update') }}" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="mobilemanager_base_url" value="Base URL" />
                            <x-text-input id="mobilemanager_base_url" name="mobilemanager_base_url" type="url" class="mt-1 block w-full" value="{{ old('mobilemanager_base_url', $mobileManagerBaseUrl) }}" />
                            <x-input-error :messages="$errors->get('mobilemanager_base_url')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="mobilemanager_cdr_export_folder" value="CDR export folder ID" />
                            <x-text-input id="mobilemanager_cdr_export_folder" name="mobilemanager_cdr_export_folder" type="text" class="mt-1 block w-full" value="{{ old('mobilemanager_cdr_export_folder', $mobileManagerCdrExportFolder) }}" placeholder="9919ccca-791f-4949-a284-0839f6837122" />
                            <x-input-error :messages="$errors->get('mobilemanager_cdr_export_folder')" class="mt-2" />
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <x-input-label for="mobilemanager_api_key" value="API key" />
                                <x-text-input id="mobilemanager_api_key" name="mobilemanager_api_key" type="password" class="mt-1 block w-full" autocomplete="new-password" placeholder="{{ $hasMobileManagerApiKey ? 'Saved - leave blank to keep current key' : 'Paste API key' }}" />
                                <x-input-error :messages="$errors->get('mobilemanager_api_key')" class="mt-2" />
                                @if ($hasMobileManagerApiKey)
                                    <label class="mt-3 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <input type="checkbox" name="clear_mobilemanager_api_key" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        Clear saved API key
                                    </label>
                                @endif
                            </div>
                            <div>
                                <x-input-label for="mobilemanager_api_secret" value="API secret" />
                                <x-text-input id="mobilemanager_api_secret" name="mobilemanager_api_secret" type="password" class="mt-1 block w-full" autocomplete="new-password" placeholder="{{ $hasMobileManagerApiSecret ? 'Saved - leave blank to keep current secret' : 'Paste API secret' }}" />
                                <x-input-error :messages="$errors->get('mobilemanager_api_secret')" class="mt-2" />
                                @if ($hasMobileManagerApiSecret)
                                    <label class="mt-3 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <input type="checkbox" name="clear_mobilemanager_api_secret" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        Clear saved API secret
                                    </label>
                                @endif
                            </div>
                        </div>

                        <p class="text-sm text-gray-600 dark:text-gray-400">The test only performs a read-only GET request to Jola and does not change SIMs, orders, tariffs, bars, or provisioning state.</p>

                        <div class="flex justify-end">
                            <x-primary-button>Save Jola</x-primary-button>
                        </div>
                    </form>
                </div>
            @elseif ($activeTab === 'microsoft365')
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Connection status</h3>
                                @if ($microsoft365LastTestStatus === 'success')
                                    <p class="mt-1 text-sm font-medium text-green-700 dark:text-green-300">Successful</p>
                                @elseif ($microsoft365LastTestStatus === 'failed')
                                    <p class="mt-1 text-sm font-medium text-red-700 dark:text-red-300">Failed</p>
                                @else
                                    <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">Not tested yet</p>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('admin.settings.microsoft365.test') }}">
                                @csrf
                                <x-primary-button>Send test email</x-primary-button>
                            </form>
                        </div>

                        @if ($microsoft365LastTestMessage)
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $microsoft365LastTestMessage }}</p>
                        @endif

                        @if ($microsoft365LastTestedAt)
                            <p class="text-xs text-gray-500 dark:text-gray-500">Last tested {{ \Illuminate\Support\Carbon::parse($microsoft365LastTestedAt)->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.settings.microsoft365.update') }}" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="microsoft365_tenant_id" value="Tenant ID" />
                            <x-text-input id="microsoft365_tenant_id" name="microsoft365_tenant_id" type="text" class="mt-1 block w-full" value="{{ old('microsoft365_tenant_id', $microsoft365TenantId) }}" placeholder="Directory tenant ID or domain" />
                            <x-input-error :messages="$errors->get('microsoft365_tenant_id')" class="mt-2" />
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <x-input-label for="microsoft365_client_id" value="Application client ID" />
                                <x-text-input id="microsoft365_client_id" name="microsoft365_client_id" type="text" class="mt-1 block w-full" value="{{ old('microsoft365_client_id', $microsoft365ClientId) }}" />
                                <x-input-error :messages="$errors->get('microsoft365_client_id')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="microsoft365_sender_email" value="Sender email" />
                                <x-text-input id="microsoft365_sender_email" name="microsoft365_sender_email" type="email" class="mt-1 block w-full" value="{{ old('microsoft365_sender_email', $microsoft365SenderEmail) }}" placeholder="billing@example.com" />
                                <x-input-error :messages="$errors->get('microsoft365_sender_email')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="microsoft365_client_secret" value="Client secret" />
                            <x-text-input id="microsoft365_client_secret" name="microsoft365_client_secret" type="password" class="mt-1 block w-full" autocomplete="new-password" placeholder="{{ $hasMicrosoft365ClientSecret ? 'Saved - leave blank to keep current secret' : 'Paste client secret' }}" />
                            <x-input-error :messages="$errors->get('microsoft365_client_secret')" class="mt-2" />
                            @if ($hasMicrosoft365ClientSecret)
                                <label class="mt-3 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <input type="checkbox" name="clear_microsoft365_client_secret" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    Clear saved client secret
                                </label>
                            @endif
                        </div>

                        <div class="rounded-md bg-gray-50 dark:bg-gray-900 p-4 text-sm text-gray-600 dark:text-gray-400">
                            Use an Azure app registration with Microsoft Graph application permission <span class="font-medium text-gray-900 dark:text-gray-100">Mail.Send</span> and admin consent granted. This uses OAuth client credentials, not basic SMTP passwords.
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button>Save Microsoft 365</x-primary-button>
                        </div>
                    </form>
                </div>
            @elseif ($activeTab === 'help')
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-6">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Office 365 email setup</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Use Microsoft Graph modern authentication. Do not use SMTP basic authentication or a mailbox password.</p>
                        </div>

                        <div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">1. Create an app registration</h4>
                                <ol class="mt-2 list-decimal space-y-1 pl-5">
                                    <li>Open Microsoft Entra admin center.</li>
                                    <li>If Microsoft asks what you want to do, choose <span class="font-medium text-gray-900 dark:text-gray-100">Register an application to integrate with Microsoft Entra ID (App you're developing)</span>.</li>
                                    <li>Go to Identity, Applications, App registrations.</li>
                                    <li>Create a new registration for this portal.</li>
                                    <li>Use single tenant unless your Microsoft 365 admin specifically needs multi-tenant access.</li>
                                </ol>
                                <p class="mt-2 text-gray-600 dark:text-gray-400">Do not choose Application Proxy or Non-gallery application. Those are for different types of setup and will not give this portal the Graph API client credentials it needs.</p>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">2. Copy these values into Settings</h4>
                                <dl class="mt-2 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-md bg-gray-50 dark:bg-gray-900 p-3">
                                        <dt class="font-medium text-gray-900 dark:text-gray-100">Tenant ID</dt>
                                        <dd class="mt-1 text-gray-600 dark:text-gray-400">Directory tenant ID from the app overview page.</dd>
                                    </div>
                                    <div class="rounded-md bg-gray-50 dark:bg-gray-900 p-3">
                                        <dt class="font-medium text-gray-900 dark:text-gray-100">Application client ID</dt>
                                        <dd class="mt-1 text-gray-600 dark:text-gray-400">Application client ID from the app overview page.</dd>
                                    </div>
                                    <div class="rounded-md bg-gray-50 dark:bg-gray-900 p-3">
                                        <dt class="font-medium text-gray-900 dark:text-gray-100">Client secret</dt>
                                        <dd class="mt-1 text-gray-600 dark:text-gray-400">Create one under Certificates and secrets, then copy the secret value.</dd>
                                    </div>
                                    <div class="rounded-md bg-gray-50 dark:bg-gray-900 p-3">
                                        <dt class="font-medium text-gray-900 dark:text-gray-100">Sender email</dt>
                                        <dd class="mt-1 text-gray-600 dark:text-gray-400">The licensed mailbox address the portal should send from.</dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">3. Add Microsoft Graph permission</h4>
                                <ol class="mt-2 list-decimal space-y-1 pl-5">
                                    <li>In the app registration, open API permissions.</li>
                                    <li>Select Add a permission.</li>
                                    <li>Select Microsoft Graph.</li>
                                    <li>Select Application permissions.</li>
                                    <li>Add <span class="font-medium text-gray-900 dark:text-gray-100">Mail.Send</span>.</li>
                                    <li>Select Grant admin consent for your tenant.</li>
                                </ol>
                            </div>

                            <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-100">
                                <h4 class="font-semibold">Permission warning</h4>
                                <p class="mt-1">Microsoft Graph <span class="font-medium">Mail.Send</span> application permission can send as users in the tenant. For tighter security, ask your Microsoft 365 admin to restrict the app to the sender mailbox with an Exchange application access policy.</p>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">4. Test the flow</h4>
                                <ol class="mt-2 list-decimal space-y-1 pl-5">
                                    <li>Save the details in the Microsoft 365 tab.</li>
                                    <li>Open the Users tab.</li>
                                    <li>Create a customer user or use Send welcome on an existing user.</li>
                                    <li>The client receives a welcome email with a password setup link.</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-6">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">ConnectWise PSA setup</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">ConnectWise PSA is the source of truth for companies, SIM agreements, additions, and invoices. This portal only syncs configured SIM agreement types.</p>
                        </div>

                        <div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">1. Create or choose an API member</h4>
                                <ol class="mt-2 list-decimal space-y-1 pl-5">
                                    <li>Open ConnectWise PSA as an administrator.</li>
                                    <li>Go to System, Members, API Members.</li>
                                    <li>Create a dedicated API member for this portal.</li>
                                    <li>Generate API keys for that API member.</li>
                                </ol>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">2. Copy these values into the ConnectWise PSA tab</h4>
                                <dl class="mt-2 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-md bg-gray-50 dark:bg-gray-900 p-3">
                                        <dt class="font-medium text-gray-900 dark:text-gray-100">Base URL</dt>
                                        <dd class="mt-1 text-gray-600 dark:text-gray-400">Your ConnectWise API URL, for example https://api-na.myconnectwise.net/v4_6_release/apis/3.0.</dd>
                                    </div>
                                    <div class="rounded-md bg-gray-50 dark:bg-gray-900 p-3">
                                        <dt class="font-medium text-gray-900 dark:text-gray-100">Company ID</dt>
                                        <dd class="mt-1 text-gray-600 dark:text-gray-400">Your ConnectWise company identifier used for API login.</dd>
                                    </div>
                                    <div class="rounded-md bg-gray-50 dark:bg-gray-900 p-3">
                                        <dt class="font-medium text-gray-900 dark:text-gray-100">Public key</dt>
                                        <dd class="mt-1 text-gray-600 dark:text-gray-400">The public key generated for the API member.</dd>
                                    </div>
                                    <div class="rounded-md bg-gray-50 dark:bg-gray-900 p-3">
                                        <dt class="font-medium text-gray-900 dark:text-gray-100">Private key</dt>
                                        <dd class="mt-1 text-gray-600 dark:text-gray-400">The private key generated for the API member. This is encrypted when saved.</dd>
                                    </div>
                                    <div class="rounded-md bg-gray-50 dark:bg-gray-900 p-3">
                                        <dt class="font-medium text-gray-900 dark:text-gray-100">Client ID</dt>
                                        <dd class="mt-1 text-gray-600 dark:text-gray-400">The ConnectWise client ID required in the API request headers.</dd>
                                    </div>
                                    <div class="rounded-md bg-gray-50 dark:bg-gray-900 p-3">
                                        <dt class="font-medium text-gray-900 dark:text-gray-100">SIM agreement type IDs</dt>
                                        <dd class="mt-1 text-gray-600 dark:text-gray-400">Comma-separated agreement type IDs that represent SIM agreements, for example 12,18.</dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">3. Find the SIM agreement type IDs</h4>
                                <ol class="mt-2 list-decimal space-y-1 pl-5">
                                    <li>In ConnectWise PSA, open Setup Tables.</li>
                                    <li>Find the Agreement Types setup table.</li>
                                    <li>Open the agreement type used for SIM or airtime billing.</li>
                                    <li>Copy the type ID and add it to the SIM agreement type IDs field.</li>
                                </ol>
                            </div>

                            <div class="rounded-md border border-red-200 bg-red-50 p-4 text-red-900 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-100">
                                <h4 class="font-semibold">Critical sync rule</h4>
                                <p class="mt-1">Only add agreement type IDs that are genuinely for SIM agreements. The portal builds a ConnectWise condition like <span class="font-medium">type/id=12 OR type/id=18</span> and must never sync all agreements.</p>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">4. Run or schedule sync</h4>
                                <ol class="mt-2 list-decimal space-y-1 pl-5">
                                    <li>Save the ConnectWise PSA settings.</li>
                                    <li>Run <span class="font-medium text-gray-900 dark:text-gray-100">php artisan sync:connectwise-sim-agreements</span> to pull SIM agreements, additions, and invoices.</li>
                                    <li>Run <span class="font-medium text-gray-900 dark:text-gray-100">php artisan sync:connectwise-invoices</span> to refresh invoices for existing SIM agreements only.</li>
                                    <li>The scheduler runs agreements hourly and invoices every 30 minutes.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="space-y-6">
                    <div class="overflow-hidden rounded-lg border border-[#020f40]/10 bg-white shadow-sm dark:border-white/10 dark:bg-gray-800">
                        <div class="relative bg-[#020f40] px-5 py-5">
                            <div class="absolute inset-x-0 bottom-0 h-1 bg-[#FFA500]"></div>
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-md bg-white p-2 shadow-sm">
                                        <img src="{{ asset('images/micronet-logo.svg') }}" alt="Micronet" class="max-h-10 w-auto">
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-white">User Management</h3>
                                        <p class="mt-1 text-sm text-slate-200">Create portal users, assign roles, and send branded welcome emails.</p>
                                    </div>
                                </div>
                                <a href="{{ route('admin.settings.edit', ['tab' => 'users']) }}" class="inline-flex items-center justify-center rounded-md bg-[#FFA500] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[#020f40] shadow-sm transition hover:bg-[#ffb52e] focus:outline-none focus:ring-2 focus:ring-[#FFA500] focus:ring-offset-2 focus:ring-offset-[#020f40]">Add user</a>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-lg border border-[#020f40]/10 bg-[#020f40] p-4 shadow-sm dark:border-white/10">
                            <div class="text-sm font-medium text-slate-200">All users</div>
                            <div class="mt-2 text-3xl font-semibold text-white">{{ $userTotalCount }}</div>
                            <div class="mt-3 h-1.5 rounded-full bg-white/15">
                                <div class="h-1.5 w-2/3 rounded-full bg-[#FFA500]"></div>
                            </div>
                        </div>
                        <div class="rounded-lg border border-cyan-100 bg-cyan-50 p-4 shadow-sm dark:border-cyan-900/60 dark:bg-cyan-950/30">
                            <div class="text-sm font-medium text-cyan-900 dark:text-cyan-200">Customer users</div>
                            <div class="mt-2 text-3xl font-semibold text-[#020f40] dark:text-cyan-100">{{ $customerUserCount }}</div>
                            <div class="mt-3 h-1.5 rounded-full bg-cyan-100 dark:bg-cyan-900">
                                <div class="h-1.5 w-1/2 rounded-full bg-cyan-500"></div>
                            </div>
                        </div>
                        <div class="rounded-lg border border-orange-100 bg-orange-50 p-4 shadow-sm dark:border-orange-900/60 dark:bg-orange-950/30">
                            <div class="text-sm font-medium text-orange-900 dark:text-orange-200">Admin users</div>
                            <div class="mt-2 text-3xl font-semibold text-[#020f40] dark:text-orange-100">{{ $adminUserCount }}</div>
                            <div class="mt-3 h-1.5 rounded-full bg-orange-100 dark:bg-orange-900">
                                <div class="h-1.5 w-1/3 rounded-full bg-[#FFA500]"></div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="border-b border-gray-200 bg-slate-50 px-5 py-4 dark:border-gray-700 dark:bg-gray-900/50">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 class="text-base font-semibold text-[#020f40] dark:text-gray-100">Users</h3>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage portal access, roles, company links, and welcome emails.</p>
                                </div>
                                <span class="rounded-full bg-[#FFA500]/15 px-3 py-1 text-xs font-semibold text-[#020f40] dark:bg-[#FFA500]/20 dark:text-orange-100">{{ $users->count() }} shown</span>
                            </div>

                            <form method="GET" action="{{ route('admin.settings.edit') }}" class="mt-5 grid gap-3 lg:grid-cols-[1fr_160px_220px_auto]">
                                <input type="hidden" name="tab" value="users">
                                <div>
                                    <x-input-label for="user_search" value="Search" />
                                    <x-text-input id="user_search" name="user_search" type="search" class="mt-1 block w-full" value="{{ $userSearch }}" placeholder="Name, email or company" />
                                </div>
                                <div>
                                    <x-input-label for="user_role" value="Role" />
                                    <select id="user_role" name="user_role" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">All roles</option>
                                        <option value="admin" @selected($userRole === 'admin')>Admin</option>
                                        <option value="customer" @selected($userRole === 'customer')>Customer</option>
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="user_company_id" value="Company" />
                                    <select id="user_company_id" name="user_company_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">All companies</option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}" @selected((string) $userCompanyId === (string) $company->id)>{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex items-end gap-2">
                                    <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-[#020f40] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#0b1f66] focus:outline-none focus:ring-2 focus:ring-[#FFA500] focus:ring-offset-2 dark:focus:ring-offset-gray-800">Filter</button>
                                    <a href="{{ route('admin.settings.edit', ['tab' => 'users']) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-500 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Reset</a>
                                </div>
                            </form>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <thead class="bg-[#020f40] dark:bg-gray-950">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium text-white">Name</th>
                                        <th class="px-4 py-3 text-left font-medium text-white">Email</th>
                                        <th class="px-4 py-3 text-left font-medium text-white">Role</th>
                                        <th class="px-4 py-3 text-left font-medium text-white">Company</th>
                                        <th class="px-4 py-3 text-right font-medium text-white"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse ($users as $user)
                                        <tr class="{{ $editingUser?->is($user) ? 'bg-orange-50 dark:bg-orange-950/20' : 'hover:bg-cyan-50/60 dark:hover:bg-gray-900/50' }}">
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</div>
                                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Created {{ $user->created_at?->format('d M Y') }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                                <a href="mailto:{{ $user->email }}" class="font-medium text-[#020f40] hover:text-[#FFA500] hover:underline dark:text-cyan-200">{{ $user->email }}</a>
                                            </td>
                                            <td class="px-4 py-3">
                                                @if ($user->role === 'admin')
                                                    <span class="inline-flex rounded-full bg-orange-100 px-2.5 py-1 text-xs font-medium text-orange-800 ring-1 ring-orange-200 dark:bg-orange-900/60 dark:text-orange-100 dark:ring-orange-700">Admin</span>
                                                @else
                                                    <span class="inline-flex rounded-full bg-cyan-100 px-2.5 py-1 text-xs font-medium text-cyan-800 ring-1 ring-cyan-200 dark:bg-cyan-900/60 dark:text-cyan-100 dark:ring-cyan-700">Customer</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                                @if ($user->company)
                                                    {{ $user->company->name }}
                                                @else
                                                    <span class="text-gray-400 dark:text-gray-500">No company</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="flex flex-wrap justify-end gap-3">
                                                    <form method="POST" action="{{ route('admin.settings.users.welcome-email', $user) }}">
                                                        @csrf
                                                        <button type="submit" class="font-medium text-[#020f40] hover:text-[#FFA500] hover:underline dark:text-cyan-200">Send welcome</button>
                                                    </form>
                                                    <a href="{{ route('admin.settings.edit', ['tab' => 'users', 'edit_user' => $user->id]) }}" class="font-medium text-[#020f40] hover:text-[#FFA500] hover:underline dark:text-cyan-200">Edit</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No users match those filters.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.settings.users.welcome-email-template') }}" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 space-y-5">
                        @csrf

                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="inline-flex rounded-full bg-[#FFA500]/15 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-[#020f40] dark:bg-[#FFA500]/20 dark:text-orange-100">Branded email</div>
                                <h3 class="mt-3 text-base font-semibold text-[#020f40] dark:text-gray-100">Welcome Email Editor</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Used when creating a user with Send welcome email or when clicking Send welcome.</p>
                            </div>
                            <div class="rounded-md border border-cyan-100 bg-cyan-50 p-3 text-xs text-cyan-900 dark:border-cyan-900/60 dark:bg-cyan-950/30 dark:text-cyan-100">
                                <div class="font-medium text-gray-800 dark:text-gray-200">Placeholders</div>
                                <div class="mt-1 font-mono">{name}, {email}, {company}, {app_name}, {password_setup_url}</div>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="welcome_email_subject" value="Subject" />
                            <x-text-input id="welcome_email_subject" name="welcome_email_subject" type="text" class="mt-1 block w-full" value="{{ old('welcome_email_subject', $welcomeEmailSubject) }}" />
                            <x-input-error :messages="$errors->get('welcome_email_subject')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="welcome_email_body" value="Email HTML body" />
                            <textarea id="welcome_email_body" name="welcome_email_body" rows="13" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ old('welcome_email_body', $welcomeEmailBody) }}</textarea>
                            <x-input-error :messages="$errors->get('welcome_email_body')" class="mt-2" />
                        </div>

                        <div class="rounded-md border border-orange-100 bg-orange-50 p-4 dark:border-orange-900/60 dark:bg-orange-950/20">
                            <x-input-label for="test_user_id" value="Send test to user" />
                            <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                                <select id="test_user_id" name="test_user_id" class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Choose a user</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" @selected((string) old('test_user_id') === (string) $user->id)>{{ $user->name }} - {{ $user->email }}</option>
                                    @endforeach
                                </select>
                                <x-secondary-button
                                    type="submit"
                                    formaction="{{ route('admin.settings.users.welcome-email-template.test') }}"
                                    formmethod="POST"
                                >
                                    Send test
                                </x-secondary-button>
                            </div>
                            <x-input-error :messages="$errors->get('test_user_id')" class="mt-2" />
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">The test uses the subject and body currently shown above, even if you have not saved them yet.</p>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">HTML is supported. Keep the password setup link placeholder in the email.</p>
                            <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-[#020f40] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#0b1f66] focus:outline-none focus:ring-2 focus:ring-[#FFA500] focus:ring-offset-2 dark:focus:ring-offset-gray-800">Save welcome email</button>
                        </div>
                    </form>

                    @if ($editingUser)
                        <form method="POST" action="{{ route('admin.settings.users.update', $editingUser) }}" class="rounded-lg border border-orange-200 bg-white p-6 shadow-sm dark:border-orange-900/60 dark:bg-gray-800 space-y-5">
                            @csrf
                            @method('PUT')

                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="inline-flex rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-orange-800 dark:bg-orange-900/50 dark:text-orange-100">Editing</div>
                                    <h3 class="mt-3 text-base font-semibold text-[#020f40] dark:text-gray-100">Edit user</h3>
                                </div>
                                <a href="{{ route('admin.settings.edit', ['tab' => 'users']) }}" class="text-sm text-gray-500 dark:text-gray-400 hover:underline">Cancel</a>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="edit_name" value="Name" />
                                    <x-text-input id="edit_name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $editingUser->name) }}" />
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="edit_email" value="Email" />
                                    <x-text-input id="edit_email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email', $editingUser->email) }}" />
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-3">
                                <div>
                                    <x-input-label for="edit_password" value="New password" />
                                    <x-text-input id="edit_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" placeholder="Leave blank to keep current" />
                                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="edit_role" value="Role" />
                                    <select id="edit_role" name="role" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="customer" @selected(old('role', $editingUser->role) === 'customer')>Customer</option>
                                        <option value="admin" @selected(old('role', $editingUser->role) === 'admin')>Admin</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="edit_company_id" value="Company" />
                                    <select id="edit_company_id" name="company_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">None</option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}" @selected((string) old('company_id', $editingUser->company_id) === (string) $company->id)>{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('company_id')" class="mt-2" />
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-[#020f40] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#0b1f66] focus:outline-none focus:ring-2 focus:ring-[#FFA500] focus:ring-offset-2 dark:focus:ring-offset-gray-800">Save user</button>
                            </div>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.settings.users.store') }}" class="rounded-lg border border-cyan-100 bg-white p-6 shadow-sm dark:border-cyan-900/60 dark:bg-gray-800 space-y-5">
                            @csrf

                            <div>
                                <div class="inline-flex rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-cyan-800 dark:bg-cyan-900/50 dark:text-cyan-100">New access</div>
                                <h3 class="mt-3 text-base font-semibold text-[#020f40] dark:text-gray-100">Add user</h3>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="name" value="Name" />
                                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" />
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="email" value="Email" />
                                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email') }}" />
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-3">
                                <div>
                                    <x-input-label for="password" value="Password" />
                                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" placeholder="Required unless sending welcome" />
                                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="role" value="Role" />
                                    <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="customer" @selected(old('role') === 'customer')>Customer</option>
                                        <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="company_id" value="Company" />
                                    <select id="company_id" name="company_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">None</option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}" @selected((string) old('company_id') === (string) $company->id)>{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('company_id')" class="mt-2" />
                                </div>
                            </div>

                            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <input type="checkbox" name="send_welcome_email" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('send_welcome_email'))>
                                Send welcome email so the user creates their own password
                            </label>

                            <div class="flex justify-end">
                                <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-[#020f40] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#0b1f66] focus:outline-none focus:ring-2 focus:ring-[#FFA500] focus:ring-offset-2 dark:focus:ring-offset-gray-800">Add user</button>
                            </div>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
