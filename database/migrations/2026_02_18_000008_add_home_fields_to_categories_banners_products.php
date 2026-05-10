<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ar');
            $table->string('image')->nullable()->after('slug');
            $table->index('name_ar');
            $table->index('name_en');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('tags')->nullable()->after('featured');
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->string('image')->nullable()->after('subtitle');
            $table->string('link_type')->default('url')->after('image');
            $table->string('link_target')->nullable()->after('link_type');
            $table->date('start_date')->nullable()->after('link_target');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn(['image', 'link_type', 'link_target', 'start_date', 'end_date']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('tags');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['name_ar']);
            $table->dropIndex(['name_en']);
            $table->dropColumn(['name_ar', 'name_en', 'image']);
        });
    }
};
