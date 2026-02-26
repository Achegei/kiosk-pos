<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProformaQuoteItem extends Model
{
    protected $fillable = ['proforma_quote_id','product_id','quantity','price','total'];

    public function quote() {
        return $this->belongsTo(ProformaQuote::class, 'proforma_quote_id');
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }
}