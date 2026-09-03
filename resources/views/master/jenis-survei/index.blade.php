<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Jenis Survei') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <p>{{ __('Daftar jenis survei.') }}</p>
                        <a href="{{ route('master.jenis-survei.create') }}" class="underline">{{ __('Tambah jenis survei') }}</a>
                    </div>
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('Kode') }}</th>
                                <th class="py-2">{{ __('Nama') }}</th>
                                <th class="py-2">{{ __('Deskripsi') }}</th>
                                <th class="py-2">{{ __('Status') }}</th>
                                <th class="py-2">{{ __('Dibuat') }}</th>
                                <th class="py-2">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($types as $type)
                                <tr class="border-b">
                                    <td class="py-2">{{ $type->code }}</td>
                                    <td class="py-2">{{ $type->name }}</td>
                                    <td class="py-2">{{ \Illuminate\Support\Str::limit($type->description, 80) }}</td>
                                    <td class="py-2">{{ $type->is_active ? __('Aktif') : __('Nonaktif') }}</td>
                                    <td class="py-2">{{ $type->created_at }}</td>
                                    <td class="py-2">
                                        <a href="{{ route('master.jenis-survei.edit', $type) }}" class="underline">{{ __('Ubah') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $types->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
