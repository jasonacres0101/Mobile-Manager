@php
    $user = request()->user();
    $twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
    $twoFactorPending = filled($user->two_factor_secret) && ! $twoFactorEnabled;
@endphp

<section>
    <header>
        <div class="inline-flex rounded-full bg-[#FFA500]/15 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-[#020f40] dark:bg-[#FFA500]/20 dark:text-orange-100">Microsoft Authenticator ready</div>
        <h2 class="mt-3 text-lg font-medium text-[#020f40] dark:text-gray-100">
            Two-factor authentication
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Protect your portal login with a 6-digit code from Microsoft Authenticator, Google Authenticator, 1Password, or Authy.
        </p>
    </header>

    <div class="mt-6 space-y-6">
        @if (session('status') === 'two-factor-authentication-enabled')
            <div class="rounded-md border border-orange-200 bg-orange-50 p-4 text-sm text-orange-900 dark:border-orange-900/60 dark:bg-orange-950/30 dark:text-orange-100">
                Scan the QR code below, then enter the authenticator code to confirm 2FA.
            </div>
        @elseif (session('status') === 'two-factor-authentication-confirmed')
            <div class="rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-900 dark:border-green-900/60 dark:bg-green-950/30 dark:text-green-100">
                Two-factor authentication is enabled.
            </div>
        @elseif (session('status') === 'two-factor-authentication-disabled')
            <div class="rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                Two-factor authentication has been disabled.
            </div>
        @elseif (session('status') === 'recovery-codes-generated')
            <div class="rounded-md border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-900 dark:border-cyan-900/60 dark:bg-cyan-950/30 dark:text-cyan-100">
                Recovery codes regenerated. Store the new codes somewhere safe.
            </div>
        @endif

        <div class="rounded-lg border {{ $twoFactorEnabled ? 'border-green-200 bg-green-50 dark:border-green-900/60 dark:bg-green-950/20' : 'border-orange-100 bg-orange-50 dark:border-orange-900/60 dark:bg-orange-950/20' }} p-4">
            <div class="text-sm font-medium {{ $twoFactorEnabled ? 'text-green-900 dark:text-green-100' : 'text-orange-900 dark:text-orange-100' }}">
                Status: {{ $twoFactorEnabled ? 'Enabled' : ($twoFactorPending ? 'Waiting for confirmation' : 'Not enabled') }}
            </div>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Admin accounts should enable this before the portal goes live.
            </p>
        </div>

        @if (! filled($user->two_factor_secret))
            <form method="POST" action="{{ route('two-factor.enable') }}">
                @csrf
                <x-primary-button>Enable 2FA</x-primary-button>
            </form>
        @else
            @if ($twoFactorPending)
                <div class="grid gap-5 lg:grid-cols-[220px_1fr]">
                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                        {!! $user->twoFactorQrCodeSvg() !!}
                    </div>
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Scan this QR code with Microsoft Authenticator, then enter the generated 6-digit code.
                        </p>
                        <form method="POST" action="{{ route('two-factor.confirm') }}" class="flex flex-col gap-3 sm:flex-row">
                            @csrf
                            <x-text-input name="code" type="text" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" class="block w-full sm:max-w-xs" />
                            <x-primary-button>Confirm 2FA</x-primary-button>
                        </form>
                        <x-input-error :messages="$errors->get('code')" />
                    </div>
                </div>
            @endif

            @if ($twoFactorEnabled)
                <div>
                    <h3 class="text-sm font-semibold text-[#020f40] dark:text-gray-100">Recovery codes</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Use these if you lose access to your authenticator app. Each code works once.</p>
                    <div class="mt-3 grid gap-2 rounded-lg border border-gray-200 bg-gray-50 p-4 font-mono text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 sm:grid-cols-2">
                        @foreach ($user->recoveryCodes() as $code)
                            <div>{{ $code }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex flex-wrap gap-3">
                @if ($twoFactorEnabled)
                    <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                        @csrf
                        <x-secondary-button>Regenerate recovery codes</x-secondary-button>
                    </form>
                @endif

                <form method="POST" action="{{ route('two-factor.disable') }}">
                    @csrf
                    @method('DELETE')
                    <x-secondary-button>Disable 2FA</x-secondary-button>
                </form>
            </div>
        @endif
    </div>
</section>
