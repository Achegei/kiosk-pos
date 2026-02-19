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
}
