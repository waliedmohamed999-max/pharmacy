<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $nameAr = 'تصنيف ' . $this->faker->unique()->numberBetween(100, 999);

        return [
            'name' => $nameAr,
            'name_ar' => $nameAr,
            'name_en' => Str::title($this->faker->words(2, true)),
            'slug' => Str::slug($nameAr),
            'image' => 'images/placeholder.png',
            'parent_id' => null,
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
