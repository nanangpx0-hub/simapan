<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Alias petugas: :code', ['code' => $officer->code]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <p>{{ __('Nama utama: :name', ['name' => $officer->name]) }}</p>
                        <a href="{{ route('master.officers.aliases.create', $officer) }}" class="underline">{{ __('Tambah alias') }}</a>
                    </div>
                    @if (session('warning'))
                        <p class="mb-4 text-sm text-yellow-700">{{ session('warning') }}</p>
                    @endif
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('Alias') }}</th>
                                <th class="py-2">{{ __('Normalisasi') }}</th>
                                <th class="py-2">{{ __('Dibuat oleh') }}</th>
                                <th class="py-2">{{ __('Dibuat') }}</th>
                                <th class="py-2">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($aliases as $alias)
                                <tr class="border-b">
                                    <td class="py-2">{{ $alias->alias_name }}</td>
                                    <td class="py-2">{{ $alias->normalized_alias }}</td>
                                    <td class="py-2">{{ $alias->creator->name ?? __('sistem') }}</td>
                                    <td class="py-2">{{ $alias->created_at }}</td>
                                    <td class="py-2">
                                        <a href="{{ route('master.officers.aliases.edit', [$officer, $alias]) }}" class="underline">{{ __('Ubah') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $aliases->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
