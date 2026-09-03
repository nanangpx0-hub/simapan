{{-- Mobile overlay --}}
<div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden" @click="sidebarOpen = false" style="display: none;"></div>

{{-- Sidebar --}}
<aside :class="{'-translate-x-full': ! sidebarOpen}" class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full lg:translate-x-0 transform transition-transform duration-200 ease-in-out bg-indigo-950 flex flex-col">
    <div class="flex items-center gap-2 h-16 px-4 border-b border-white/10 shrink-0">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 min-w-0">
            <x-application-logo class="block h-8 w-auto fill-current text-white" />
            <span class="text-white font-semibold tracking-wide truncate">SIMAPAN</span>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden ms-auto p-1 rounded text-indigo-200 hover:text-white" aria-label="{{ __('Tutup menu') }}">
            <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-5 text-sm">
        <div>
            <p class="px-3 mb-1 text-[11px] font-semibold uppercase tracking-wider text-indigo-300/70">{{ __('Menu Utama') }}</p>
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-md {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white font-medium' : 'text-indigo-100/80 hover:bg-white/5 hover:text-white' }}">
                {{ __('Dashboard') }}
            </a>
        </div>

        @canany(['admin.user.manage', 'admin.role.manage', 'audit.view'])
            <div>
                <p class="px-3 mb-1 text-[11px] font-semibold uppercase tracking-wider text-indigo-300/70">{{ __('Administrasi') }}</p>
                <div class="space-y-0.5">
                    @can('admin.user.manage')
                        <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.users.*') ? 'bg-white/10 text-white font-medium' : 'text-indigo-100/80 hover:bg-white/5 hover:text-white' }}">
                            {{ __('Pengguna') }}
                        </a>
                    @endcan
                    @can('admin.role.manage')
                        <a href="{{ route('admin.roles.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md {{ request()->routeIs('admin.roles.*') ? 'bg-white/10 text-white font-medium' : 'text-indigo-100/80 hover:bg-white/5 hover:text-white' }}">
                            {{ __('Role') }}
                        </a>
                    @endcan
                    @can('audit.view')
                        <a href="{{ route('audit_logs.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md {{ request()->routeIs('audit_logs.*') ? 'bg-white/10 text-white font-medium' : 'text-indigo-100/80 hover:bg-white/5 hover:text-white' }}">
                            {{ __('Audit Trail') }}
                        </a>
                    @endcan
                </div>
            </div>
        @endcanany

        @canany(['master.survey_type.view', 'master.work_unit.view', 'master.survey_period.view', 'master.region.view', 'master.officer.view'])
            <div>
                <p class="px-3 mb-1 text-[11px] font-semibold uppercase tracking-wider text-indigo-300/70">{{ __('Master Data') }}</p>
                <div class="space-y-0.5">
                    @can('master.survey_type.view')
                        <a href="{{ route('master.jenis-survei.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md {{ request()->routeIs('master.jenis-survei.*') ? 'bg-white/10 text-white font-medium' : 'text-indigo-100/80 hover:bg-white/5 hover:text-white' }}">
                            {{ __('Jenis Survei') }}
                        </a>
                    @endcan
                    @can('master.work_unit.view')
                        <a href="{{ route('master.unit-kerja.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md {{ request()->routeIs('master.unit-kerja.*') ? 'bg-white/10 text-white font-medium' : 'text-indigo-100/80 hover:bg-white/5 hover:text-white' }}">
                            {{ __('Unit Kerja') }}
                        </a>
                    @endcan
                    @can('master.survey_period.view')
                        <a href="{{ route('master.survey_periods.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md {{ request()->routeIs('master.survey_periods.*') ? 'bg-white/10 text-white font-medium' : 'text-indigo-100/80 hover:bg-white/5 hover:text-white' }}">
                            {{ __('Periode Survei') }}
                        </a>
                    @endcan
                    @can('master.region.view')
                        <a href="{{ route('master.wilayah.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md {{ request()->routeIs('master.wilayah.*') ? 'bg-white/10 text-white font-medium' : 'text-indigo-100/80 hover:bg-white/5 hover:text-white' }}">
                            {{ __('Master Wilayah') }}
                        </a>
                    @endcan
                    @can('master.officer.view')
                        <a href="{{ route('master.officers.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md {{ request()->routeIs('master.officers.*') ? 'bg-white/10 text-white font-medium' : 'text-indigo-100/80 hover:bg-white/5 hover:text-white' }}">
                            {{ __('Master Petugas') }}
                        </a>
                    @endcan
                </div>
            </div>
        @endcanany

        @canany(['allocation.view', 'document.view'])
            <div>
                <p class="px-3 mb-1 text-[11px] font-semibold uppercase tracking-wider text-indigo-300/70">{{ __('Kegiatan') }}</p>
                <div class="space-y-0.5">
                    @can('allocation.view')
                        <a href="{{ route('allocations.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md {{ request()->routeIs('allocations.*') ? 'bg-white/10 text-white font-medium' : 'text-indigo-100/80 hover:bg-white/5 hover:text-white' }}">
                            {{ __('Alokasi Kegiatan') }}
                        </a>
                    @endcan
                    @can('document.view')
                        <a href="{{ route('documents.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md {{ request()->routeIs('documents.*') || request()->routeIs('document_manifests.*') || request()->routeIs('document_transfers.*') || request()->routeIs('document_processing_assignments.*') ? 'bg-white/10 text-white font-medium' : 'text-indigo-100/80 hover:bg-white/5 hover:text-white' }}">
                            {{ __('Dokumen') }}
                        </a>
                    @endcan
                </div>
            </div>
        @endcanany
    </nav>

    <div class="border-t border-white/10 p-3 shrink-0" x-data="{ userOpen: false }">
        <button @click="userOpen = ! userOpen" class="w-full flex items-center gap-2 px-3 py-2 rounded-md text-indigo-100/80 hover:bg-white/5 hover:text-white text-sm">
            <span class="truncate">{{ Auth::user()->name }}</span>
            <svg class="h-4 w-4 ms-auto shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        <div x-show="userOpen" class="mt-1 space-y-0.5 text-sm" style="display: none;">
            @can('profile.manage')
                <a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded-md text-indigo-100/80 hover:bg-white/5 hover:text-white">
                    {{ __('Profile') }}
                </a>
            @endcan
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();" class="block px-3 py-2 rounded-md text-indigo-100/80 hover:bg-white/5 hover:text-white">
                    {{ __('Log Out') }}
                </a>
            </form>
        </div>
    </div>
</aside>
