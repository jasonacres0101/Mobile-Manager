<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Mobile Manager') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-[#020f40] px-4">
            <div class="rounded-md bg-white p-3 shadow-sm">
                <a href="/">
                    <x-application-logo class="h-14 w-auto max-w-[220px]" />
                </a>
            </div>

            <div class="mt-6 h-1 w-24 rounded-full bg-[#FFA500]"></div>

            <div class="w-full sm:max-w-md mt-6 overflow-hidden rounded-lg border border-white/10 bg-white px-6 py-5 shadow-xl dark:bg-gray-900">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
