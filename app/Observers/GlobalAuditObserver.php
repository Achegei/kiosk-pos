<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class GlobalAuditObserver
{
    protected function log($model, $action)
    {
        // Only log if user is logged in
        $user = Auth::user();

        if (!$user) {
            return;
        }

        $old = $action === 'updated' ? $model->getOriginal() : null;
        $new = in_array($action, ['created', 'updated']) ? $model->getAttributes() : null;

        \DB::table('audit_logs')->insert([
            'user_id' => $user->id,
            'device_uuid' => $user->device_uuid ?? null,
            'action' => $action,
            'table_name' => $model->getTable(),
            'record_id' => $model->id ?? null,
            'old_values' => $old ? json_encode($old) : null,
            'new_values' => $new ? json_encode($new) : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function created($model)
    {
        $this->log($model, 'created');
    }

    public function updated($model)
    {
        $this->log($model, 'updated');
    }

    public function deleted($model)
    {
        $this->log($model, 'deleted');
    }

    public function restored($model)
    {
        $this->log($model, 'restored');
    }

    public function forceDeleted($model)
    {
        $this->log($model, 'forceDeleted');
    }
}
