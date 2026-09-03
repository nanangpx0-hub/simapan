<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Master Wilayah') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <p>{{ __('Daftar wilayah hierarkis.') }}</p>
                        <a href="{{ route('master.wilayah.create') }}" class="underline">{{ __('Tambah wilayah') }}</a>
                    </div>
                    <form method="GET" action="{{ route('master.wilayah.index') }}" class="mb-4 flex flex-wrap gap-2 text-sm">
                        <select name="level" class="border rounded px-2 py-1">
                            <option value="">{{ __('Semua level') }}</option>
                            @foreach ($levels as $level)
                                <option value="{{ $level }}" @selected(($filters['level'] ?? '') === $level)>{{ $level }}</option>
                            @endforeach
                        </select>
                        <select name="parent_id" class="border rounded px-2 py-1">
                            <option value="">{{ __('Semua parent') }}</option>
                            @foreach ($filterParents as $parent)
                                <option value="{{ $parent->id }}" @selected((string) ($filters['parent_id'] ?? '') === (string) $parent->id)>{{ $parent->full_code }} — {{ $parent->name }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="border rounded px-2 py-1">
                            <option value="">{{ __('Semua status') }}</option>
                            <option value="aktif" @selected(($filters['status'] ?? '') === 'aktif')>{{ __('Aktif') }}</option>
                            <option value="nonaktif" @selected(($filters['status'] ?? '') === 'nonaktif')>{{ __('Nonaktif') }}</option>
                        </select>
                        <input name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Cari code/nama') }}" class="border rounded px-2 py-1" />
                        <button type="submit" class="underline">{{ __('Filter') }}</button>
                    </form>
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('Full Code') }}</th>
                                <th class="py-2">{{ __('Code') }}</th>
                                <th class="py-2">{{ __('Nama') }}</th>
                                <th class="py-2">{{ __('Level') }}</th>
                                <th class="py-2">{{ __('Parent') }}</th>
                                <th class="py-2">{{ __('Status') }}</th>
                                <th class="py-2">{{ __('Child aktif') }}</th>
                                <th class="py-2">{{ __('Dibuat') }}</th>
                                <th class="py-2">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($regions as $region)
                                <tr class="border-b">
                                    <td class="py-2">{{ $region->full_code }}</td>
                                    <td class="py-2">{{ $region->code }}</td>
                                    <td class="py-2" style="padding-left: {{ ($depths[$region->id] ?? 0) * 1.5 }}rem;">{{ $region->name }}</td>
                                    <td class="py-2">{{ $region->level }}</td>
                                    <td class="py-2">{{ $region->parent ? $region->parent->full_code.' — '.$region->parent->name : __('—') }}</td>
                                    <td class="py-2">{{ $region->is_active ? __('Aktif') : __('Nonaktif') }}</td>
                                    <td class="py-2">{{ $region->active_children_count }}</td>
                                    <td class="py-2">{{ $region->created_at }}</td>
                                    <td class="py-2">
                                        <a href="{{ route('master.wilayah.edit', $region) }}" class="underline">{{ __('Ubah') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $regions->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
