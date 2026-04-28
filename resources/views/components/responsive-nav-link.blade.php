@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-[#FFA500] text-start text-base font-medium text-[#020f40] dark:text-white bg-orange-50 dark:bg-orange-950/30 focus:outline-none focus:text-[#020f40] focus:bg-orange-100 focus:border-[#FFA500] transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-slate-600 dark:text-gray-400 hover:text-[#020f40] dark:hover:text-white hover:bg-orange-50 dark:hover:bg-gray-800 hover:border-[#FFA500]/70 focus:outline-none focus:text-[#020f40] dark:focus:text-white focus:bg-orange-50 focus:border-[#FFA500]/70 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
