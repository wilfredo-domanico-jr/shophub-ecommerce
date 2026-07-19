<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    public function index()
    {
        return Voucher::latest()->get();
    }

    public function store(Request $request)
    {
        $voucher = Voucher::create($this->validated($request));

        return response()->json($voucher, 201);
    }

    public function update(Request $request, Voucher $voucher)
    {
        $voucher->update($this->validated($request, $voucher));

        return $voucher;
    }

    public function destroy(Voucher $voucher)
    {
        $voucher->delete();

        return response()->json(['message' => 'Voucher removed']);
    }

    private function validated(Request $request, ?Voucher $voucher = null): array
    {
        // Uppercase before validating so uniqueness is case-insensitive on
        // MySQL and SQLite alike (codes are always stored uppercase).
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('vouchers', 'code')->ignore($voucher?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in([Voucher::TYPE_PERCENT, Voucher::TYPE_FIXED])],
            'value' => array_merge(
                ['required', 'numeric', 'min:0.01'],
                $request->input('type') === Voucher::TYPE_PERCENT ? ['max:100'] : [],
            ),
            'max_discount' => ['nullable', 'numeric', 'min:0.01', 'prohibited_unless:type,'.Voucher::TYPE_PERCENT],
            'min_spend' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['is_public'] = $validated['is_public'] ?? false;

        return $validated;
    }
}
