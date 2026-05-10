<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            if ($page->title && (!$page->slug || $page->isDirty('title'))) {
                $base = Str::slug($page->title);
                $slug = $base ?: 'page';
                $i = 1;

                while (static::where('slug', $slug)->where('id', '!=', $page->id)->exists()) {
                    $slug = "{$base}-{$i}";
                    $i++;
                }

                $page->slug = $slug;
            }
        });
    }
}
