<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;
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
        $name = 'منتج طبي ' . $this->faker->unique()->numberBetween(100, 9999);
        $tags = ['برد وزكام', 'فيتامينات', 'عناية شعر', 'أطفال', 'عناية بالبشرة'];

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'sku' => 'SKU-' . strtoupper(Str::random(8)),
            'short_description' => 'وصف مختصر للمنتج',
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 20, 600),
            'compare_price' => $this->faker->boolean(45) ? $this->faker->randomFloat(2, 30, 700) : null,
            'quantity' => $this->faker->numberBetween(0, 120),
            'is_active' => true,
            'featured' => $this->faker->boolean(30),
            'tags' => collect($tags)->random(rand(1, 3))->implode(','),
            'primary_image' => $this->faker->randomElement($this->placeholders),
        ];
    }
}
