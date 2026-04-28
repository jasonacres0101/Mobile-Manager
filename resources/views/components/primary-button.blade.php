<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-[#020f40] dark:bg-[#FFA500] border border-transparent rounded-md font-semibold text-xs text-white dark:text-[#020f40] uppercase tracking-widest hover:bg-[#0b1f66] dark:hover:bg-[#ffb52e] focus:bg-[#0b1f66] dark:focus:bg-[#ffb52e] active:bg-[#020f40] dark:active:bg-[#ffb52e] focus:outline-none focus:ring-2 focus:ring-[#FFA500] focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
