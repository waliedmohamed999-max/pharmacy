<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockCount extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'warehouse_id',
        'count_date',
        'status',
        'notes',
        'posted_at',
        'created_by',
    ];

    protected $casts = [
        'count_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(StockCountItem::class);
    }
}
