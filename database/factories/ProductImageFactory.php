<?php

namespace Database\Factories;

use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;
    private array $placeholders = [
        'images/placeholders/product-1.svg',
        'images/placeholders/product-2.svg',
        'images/placeholders/product-3.svg',
        'images/placeholders/product-4.svg',
        'images/placeholders/product-5.svg',
        'images/placeholders/product-6.svg',
    ];

    public function definition(): array
    {
        return [
            'path' => $this->faker->randomElement($this->placeholders),
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
