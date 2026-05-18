<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'ملابس نسائية',
                'is_active' => true,
            ],
            [
                'name' => 'اطفال',
                'is_active' => true,
            ],
            [
                'name' => 'جمال',
                'is_active' => true,
            ],
            [
                'name' => 'اكسسوارات',
                'is_active' => true,
            ],
            [
                'name' => 'عطور',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']) . '-' . uniqid(),
                'description' => null,
                'parent_id' => null,
                'type' => 'main',
                'image' => null,
                'is_active' => $category['is_active'],
            ]);
        }
    }
}