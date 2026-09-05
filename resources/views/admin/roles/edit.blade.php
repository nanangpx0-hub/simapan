<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Kelola Role: :name', ['name' => $role->name]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-md p-3 text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block font-medium text-sm text-gray-700">{{ __('Slug Role') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $role->name) }}" maxlength="64"
                           pattern="[a-z0-9_]+" @disabled($isSystemRole)
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
                    @if ($isSystemRole)
                        <p class="mt-1 text-xs text-gray-500">{{ __('Role sistem tidak dapat diubah namanya (hanya permission yang dapat dikelola).') }}</p>
                    @endif
                    @if ($isSystemRole)
                        <input type="hidden" name="name" value="{{ $role->name }}" />
                    @endif
                </div>

                <fieldset>
                    <legend class="block font-medium text-sm text-gray-700 mb-2">{{ __('Permission') }}</legend>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                        @foreach ($permissions as $permission)
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="permissions[]" value="{{ $permission }}"
                                       @checked($role->hasPermissionTo($permission))
                                       class="rounded border-gray-300 text-indigo-600" />
                                <span class="font-mono">{{ $permission }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        {{ __('Simpan') }}
                    </button>
                    <a href="{{ route('admin.roles.index') }}" class="text-sm text-gray-600 underline">{{ __('Kembali') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
