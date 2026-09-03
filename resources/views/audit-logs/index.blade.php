<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Audit Trail') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('audit_logs.index') }}" class="mb-4 flex flex-wrap gap-2 text-sm">
                        <input name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="border rounded px-2 py-1" />
                        <input name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="border rounded px-2 py-1" />
                        <select name="user_id" class="border rounded px-2 py-1">
                            <option value="">{{ __('Semua pelaku') }}</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                        <select name="action" class="border rounded px-2 py-1">
                            <option value="">{{ __('Semua aksi') }}</option>
                            @foreach ($actions as $action)
                                <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                            @endforeach
                        </select>
                        <select name="auditable_type" class="border rounded px-2 py-1">
                            <option value="">{{ __('Semua model') }}</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" @selected(($filters['auditable_type'] ?? '') === $type)>{{ class_basename($type) }}</option>
                            @endforeach
                        </select>
                        <input name="auditable_id" type="number" value="{{ $filters['auditable_id'] ?? '' }}" placeholder="{{ __('ID objek') }}" class="border rounded px-2 py-1 w-24" />
                        <input name="event_uuid" type="text" value="{{ $filters['event_uuid'] ?? '' }}" placeholder="{{ __('Event UUID') }}" class="border rounded px-2 py-1" />
                        <button type="submit" class="underline">{{ __('Filter') }}</button>
                    </form>
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('Waktu') }}</th>
                                <th class="py-2">{{ __('Pelaku') }}</th>
                                <th class="py-2">{{ __('Aksi') }}</th>
                                <th class="py-2">{{ __('Objek') }}</th>
                                <th class="py-2">{{ __('ID') }}</th>
                                <th class="py-2">{{ __('Perubahan') }}</th>
                                <th class="py-2">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr class="border-b">
                                    <td class="py-2">{{ $log->created_at }}</td>
                                    <td class="py-2">{{ $log->actorName() }}</td>
                                    <td class="py-2">{{ $log->action }}</td>
                                    <td class="py-2">{{ $log->objectLabel() }}</td>
                                    <td class="py-2">{{ $log->auditable_id }}</td>
                                    <td class="py-2">{{ implode(', ', $log->changedKeys()) }}</td>
                                    <td class="py-2">
                                        <a href="{{ route('audit_logs.show', $log) }}" class="underline">{{ __('Detail') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $logs->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
