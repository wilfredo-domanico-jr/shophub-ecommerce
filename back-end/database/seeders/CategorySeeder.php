<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $icons = [
            'chip' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
            'shirt' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
            'home' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            'sparkle' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
            'bolt' => 'M13 10V3L4 14h7v7l9-11h-7z',
            'book' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
            'tag' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 10V5a2 2 0 012-2z',
            'cart' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
            'heart' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
            'truck' => 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1',
        ];

        $gradients = ['gradient-primary', 'gradient-secondary', 'gradient-accent', 'gradient-success'];

        $categories = [
            ['name' => 'Electronics', 'icon' => 'chip'],
            ['name' => 'Fashion', 'icon' => 'shirt'],
            ['name' => 'Home & Living', 'icon' => 'home'],
            ['name' => 'Beauty', 'icon' => 'sparkle'],
            ['name' => 'Sports & Outdoors', 'icon' => 'bolt'],
            ['name' => 'Books & Media', 'icon' => 'book'],
            ['name' => 'Toys & Games', 'icon' => 'tag'],
            ['name' => 'Groceries', 'icon' => 'cart'],
            ['name' => 'Health & Wellness', 'icon' => 'heart'],
            ['name' => 'Automotive', 'icon' => 'truck'],
            ['name' => 'Baby & Kids', 'icon' => 'sparkle'],
            ['name' => 'Pet Supplies', 'icon' => 'heart'],
            ['name' => 'Office Supplies', 'icon' => 'tag'],
            ['name' => 'Furniture', 'icon' => 'home'],
            ['name' => 'Jewelry & Watches', 'icon' => 'sparkle'],
            ['name' => 'Garden & Outdoor', 'icon' => 'bolt'],
            ['name' => 'Music & Instruments', 'icon' => 'tag'],
            ['name' => 'Video Games', 'icon' => 'chip'],
            ['name' => 'Travel & Luggage', 'icon' => 'cart'],
            ['name' => 'Arts & Crafts', 'icon' => 'sparkle'],
        ];

        foreach ($categories as $index => $category) {
            Category::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'description' => $category['name'] . ' products',
                    'icon' => $icons[$category['icon']],
                    'color_class' => $gradients[$index % count($gradients)],
                    'is_active' => true,
                ]
            );
        }
    }
}
