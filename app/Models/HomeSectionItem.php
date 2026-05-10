<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeSectionItem extends Model
{
    use HasFactory;

    protected $fillable = ['home_section_id', 'item_type', 'item_id', 'sort_order'];

    public function section()
    {
        return $this->belongsTo(HomeSection::class, 'home_section_id');
    }
}
