<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProformaQuote extends Model
{
    protected $fillable = [
        'tenant_id',
        'staff_id',
        'customer_id',
        'quote_number',
        'total_amount',
        'status',
        'company_name',
        'company_email',
        'company_phone',
        'company_address',
        'company_logo',
        'client_name',
        'client_email',
        'client_phone',
        'client_address',
        'tax_percent',
        'discount',
        'notes',
        'expiry_date'
    ];

    public function items() {
        return $this->hasMany(ProformaQuoteItem::class);
    }

    public function customer() {
        return $this->belongsTo(Customer::class);
    }

    public function staff() {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }
}