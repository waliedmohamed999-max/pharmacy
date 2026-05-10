<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'image_path',
        'image',
        'link_url',
        'link_type',
        'link_target',
        'start_date',
        'end_date',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function scopeActiveForToday($query)
    {
        $today = now()->toDateString();

        return $query
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhereDate('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            });
    }

    public function getResolvedImageAttribute(): string
    {
        $path = $this->image ?: $this->image_path;

        if (!$path) {
            return asset('images/placeholder.png');
        }

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return asset('storage/' . $path);
    }

    public function getResolvedUrlAttribute(): string
    {
        return match ($this->link_type) {
            'product' => ($product = Product::find($this->link_target)) && $product->slug
                ? route('store.product.show', $product->slug)
                : ($this->link_url ?: '#'),
            'category' => ($category = Category::find($this->link_target)) && $category->slug
                ? route('store.category.show', $category->slug)
                : ($this->link_url ?: '#'),
            default => $this->link_target ?: ($this->link_url ?: '#'),
        };
    }
}
