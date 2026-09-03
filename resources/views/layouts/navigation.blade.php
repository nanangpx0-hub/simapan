<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    @can('admin.user.manage')
                        <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                            {{ __('Pengguna') }}
                        </x-nav-link>
                    @endcan
                    @can('admin.role.manage')
                        <x-nav-link :href="route('admin.roles.index')" :active="request()->routeIs('admin.roles.*')">
                            {{ __('Role') }}
                        </x-nav-link>
                    @endcan
                    @can('master.survey_type.view')
                        <x-nav-link :href="route('master.jenis-survei.index')" :active="request()->routeIs('master.jenis-survei.*')">
                            {{ __('Jenis Survei') }}
                        </x-nav-link>
                    @endcan
                    @can('master.work_unit.view')
                        <x-nav-link :href="route('master.unit-kerja.index')" :active="request()->routeIs('master.unit-kerja.*')">
                            {{ __('Unit Kerja') }}
                        </x-nav-link>
                    @endcan
                    @can('master.survey_period.view')
                        <x-nav-link :href="route('master.survey_periods.index')" :active="request()->routeIs('master.survey_periods.*')">
                            {{ __('Periode Survei') }}
                        </x-nav-link>
                    @endcan
                    @can('master.officer.view')
                        <x-nav-link :href="route('master.officers.index')" :active="request()->routeIs('master.officers.*')">
                            {{ __('Master Petugas') }}
                        </x-nav-link>
                    @endcan
                    @can('allocation.view')
                        <x-nav-link :href="route('allocations.index')" :active="request()->routeIs('allocations.*')">
                            {{ __('Alokasi Kegiatan') }}
                        </x-nav-link>
                    @endcan
                    @can('audit.view')
                        <x-nav-link :href="route('audit_logs.index')" :active="request()->routeIs('audit_logs.*')">
                            {{ __('Audit Trail') }}
                        </x-nav-link>
                    @endcan
                    @can('master.region.view')
                        <x-nav-link :href="route('master.wilayah.index')" :active="request()->routeIs('master.wilayah.*')">
                            {{ __('Master Wilayah') }}
                        </x-nav-link>
                    @endcan
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        @can('profile.manage')
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>
                        @endcan

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            @can('admin.user.manage')
                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                    {{ __('Pengguna') }}
                </x-responsive-nav-link>
            @endcan
            @can('admin.role.manage')
                <x-responsive-nav-link :href="route('admin.roles.index')" :active="request()->routeIs('admin.roles.*')">
                    {{ __('Role') }}
                </x-responsive-nav-link>
            @endcan
            @can('master.survey_type.view')
                <x-responsive-nav-link :href="route('master.jenis-survei.index')" :active="request()->routeIs('master.jenis-survei.*')">
                    {{ __('Jenis Survei') }}
                </x-responsive-nav-link>
            @endcan
            @can('master.work_unit.view')
                <x-responsive-nav-link :href="route('master.unit-kerja.index')" :active="request()->routeIs('master.unit-kerja.*')">
                    {{ __('Unit Kerja') }}
                </x-responsive-nav-link>
            @endcan
            @can('master.survey_period.view')
                <x-responsive-nav-link :href="route('master.survey_periods.index')" :active="request()->routeIs('master.survey_periods.*')">
                    {{ __('Periode Survei') }}
                </x-responsive-nav-link>
            @endcan
            @can('master.officer.view')
                <x-responsive-nav-link :href="route('master.officers.index')" :active="request()->routeIs('master.officers.*')">
                    {{ __('Master Petugas') }}
                </x-responsive-nav-link>
            @endcan
            @can('allocation.view')
                <x-responsive-nav-link :href="route('allocations.index')" :active="request()->routeIs('allocations.*')">
                    {{ __('Alokasi Kegiatan') }}
                </x-responsive-nav-link>
            @endcan
            @can('audit.view')
                <x-responsive-nav-link :href="route('audit_logs.index')" :active="request()->routeIs('audit_logs.*')">
                    {{ __('Audit Trail') }}
                </x-responsive-nav-link>
            @endcan
            @can('master.region.view')
                <x-responsive-nav-link :href="route('master.wilayah.index')" :active="request()->routeIs('master.wilayah.*')">
                    {{ __('Master Wilayah') }}
                </x-responsive-nav-link>
            @endcan
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                @can('profile.manage')
                    <x-responsive-nav-link :href="route('profile.edit')">
                        {{ __('Profile') }}
                    </x-responsive-nav-link>
                @endcan

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
