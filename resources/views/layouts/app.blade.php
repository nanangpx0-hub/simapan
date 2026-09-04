<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <div x-data="{ sidebarOpen: false }" x-init="$watch('sidebarOpen', val => { if (!val) { $nextTick(() => $refs.hamburger.focus()) } })" x-on:keydown.escape.window="sidebarOpen = false" class="min-h-screen">
            @include('layouts.navigation')

            <div class="lg:ps-64 flex flex-col min-h-screen">
                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white shadow-sm sticky top-0 z-20">
                        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex items-center gap-3">
                            <button @click="sidebarOpen = true" x-ref="hamburger" aria-label="{{ __('Buka menu') }}" x-bind:aria-expanded="sidebarOpen ? 'true' : 'false'" aria-controls="sidebar" class="lg:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="flex-1">
                    {{ $slot }}
                </main>

                <footer class="bg-white border-t border-gray-200">
                    <div class="max-w-7xl mx-auto py-3 px-4 sm:px-6 lg:px-8 text-xs text-gray-500 flex flex-wrap gap-x-4 gap-y-1">
                        <span><strong>SIMAPAN</strong> {{ __('Sistem Informasi Manajemen Pengolahan dan Pengawasan') }}</span>
                        <span class="ms-auto">{{ __('Dari lapangan hingga data final.') }}</span>
                    </div>
                </footer>
            </div>
        </div>
    </body>
</html>
