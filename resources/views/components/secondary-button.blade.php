<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-[#020f40]/20 dark:border-[#FFA500]/50 rounded-md font-semibold text-xs text-[#020f40] dark:text-gray-200 uppercase tracking-widest shadow-sm hover:border-[#FFA500] hover:bg-orange-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-[#FFA500] focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
