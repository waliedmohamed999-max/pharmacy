<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'supplier_invoice_number',
        'contact_id',
        'warehouse_id',
        'supplier_tax_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'discount',
        'tax_rate',
        'taxable_amount',
        'tax',
        'total',
        'paid_amount',
        'balance',
        'notes',
        'zatca_qr_payload',
        'zatca_status',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function contact()
    {
        return $this->belongsTo(FinanceContact::class, 'contact_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }
}
