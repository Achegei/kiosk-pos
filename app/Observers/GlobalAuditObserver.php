<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;

class GlobalAuditObserver
{
    protected function log($model, string $action): void
    {
        $user = Auth::user();

        // Skip if no authenticated user
        if (!$user) {
            return;
        }

        $tenantId = $user->tenant_id ?? null;
        $deviceUuid = $user->device_uuid ?? null;

        $old = $action === 'updated' ? $model->getOriginal() : null;
        $new = in_array($action, ['created', 'updated']) ? $model->getAttributes() : null;

        DB::table('audit_logs')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'device_uuid' => $deviceUuid,
            'action' => $action,
            'table_name' => $model->getTable(),
            'record_id' => $model->id ?? null,
            'old_values' => $old ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
            'new_values' => $new ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function created($model)      { $this->log($model, 'created'); }
    public function updated($model)      { $this->log($model, 'updated'); }
    public function deleted($model)      { $this->log($model, 'deleted'); }
    public function restored($model)     { $this->log($model, 'restored'); }
    public function forceDeleted($model) { $this->log($model, 'forceDeleted'); }
}