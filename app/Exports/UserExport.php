<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UserExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  array{q?: string, role?: string, status?: string}  $filters
     */
    public function __construct(private array $filters = []) {}

    /** @return Builder<User> */
    public function query(): Builder
    {
        return User::query()
            ->with('roles')
            ->when(! empty($this->filters['q'] ?? null), function ($query): void {
                $keyword = '%'.str_replace(['%', '_'], '', (string) $this->filters['q']).'%';
                $query->where(function ($query) use ($keyword): void {
                    $query->where('name', 'like', $keyword)->orWhere('email', 'like', $keyword);
                });
            })
            ->when(! empty($this->filters['role'] ?? null), fn ($query) => $query->role((string) $this->filters['role']))
            ->when(($this->filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($this->filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name');
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['name', 'email', 'status', 'roles', 'created_at'];
    }

    /** @param User $row */
    public function map($row): array
    {
        return [
            (string) $row->name,
            (string) $row->email,
            $row->is_active ? 'Aktif' : 'Nonaktif',
            (string) $row->getRoleNames()->join(', '),
            (string) ($row->created_at?->format('Y-m-d H:i:s') ?? ''),
        ];
    }
}
