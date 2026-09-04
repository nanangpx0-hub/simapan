<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Unit Kerja') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <p>{{ __('Daftar unit kerja.') }}</p>
                        <a href="{{ route('master.unit-kerja.create') }}" class="underline">{{ __('Tambah unit kerja') }}</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('Kode') }}</th>
                                <th class="py-2">{{ __('Nama') }}</th>
                                <th class="py-2">{{ __('Parent') }}</th>
                                <th class="py-2">{{ __('Status') }}</th>
                                <th class="py-2">{{ __('Dibuat') }}</th>
                                <th class="py-2">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($units as $unit)
                                <tr class="border-b">
                                    <td class="py-2">{{ $unit->code }}</td>
                                    <td class="py-2" style="padding-left: {{ ($depths[$unit->id] ?? 0) * 1.5 }}rem;">{{ $unit->name }}</td>
                                    <td class="py-2">{{ $unit->parent ? $unit->parent->code.' — '.$unit->parent->name : __('—') }}</td>
                                    <td class="py-2">{{ $unit->is_active ? __('Aktif') : __('Nonaktif') }}</td>
                                    <td class="py-2">{{ $unit->created_at }}</td>
                                    <td class="py-2">
                                        <a href="{{ route('master.unit-kerja.edit', $unit) }}" class="underline">{{ __('Ubah') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $units->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
