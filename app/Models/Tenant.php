<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'logo',
        'street_address',
        'building_name',
        'office_number',
        'subscription_status', // trial, active, suspended, terminated
        'expiry_date'
    ];

    protected $dates = ['expiry_date', 'created_at', 'updated_at'];
    protected $casts = [
    'expiry_date' => 'datetime',
];

    /**
     * Accessor for full logo URL
     */
    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            // Assumes logos are stored in storage/app/public/tenant_logos
            return asset('storage/tenant_logos/' . $this->logo);
        }
        return null;
    }

    /**
     * Relationship to staff users
     */
    public function staff()
    {
        return $this->hasMany(\App\Models\User::class, 'tenant_id')
                    ->whereIn('role', ['staff', 'supervisor', 'admin']);
    }

    /**
     * Relationship to transactions
     */
    public function transactions()
    {
        return $this->hasMany(\App\Models\Transaction::class, 'tenant_id');
    }

    /**
     * Relationship to payments
     */
    public function payments()
    {
        return $this->hasMany(\App\Models\Payment::class, 'tenant_id');
    }

    /**
     * Update subscription status based on expiry_date
     */
    public function updateSubscriptionStatus()
{
    $now = Carbon::now();

    if (in_array($this->subscription_status, ['trial', 'active'])) {
        if ($this->expiry_date && $this->expiry_date < $now) {
            $this->suspend(); // automatically suspends tenant and logs out staff
        }
    }
}

    /**
     * Suspend tenant account (when subscription expires)
     */
    public function suspend()
{
    $this->subscription_status = 'suspended';
    $this->save();

    // Log out all staff by deleting sessions
    \DB::table('sessions')
        ->whereIn('user_id', function ($query) {
            $query->select('id')
                  ->from('users')
                  ->where('tenant_id', $this->id);
        })
        ->delete();
}

    /**
     * Check if tenant is active (trial or paid)
     */
    public function isActive()
    {
        return in_array($this->subscription_status, ['trial', 'active']);
    }

    /**
     * Start a free trial for a new tenant (1 month)
     */
    public function startTrial($months = 1)
    {
        $this->subscription_status = 'trial';
        $this->expiry_date = Carbon::now()->addMonths($months);
        $this->save();
    }

    /**
     * Extend subscription when payment is successful
     */
    public function extendSubscription($months = 1)
    {
        $now = Carbon::now();
        $currentExpiry = $this->expiry_date && $this->expiry_date > $now
            ? Carbon::parse($this->expiry_date)
            : $now;

        $this->subscription_status = 'active';
        $this->expiry_date = $currentExpiry->addMonths($months);
        $this->save();
    }

    /**
     * Terminate tenant account (delete tenant and free memory)
     */
    public function terminateAccount()
    {
        // Optional: delete files, logo, etc.
        if ($this->logo) {
            Storage::delete('public/tenant_logos/' . $this->logo);
        }

        $this->delete(); // cascades to payments, transactions if foreign keys have onDelete('cascade')
    }
}