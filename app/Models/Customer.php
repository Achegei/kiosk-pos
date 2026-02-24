<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'credit',
    ];

    // Relationship example: Customer can have many Transactions
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if(auth()->check() && empty($model->tenant_id)){
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });

        static::addGlobalScope('tenant', function ($query) {
        if(auth()->check()){
            $query->where('tenant_id', auth()->user()->tenant_id);
        }
    });
    }

}
