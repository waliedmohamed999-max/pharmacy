<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceJournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'entry_date',
        'reference_type',
        'reference_id',
        'description',
        'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function lines()
    {
        return $this->hasMany(FinanceJournalLine::class, 'journal_entry_id');
    }
}
