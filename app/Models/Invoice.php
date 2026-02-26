<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['tenant_id','staff_id','customer_id','proforma_quote_id','total_amount','status'];

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
}