<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->string('type')->default('auto');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('data_source')->nullable();
            $table->json('filters_json')->nullable();
            $table->timestamps();
        });

        Schema::create('home_section_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_section_id')->constrained()->cascadeOnDelete();
            $table->string('item_type');
            $table->unsignedBigInteger('item_id');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['item_type', 'item_id']);
        });

        DB::table('home_sections')->insert([
            ['key' => 'slider_banners', 'title_ar' => 'بنرات رئيسية', 'title_en' => 'Hero Banners', 'type' => 'static', 'sort_order' => 1, 'is_active' => 1, 'data_source' => 'banners', 'filters_json' => null, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'categories_circles', 'title_ar' => 'التصنيفات', 'title_en' => 'Categories', 'type' => 'auto', 'sort_order' => 2, 'is_active' => 1, 'data_source' => 'categories', 'filters_json' => json_encode(['limit' => 12]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'flash_deals', 'title_ar' => 'عروض اليوم', 'title_en' => 'Flash Deals', 'type' => 'auto', 'sort_order' => 3, 'is_active' => 1, 'data_source' => 'discounted', 'filters_json' => json_encode(['limit' => 12]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'featured_products', 'title_ar' => 'منتجات مميزة', 'title_en' => 'Featured Products', 'type' => 'manual', 'sort_order' => 4, 'is_active' => 1, 'data_source' => 'products', 'filters_json' => json_encode(['limit' => 12]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'best_sellers', 'title_ar' => 'الأكثر مبيعًا', 'title_en' => 'Best Sellers', 'type' => 'auto', 'sort_order' => 5, 'is_active' => 1, 'data_source' => 'best_sellers', 'filters_json' => json_encode(['limit' => 12]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'new_arrivals', 'title_ar' => 'وصلنا حديثًا', 'title_en' => 'New Arrivals', 'type' => 'auto', 'sort_order' => 6, 'is_active' => 1, 'data_source' => 'new_arrivals', 'filters_json' => json_encode(['limit' => 12]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'collections', 'title_ar' => 'حسب احتياجك', 'title_en' => 'Collections', 'type' => 'auto', 'sort_order' => 7, 'is_active' => 1, 'data_source' => 'tags', 'filters_json' => json_encode(['tags' => ['برد وزكام', 'فيتامينات', 'عناية شعر', 'أطفال']]), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('home_section_items');
        Schema::dropIfExists('home_sections');
    }
};
