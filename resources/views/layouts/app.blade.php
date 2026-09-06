<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'SIMAPAN'))</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        {{-- AdminLTE (Bootstrap 5) dimuat lebih dulu; Tailwind kemudian agar utility menang --}}
        @vite(['resources/scss/adminlte.scss', 'resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="layout-fixed sidebar-expand-lg bg-body-tertiary font-sans antialiased">
        <div class="app-wrapper">

            {{-- ===== Header atas (AdminLTE app-header) ===== --}}
            <header class="app-header">
                <nav class="navbar navbar-expand" aria-label="{{ __('Header') }}">
                    <div class="container-fluid">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <button class="btn btn-nav" type="button" data-lte-toggle="sidebar"
                                        aria-label="{{ __('Buka atau tutup menu') }}" aria-controls="sidebar">
                                    <i class="fa-solid fa-bars" aria-hidden="true"></i>
                                </button>
                            </li>
                        </ul>

                        <ul class="navbar-nav ms-auto">
                            {{-- Dropdown pengguna (Bootstrap dropdown) --}}
                            <li class="nav-item dropdown">
                                <button class="btn btn-nav dropdown-toggle d-flex align-items-center gap-2" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="d-none d-sm-inline">{{ Auth::user()->name }}</span>
                                    <i class="fa-solid fa-user-circle" aria-hidden="true"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                    <li><h6 class="dropdown-header">{{ __('Role: :roles', ['roles' => auth()->user()->getRoleNames()->join(', ')]) }}</h6></li>
                                    @can('profile.manage')
                                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">
                                            <i class="fa-regular fa-user me-2" aria-hidden="true"></i> {{ __('Profile') }}
                                        </a></li>
                                    @endcan
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <a href="{{ route('logout') }}" class="dropdown-item"
                                               onclick="event.preventDefault(); this.closest('form').submit();">
                                                <i class="fa-solid fa-arrow-right-from-bracket me-2" aria-hidden="true"></i> {{ __('Log Out') }}
                                            </a>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
            </header>

            {{-- ===== Sidebar navigasi (AdminLTE app-sidebar) ===== --}}
            <aside id="sidebar" class="app-sidebar shadow-sm" aria-label="{{ __('Main navigation') }}">
                <div class="sidebar-brand">
                    <a href="{{ route('dashboard') }}" class="brand-link d-flex align-items-center gap-2 px-3 py-2">
                        <x-application-logo class="block h-8 w-auto fill-current text-white" />
                        <span class="brand-text fw-semibold" style="color: #ffffff !important;">SIMAPAN</span>
                    </a>
                </div>

                <div class="sidebar-wrapper">
                    <nav role="navigation">
                        <ul class="sidebar-menu" data-accordion="false">
                            <li class="nav-header">{{ __('Menu Utama') }}</li>
                            <li class="nav-item">
                                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                    <i class="nav-icon fa-solid fa-gauge-high" aria-hidden="true"></i>
                                    <p>{{ __('Dashboard') }}</p>
                                </a>
                            </li>

                            @canany(['admin.user.manage', 'admin.role.manage', 'audit.view'])
                                <li class="nav-header">{{ __('Administrasi') }}</li>
                                @can('admin.user.manage')
                                    <li class="nav-item">
                                        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                            <i class="nav-icon fa-solid fa-users-gear" aria-hidden="true"></i>
                                            <p>{{ __('Pengguna') }}</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('admin.role.manage')
                                    <li class="nav-item">
                                        <a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                                            <i class="nav-icon fa-solid fa-user-shield" aria-hidden="true"></i>
                                            <p>{{ __('Role') }}</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('audit.view')
                                    <li class="nav-item">
                                        <a href="{{ route('audit_logs.index') }}" class="nav-link {{ request()->routeIs('audit_logs.*') ? 'active' : '' }}">
                                            <i class="nav-icon fa-solid fa-clipboard-list" aria-hidden="true"></i>
                                            <p>{{ __('Audit Trail') }}</p>
                                        </a>
                                    </li>
                                @endcan
                            @endcanany

                            @canany(['master.survey_type.view', 'master.work_unit.view', 'master.survey_period.view', 'master.region.view', 'master.officer.view'])
                                <li class="nav-header">{{ __('Master Data') }}</li>
                                @can('master.survey_type.view')
                                    <li class="nav-item">
                                        <a href="{{ route('master.jenis-survei.index') }}" class="nav-link {{ request()->routeIs('master.jenis-survei.*') ? 'active' : '' }}">
                                            <i class="nav-icon fa-solid fa-file-circle-question" aria-hidden="true"></i>
                                            <p>{{ __('Jenis Survei') }}</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('master.work_unit.view')
                                    <li class="nav-item">
                                        <a href="{{ route('master.unit-kerja.index') }}" class="nav-link {{ request()->routeIs('master.unit-kerja.*') ? 'active' : '' }}">
                                            <i class="nav-icon fa-solid fa-sitemap" aria-hidden="true"></i>
                                            <p>{{ __('Unit Kerja') }}</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('master.survey_period.view')
                                    <li class="nav-item">
                                        <a href="{{ route('master.survey_periods.index') }}" class="nav-link {{ request()->routeIs('master.survey_periods.*') ? 'active' : '' }}">
                                            <i class="nav-icon fa-regular fa-calendar-days" aria-hidden="true"></i>
                                            <p>{{ __('Periode Survei') }}</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('master.region.view')
                                    <li class="nav-item">
                                        <a href="{{ route('master.wilayah.index') }}" class="nav-link {{ request()->routeIs('master.wilayah.*') ? 'active' : '' }}">
                                            <i class="nav-icon fa-solid fa-map-location-dot" aria-hidden="true"></i>
                                            <p>{{ __('Master Wilayah') }}</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('master.officer.view')
                                    <li class="nav-item">
                                        <a href="{{ route('master.officers.index') }}" class="nav-link {{ request()->routeIs('master.officers.*') ? 'active' : '' }}">
                                            <i class="nav-icon fa-solid fa-id-badge" aria-hidden="true"></i>
                                            <p>{{ __('Master Petugas') }}</p>
                                        </a>
                                    </li>
                                @endcan
                            @endcanany

                            @canany(['allocation.view', 'document.view'])
                                <li class="nav-header">{{ __('Kegiatan') }}</li>
                                @can('allocation.view')
                                    <li class="nav-item">
                                        <a href="{{ route('allocations.index') }}" class="nav-link {{ request()->routeIs('allocations.*') ? 'active' : '' }}">
                                            <i class="nav-icon fa-solid fa-diagram-project" aria-hidden="true"></i>
                                            <p>{{ __('Alokasi Kegiatan') }}</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('document.view')
                                    <li class="nav-item">
                                        <a href="{{ route('documents.index') }}"
                                           class="nav-link {{ request()->routeIs('documents.*') || request()->routeIs('document_manifests.*') || request()->routeIs('document_transfers.*') || request()->routeIs('document_processing_assignments.*') ? 'active' : '' }}">
                                            <i class="nav-icon fa-solid fa-folder-open" aria-hidden="true"></i>
                                            <p>{{ __('Dokumen') }}</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('monitoring.pelaporan-dokumen') }}"
                                           class="nav-link {{ request()->routeIs('monitoring.pelaporan-dokumen') ? 'active' : '' }}">
                                            <i class="nav-icon fa-solid fa-chart-line" aria-hidden="true"></i>
                                            <p>{{ __('Monitoring Pelaporan Dokumen') }}</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('updating_manifests.index') }}"
                                           class="nav-link {{ request()->routeIs('updating_manifests.*') ? 'active' : '' }}">
                                            <i class="nav-icon fa-solid fa-boxes-packing" aria-hidden="true"></i>
                                            <p>{{ __('Pemutakhiran Dokumen') }}</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('sampel.bast.index') }}"
                                           class="nav-link {{ request()->routeIs('sampel.bast.*') ? 'active' : '' }}">
                                            <i class="nav-icon fa-solid fa-file-signature" aria-hidden="true"></i>
                                            <p>{{ __('Daftar BAST Sampel') }}</p>
                                        </a>
                                    </li>
                                @endcan
                            @endcanany
                        </ul>
                    </nav>
                </div>
            </aside>

            {{-- ===== Konten utama (AdminLTE app-main) ===== --}}
            <main class="app-main">
                <div class="app-content-header">
                    <div class="container-fluid">
                        @isset($header)
                            <div class="row mb-2">
                                <div class="col-sm-6">{{ $header }}</div>
                            </div>
                        @endisset
                    </div>
                </div>

                <div class="app-content">
                    <div class="container-fluid">
                        {{-- Flash status global (Bootstrap alert) --}}
                        @if (session('status'))
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                {{ session('status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
                            </div>
                        @endif

                        {{ $slot }}
                    </div>
                </div>
            </main>

            {{-- ===== Footer (AdminLTE app-footer) ===== --}}
            <footer class="app-footer">
                <div class="float-end d-none d-sm-inline">{{ __('Dari lapangan hingga data final.') }}</div>
                <strong>SIMAPAN</strong> {{ __('Sistem Informasi Manajemen Pengolahan dan Pengawasan') }}
            </footer>
        </div>
        @livewireScripts
    </body>
</html>

