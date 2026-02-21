<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',       // role for RBAC
        'can_pos',    // <-- new: staff POS permission (boolean)
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Cast attributes
     *
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'can_pos' => 'boolean', // cast can_pos to boolean
        ];
    }

    /**
     * Relationship to customer profile
     */
    public function customerProfile()
    {
        return $this->hasOne(CustomerProfile::class);
    }

    // ================= RBAC HELPERS =================

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function registerSessions()
    {
        return $this->hasMany(RegisterSession::class);
    }

    public function openRegister()
    {
        return $this->hasOne(RegisterSession::class,'user_id')
                    ->where('status','open');
    }



    /**
     * Return roles this user can create/manage
     */
    public static function creatableRoles(User $user): array
    {
        return match ($user->role) {
            'super_admin' => ['admin', 'supervisor', 'staff'],
            'admin'       => ['supervisor', 'staff'],
            'supervisor'  => ['staff'],
            default       => [],
        };
    }

    /**
     * Check if user can access POS
     */
    public function canAccessPos(): bool
    {
        return $this->can_pos && $this->isStaff();
    }
}
