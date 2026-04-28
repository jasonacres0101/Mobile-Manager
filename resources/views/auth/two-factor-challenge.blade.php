<x-guest-layout>
    <div class="mb-5">
        <h2 class="text-lg font-semibold text-[#020f40] dark:text-gray-100">Two-factor authentication</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Enter the 6-digit code from Microsoft Authenticator or use one of your recovery codes.
        </p>
    </div>

    <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="code" value="Authenticator code" />
            <x-text-input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" class="mt-1 block w-full" autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center gap-3">
            <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
            <span class="text-xs font-semibold uppercase tracking-widest text-gray-500">or</span>
            <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
        </div>

        <div>
            <x-input-label for="recovery_code" value="Recovery code" />
            <x-text-input id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code" class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('recovery_code')" class="mt-2" />
        </div>

        <div class="flex justify-end">
            <x-primary-button>Log in</x-primary-button>
        </div>
    </form>
</x-guest-layout>
