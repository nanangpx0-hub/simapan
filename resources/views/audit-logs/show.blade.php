<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detail Audit') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <dl class="text-sm space-y-2">
                        <div><dt class="font-medium inline">{{ __('Event UUID:') }}</dt> <dd class="inline">{{ $log->event_uuid }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Waktu:') }}</dt> <dd class="inline">{{ $log->created_at }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Pelaku:') }}</dt> <dd class="inline">{{ $log->actorName() }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Aksi:') }}</dt> <dd class="inline">{{ $log->action }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Objek:') }}</dt> <dd class="inline">{{ $log->objectLabel() }} #{{ $log->auditable_id }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Route:') }}</dt> <dd class="inline">{{ $log->route_name ?? '—' }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('IP:') }}</dt> <dd class="inline">{{ $log->ip_address ?? '—' }}</dd></div>
                    </dl>
                    <h3 class="mt-4 font-semibold text-sm">{{ __('Nilai lama') }}</h3>
                    <pre class="mt-1 text-xs bg-gray-50 border rounded p-2 overflow-auto">{{ json_encode($log->old_values ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    <h3 class="mt-4 font-semibold text-sm">{{ __('Nilai baru') }}</h3>
                    <pre class="mt-1 text-xs bg-gray-50 border rounded p-2 overflow-auto">{{ json_encode($log->new_values ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    <h3 class="mt-4 font-semibold text-sm">{{ __('Metadata') }}</h3>
                    <pre class="mt-1 text-xs bg-gray-50 border rounded p-2 overflow-auto">{{ json_encode($log->metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    <div class="mt-4 text-sm">
                        <a href="{{ route('audit_logs.index') }}" class="underline">{{ __('Kembali') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
