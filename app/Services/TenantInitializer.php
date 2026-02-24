<?php

namespace App\Services;

use App\Models\User;
use App\Models\RegisterSession;

class TenantInitializer
{
    public static function setup($tenant, $adminData)
    {
        // Create admin user
        $admin = User::create([
            'name' => $adminData['name'],
            'email' => $adminData['email'],
            'password' => bcrypt($adminData['password']),
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'can_pos' => true
        ]);

        // OPTIONAL: Create starter register session structure
        // You can expand later

        return $admin;
    }
}