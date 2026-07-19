<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->active()->with('category')->withCount('variants');

        if ($request->boolean('featured')) {
            $query->featured();
        }

        if ($request->boolean('flash_sale')) {
            $query->flashSale();
        }

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->string('category')));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->string('search').'%');
        }

        match ($request->string('sort')->toString()) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('name'),
        };

        return $query->paginate($request->integer('per_page', 20));
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        return $product->load('category', 'variants');
    }
}
