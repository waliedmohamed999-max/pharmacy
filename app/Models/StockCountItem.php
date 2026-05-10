<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockCountItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_count_id',
        'product_id',
        'snapshot_qty',
        'counted_qty',
        'diff_qty',
        'unit_cost_snapshot',
        'diff_value',
        'notes',
    ];

    protected $casts = [
        'snapshot_qty' => 'decimal:2',
        'counted_qty' => 'decimal:2',
        'diff_qty' => 'decimal:2',
        'unit_cost_snapshot' => 'decimal:4',
        'diff_value' => 'decimal:2',
    ];

    public function stockCount()
    {
        return $this->belongsTo(StockCount::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
