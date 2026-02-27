<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
    'invoice_number',
    'tenant_id',
    'staff_id',
    'customer_id',
    'proforma_quote_id',
    'reference',
    'notes',
    'total_amount',
    'status',

    // Company snapshot
    'company_name',
    'company_email',
    'company_phone',
    'company_address',
    'company_logo',

    // Client snapshot
    'client_name',
    'client_email',
    'client_phone',
    'client_address',

    // Financial info
    'tax_percent',
    'discount',
    'expiry_date',
];

    public function items() {
        return $this->hasMany(InvoiceItem::class);
    }

    public function customer() {
        return $this->belongsTo(Customer::class);
    }

    public function staff() {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function quote() {
        return $this->belongsTo(ProformaQuote::class, 'proforma_quote_id');
    }
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
    protected static function booted()
    {
        static::creating(function ($invoice) {

            if (!$invoice->invoice_number) {

                $lastInvoice = self::where('tenant_id', $invoice->tenant_id)
                    ->whereNotNull('invoice_number')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                $nextNumber = 1;

                if ($lastInvoice) {
                    $lastNumber = (int) str_replace('INV-', '', $lastInvoice->invoice_number);
                    $nextNumber = $lastNumber + 1;
                }

                $invoice->invoice_number = 'INV-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}