<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        DB::table('audit_logs')->insert([
            'user_id'     => $event->user->id,
            'action'      => 'login',
            'table_name'  => 'users',
            'record_id'   => $event->user->id,
            'ip_address'  => Request::ip(),
            'user_agent'  => Request::userAgent(),
            'device_uuid' => session('device_uuid'),
            'old_values'  => null,
            'new_values'  => json_encode(['login' => true]),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
}
