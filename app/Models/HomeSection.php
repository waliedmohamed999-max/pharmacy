<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'title_ar',
        'title_en',
        'type',
        'sort_order',
        'is_active',
        'data_source',
        'filters_json',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'filters_json' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(HomeSectionItem::class)->orderBy('sort_order');
    }

    public function getDisplayTitleAttribute(): ?string
    {
        if (app()->getLocale() === 'en' && $this->title_en) {
            return $this->title_en;
        }

        return $this->title_ar ?: $this->title_en;
    }
}
