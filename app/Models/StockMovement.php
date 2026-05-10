<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'movement_date',
        'type',
        'warehouse_id',
        'target_warehouse_id',
        'product_id',
        'qty',
        'unit_cost',
        'line_total',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'qty' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'line_total' => 'decimal:2',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function targetWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
