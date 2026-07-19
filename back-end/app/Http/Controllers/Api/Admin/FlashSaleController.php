<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use Illuminate\Http\Request;

class FlashSaleController extends Controller
{
    public function index()
    {
        return FlashSale::orderByDesc('starts_at')->get();
    }

    public function store(Request $request)
    {
        $sale = FlashSale::create($this->validated($request));

        return response()->json($sale, 201);
    }

    public function update(Request $request, FlashSale $flashSale)
    {
        $flashSale->update($this->validated($request));

        return $flashSale;
    }

    public function destroy(FlashSale $flashSale)
    {
        $flashSale->delete();

        return response()->json(['message' => 'Flash sale removed']);
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        return $validated;
    }
}
