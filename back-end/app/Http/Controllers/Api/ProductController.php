<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->active()->with('category');

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
            $query->where('name', 'like', '%' . $request->string('search') . '%');
        }

        return $query->orderBy('name')->paginate($request->integer('per_page', 20));
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        return $product->load('category');
    }
}
