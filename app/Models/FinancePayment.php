<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'payment_date',
        'direction',
        'contact_id',
        'account_id',
        'amount',
        'method',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function contact()
    {
        return $this->belongsTo(FinanceContact::class, 'contact_id');
    }

    public function account()
    {
        return $this->belongsTo(FinanceAccount::class, 'account_id');
    }
}
