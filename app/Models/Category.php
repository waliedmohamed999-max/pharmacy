<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_ar',
        'name_en',
        'slug',
        'image',
        'parent_id',
        'is_active',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            $sourceName = $category->name_ar ?: $category->name ?: $category->name_en;

            if ($sourceName && (!$category->slug || $category->isDirty(['name', 'name_ar', 'name_en']))) {
                $base = Str::slug($sourceName);
                $slug = $base;
                $i = 1;

                while (static::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                    $slug = "{$base}-{$i}";
                    $i++;
                }

                $category->slug = $slug;
            }

            if (!$category->name && $category->name_ar) {
                $category->name = $category->name_ar;
            }
        });

        static::deleting(function (Category $category) {
            if ($category->image && !str_starts_with($category->image, 'images/') && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }
        });
    }

    public function getDisplayNameAttribute(): string
    {
        if (app()->getLocale() === 'en' && $this->name_en) {
            return $this->name_en;
        }

        return $this->name_ar ?: $this->name ?: ($this->name_en ?: '');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
