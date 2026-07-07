<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $electronics = Category::where('slug', 'electronics')->first();
        $fashion = Category::where('slug', 'fashion')->first();
        $home = Category::where('slug', 'home-living')->first();

        $flashSaleProducts = [
            ['name' => 'Wireless Bluetooth Earbuds Pro', 'price' => 499, 'original_price' => 1999, 'sold_count' => 35, 'flash_sale_goal' => 54, 'image' => 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=300&h=300&fit=crop', 'category' => $electronics],
            ['name' => 'Smart Watch Fitness Tracker', 'price' => 799, 'original_price' => 1999, 'sold_count' => 48, 'flash_sale_goal' => 60, 'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=300&h=300&fit=crop', 'category' => $electronics],
            ['name' => 'Premium Wireless Headphones', 'price' => 1499, 'original_price' => 2999, 'sold_count' => 27, 'flash_sale_goal' => 60, 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300&h=300&fit=crop', 'category' => $electronics],
            ['name' => 'Running Shoes Athletic Sport', 'price' => 899, 'original_price' => 2999, 'sold_count' => 54, 'flash_sale_goal' => 60, 'image' => 'https://images.unsplash.com/photo-1585386959984-a4155224a1ad?w=300&h=300&fit=crop', 'category' => $fashion],
            ['name' => 'Laptop Stand Aluminum', 'price' => 449, 'original_price' => 999, 'sold_count' => 43, 'flash_sale_goal' => 60, 'image' => 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=300&h=300&fit=crop', 'category' => $electronics],
        ];

        foreach ($flashSaleProducts as $product) {
            $this->createProduct($product, isFlashSale: true, isFeatured: false);
        }

        $trendingProducts = [
            ['name' => 'Premium Wireless Headphones (Trending)', 'price' => 1499, 'original_price' => 2999, 'sold_count' => 156, 'rating' => 4.5, 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300&h=300&fit=crop', 'category' => $electronics],
            ['name' => 'Smart Watch Series 7', 'price' => 2499, 'original_price' => 4999, 'sold_count' => 203, 'rating' => 4.8, 'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=300&h=300&fit=crop', 'category' => $electronics],
            ['name' => 'Wireless Gaming Mouse', 'price' => 799, 'original_price' => 1599, 'sold_count' => 189, 'rating' => 4.6, 'image' => 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=300&h=300&fit=crop', 'category' => $electronics],
            ['name' => 'Mechanical Keyboard RGB', 'price' => 1899, 'original_price' => 3499, 'sold_count' => 142, 'rating' => 4.7, 'image' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=300&h=300&fit=crop', 'category' => $electronics],
            ['name' => '4K Webcam Pro', 'price' => 1299, 'original_price' => 2599, 'sold_count' => 98, 'rating' => 4.4, 'image' => 'https://images.unsplash.com/photo-1600456899121-68eda5705257?w=300&h=300&fit=crop', 'category' => $electronics],
            ['name' => 'Portable Bluetooth Speaker', 'price' => 599, 'original_price' => 1299, 'sold_count' => 267, 'rating' => 4.3, 'image' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=300&h=300&fit=crop', 'category' => $electronics],
            ['name' => 'USB-C Hub Multiport', 'price' => 899, 'original_price' => 1799, 'sold_count' => 178, 'rating' => 4.5, 'image' => 'https://images.unsplash.com/photo-1625948515291-69613efd103f?w=300&h=300&fit=crop', 'category' => $electronics],
            ['name' => 'Ergonomic Office Chair', 'price' => 3999, 'original_price' => 7999, 'sold_count' => 89, 'rating' => 4.9, 'image' => 'https://images.unsplash.com/photo-1580480055273-228ff5388ef8?w=300&h=300&fit=crop', 'category' => $home],
            ['name' => 'LED Desk Lamp', 'price' => 449, 'original_price' => 899, 'sold_count' => 321, 'rating' => 4.2, 'image' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=300&h=300&fit=crop', 'category' => $home],
            ['name' => 'Phone Stand Holder', 'price' => 199, 'original_price' => 499, 'sold_count' => 445, 'rating' => 4.1, 'image' => 'https://images.unsplash.com/photo-1601784551446-20c9e07cdbdb?w=300&h=300&fit=crop', 'category' => $electronics],
        ];

        foreach ($trendingProducts as $product) {
            $this->createProduct($product, isFlashSale: false, isFeatured: true);
        }
    }

    private function createProduct(array $data, bool $isFlashSale, bool $isFeatured): void
    {
        $slug = Str::slug($data['name']);

        Product::firstOrCreate(
            ['slug' => $slug],
            [
                'category_id' => $data['category']->id,
                'name' => $data['name'],
                'description' => $data['name'] . ' — great quality, fast shipping.',
                'price' => $data['price'],
                'original_price' => $data['original_price'],
                'stock_quantity' => 100,
                'image' => $data['image'],
                'is_featured' => $isFeatured,
                'is_flash_sale' => $isFlashSale,
                'sold_count' => $data['sold_count'],
                'flash_sale_goal' => $data['flash_sale_goal'] ?? null,
                'rating' => $data['rating'] ?? 4.5,
                'is_active' => true,
            ]
        );
    }
}
