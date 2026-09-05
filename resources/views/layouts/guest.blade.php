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
        @vite(['resources/scss/adminlte.scss', 'resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="login-page bg-body-tertiary font-sans text-gray-900 antialiased">
        <div class="login-box">
            <div class="card card-outline card-primary">
                <div class="card-header text-center border-0 pt-4">
                    <a href="/">
                        <x-application-logo class="w-20 h-20 fill-current text-gray-500 mx-auto" />
                        <span class="h4 fw-semibold text-gray-800 d-block mt-2">SIMAPAN</span>
                    </a>
                </div>

                <div class="card-body login-card-body">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
