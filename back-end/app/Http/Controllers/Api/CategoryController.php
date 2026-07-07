<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return Category::query()
            ->active()
            ->withCount(['products' => fn ($query) => $query->active()])
            ->orderBy('name')
            ->get();
    }

    public function show(Category $category)
    {
        abort_unless($category->is_active, 404);

        return $category->loadCount(['products' => fn ($query) => $query->active()]);
    }
}
