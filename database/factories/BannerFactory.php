<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return [
            'title' => 'عرض خاص ' . $this->faker->numberBetween(1, 99),
            'subtitle' => 'خصومات على منتجات العناية والصحة',
            'image' => 'images/placeholder.png',
            'image_path' => 'images/placeholder.png',
            'link_type' => 'url',
            'link_target' => '/',
            'link_url' => '/',
            'start_date' => now()->subDays(rand(0, 5))->toDateString(),
            'end_date' => now()->addDays(rand(10, 30))->toDateString(),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 20),
        ];
    }
}
