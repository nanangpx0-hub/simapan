<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Allocation;
use App\Models\Assignment;
use App\Models\Officer;
use App\Models\Region;
use App\Models\SurveyPeriod;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function created(Model $model): void
    {
        if (! AuditLogger::isAuditing()) {
            return;
        }

        $changes = $this->diff($model, [], $model->getAttributes());

        $action = $model instanceof Assignment ? 'assigned' : 'created';

        AuditLogger::log($action, $model, $changes['old'], $changes['new']);
    }

    public function updated(Model $model): void
    {
        if (! AuditLogger::isAuditing()) {
            return;
        }

        $changes = $this->diff($model, $model->getOriginal(), $model->getAttributes());

        if ($changes['old'] === [] && $changes['new'] === []) {
            return;
        }

        $action = $this->resolveUpdateAction($model, $changes);

        if ($action === null) {
            return;
        }

        AuditLogger::log($action, $model, $changes['old'], $changes['new']);
    }

    public function deleted(Model $model): void
    {
        if (! AuditLogger::isAuditing()) {
            return;
        }

        AuditLogger::log('deleted', $model, $this->diff($model, $model->getAttributes(), [])['old']);
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @return array{old: array<string, mixed>, new: array<string, mixed>}
     */
    private function diff(Model $model, array $old, array $new): array
    {
        $fields = method_exists($model, 'auditableFields') ? $model->auditableFields() : [];
        $ignored = ['created_at', 'updated_at', 'deleted_at', 'remember_token'];

        $oldOut = [];
        $newOut = [];

        foreach ($fields as $field) {
            $oldValue = $old[$field] ?? null;
            $newValue = $new[$field] ?? null;

            if (in_array($field, $ignored, true)) {
                continue;
            }

            if ($this->normalized($oldValue) !== $this->normalized($newValue)) {
                $oldOut[$field] = $oldValue;
                $newOut[$field] = $newValue;
            }
        }

        return ['old' => $oldOut, 'new' => $newOut];
    }

    private function normalized(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) ($value ?? '');
    }

    /**
     * @param  array{old: array<string, mixed>, new: array<string, mixed>}  $changes
     */
    private function resolveUpdateAction(Model $model, array $changes): ?string
    {
        $old = $changes['old'];
        $new = $changes['new'];

        if ($model instanceof User) {
            return $this->statusTransition($old['is_active'] ?? null, $new['is_active'] ?? null, 'updated');
        }

        if ($model instanceof Officer) {
            if (array_key_exists('user_id', $old) || array_key_exists('user_id', $new)) {
                return empty($new['user_id'] ?? null) ? 'user_unlinked' : 'user_linked';
            }

            if (array_key_exists('status', $old) || array_key_exists('status', $new)) {
                return ($new['status'] ?? null) === 'ACTIVE' ? 'activated' : 'deactivated';
            }

            return 'updated';
        }

        if ($model instanceof WorkUnit || $model instanceof Region) {
            if (array_key_exists('is_active', $old) || array_key_exists('is_active', $new)) {
                return ! empty($new['is_active']) ? 'activated' : 'deactivated';
            }

            if (array_key_exists('parent_id', $old) || array_key_exists('parent_id', $new)) {
                return 'parent_changed';
            }

            return 'updated';
        }

        if ($model instanceof SurveyPeriod) {
            $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
            $nonStatus = array_diff($keys, ['status', 'closed_by', 'closed_at']);

            return $nonStatus === [] ? null : 'updated';
        }

        if ($model instanceof Allocation) {
            $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
            $nonStatus = array_diff($keys, ['status']);

            return $nonStatus === [] ? null : 'updated';
        }

        if ($model instanceof Assignment) {
            if (array_key_exists('is_active', $old) || array_key_exists('is_active', $new)) {
                return ! empty($new['is_active']) ? 'assigned' : 'unassigned';
            }

            return 'updated';
        }

        return 'updated';
    }

    private function statusTransition(mixed $old, mixed $new, string $default): string
    {
        if ($old === null && $new === null) {
            return $default;
        }

        return ! empty($new) ? 'activated' : 'deactivated';
    }
}
