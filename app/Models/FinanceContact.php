<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'phone',
        'email',
        'city',
        'address',
        'opening_balance',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
