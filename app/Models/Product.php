<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'short_description',
        'description',
        'price',
        'avg_cost',
        'compare_price',
        'quantity',
        'reorder_level',
        'reorder_qty',
        'is_active',
        'featured',
        'tags',
        'primary_image',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'avg_cost' => 'decimal:4',
        'compare_price' => 'decimal:2',
        'reorder_level' => 'decimal:2',
        'reorder_qty' => 'decimal:2',
        'is_active' => 'boolean',
        'featured' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (!$product->slug || $product->isDirty('name')) {
                $base = Str::slug($product->name);
                $slug = $base;
                $i = 1;

                while (static::withTrashed()->where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                    $slug = "{$base}-{$i}";
                    $i++;
                }

                $product->slug = $slug;
            }
        });

        static::deleting(function (Product $product) {
            if (!$product->isForceDeleting()) {
                return;
            }

            if (
                $product->primary_image &&
                !str_starts_with($product->primary_image, 'images/') &&
                !str_starts_with($product->primary_image, 'http://') &&
                !str_starts_with($product->primary_image, 'https://') &&
                Storage::disk('public')->exists($product->primary_image)
            ) {
                Storage::disk('public')->delete($product->primary_image);
            }

            foreach ($product->images as $image) {
                if (
                    !str_starts_with($image->path, 'images/') &&
                    !str_starts_with($image->path, 'http://') &&
                    !str_starts_with($image->path, 'https://') &&
                    Storage::disk('public')->exists($image->path)
                ) {
                    Storage::disk('public')->delete($image->path);
                }
            }
        });
    }

    public function getDiscountPercentAttribute(): int
    {
        if (!$this->compare_price || $this->compare_price <= $this->price || $this->compare_price <= 0) {
            return 0;
        }

        return (int) round((($this->compare_price - $this->price) / $this->compare_price) * 100);
    }

    public function getImageUrlAttribute(): string
    {
        if (!$this->primary_image) {
            return asset('images/placeholder.png');
        }

        if (str_starts_with($this->primary_image, 'http://') || str_starts_with($this->primary_image, 'https://')) {
            return $this->primary_image;
        }

        if (str_starts_with($this->primary_image, 'images/')) {
            return asset($this->primary_image);
        }

        return asset('storage/' . $this->primary_image);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }
}
