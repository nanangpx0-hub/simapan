<div class="space-y-4">
    <div class="flex flex-wrap items-end gap-2 text-sm">
        <div class="flex flex-col">
            <label for="role-q" class="mb-1 font-medium text-gray-700">{{ __('Cari role') }}</label>
            <input id="role-q" type="text" wire:model.live.debounce.500ms="q" placeholder="{{ __('Cari role') }}" class="border rounded px-2 py-1 w-56" />
        </div>
        <div class="flex flex-col">
            <label for="role-perpage" class="mb-1 font-medium text-gray-700">{{ __('Per halaman') }}</label>
            <select id="role-perpage" wire:model.live="perPage" class="border rounded px-2 py-1">
                @foreach (\App\Livewire\Admin\RoleTable::PER_PAGE_OPTIONS as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 pb-0.5">
            <button type="button" wire:click="resetFilters" class="underline">{{ __('Reset') }}</button>
        </div>
    </div>

    <p class="text-xs text-gray-500" aria-live="polite">
        {{ __('Menampilkan :from–:to dari :total data', ['from' => $roles->firstItem() ?? 0, 'to' => $roles->lastItem() ?? 0, 'total' => $roles->total()]) }}
    </p>

    <div wire:loading.delay class="text-xs text-gray-500">{{ __('Memuat…') }}</div>

    <div class="overflow-x-auto rounded border border-gray-200">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50">
                <tr class="border-b">
                    @foreach (['name' => __('Nama'), 'created_at' => __('Dibuat')] as $field => $label)
                        <th class="py-2 px-3 whitespace-nowrap">
                            <button type="button" wire:click="sortBy('{{ $field }}')" class="font-semibold hover:underline focus:ring-2 focus:ring-indigo-500 rounded">
                                {{ $label }} @if ($sortField === $field) {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                            </button>
                        </th>
                    @endforeach
                    <th class="py-2 px-3">{{ __('Slug') }}</th>
                    <th class="py-2 px-3">{{ __('Permission') }}</th>
                    <th class="py-2 px-3">{{ __('Jumlah user') }}</th>
                    @can('super-admin')
                        <th class="py-2 px-3">{{ __('Aksi') }}</th>
                    @endcan
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $role)
                    <tr wire:click="selectRow({{ $role->id }})" class="border-b cursor-pointer transition-colors {{ $selectedId === $role->id ? 'bg-indigo-50' : 'hover:bg-gray-50' }}">
                        <td class="py-2 px-3">{{ $labels[$role->name] ?? $role->name }}</td>
                        <td class="py-2 px-3 whitespace-nowrap">{{ $role->created_at?->format('Y-m-d') }}</td>
                        <td class="py-2 px-3 font-mono">{{ $role->name }}</td>
                        <td class="py-2 px-3">{{ $role->permissions->pluck('name')->join(', ') }}</td>
                        <td class="py-2 px-3">{{ $role->users_count }}</td>
                        @can('super-admin')
                            <td class="py-2 px-3 whitespace-nowrap" wire:click.stop>
                                <a href="{{ route('admin.roles.edit', $role) }}" class="text-indigo-600 hover:text-indigo-800">{{ __('Kelola') }}</a>
                                @unless (in_array($role->name, array_keys($labels), true))
                                    <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="inline ms-2" onsubmit="return confirm('{{ __('Hapus role ini?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">{{ __('Hapus') }}</button>
                                    </form>
                                @endunless
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="{{ auth()->user()->can('super-admin') ? 6 : 5 }}" class="py-4 px-3 text-center text-gray-500">{{ __('Tidak ada data.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $roles->links() }}</div>
</div>
