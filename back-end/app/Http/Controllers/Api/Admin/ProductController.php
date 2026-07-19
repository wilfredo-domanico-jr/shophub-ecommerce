<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'variants'])->withCount('variants')->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->string('search').'%');
        }

        return $query->paginate($request->integer('per_page', 10));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        [$options, $variants] = $this->pullVariantPayload($validated);
        $this->validateVariantIntegrity($options, $variants, null);

        $validated['slug'] = $this->uniqueSlug($validated['name']);

        $product = DB::transaction(function () use ($validated, $options, $variants) {
            $product = Product::create($validated);
            $this->syncVariants($product, $options, $variants);

            return $product;
        });

        return response()->json($product->load('category', 'variants'), 201);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $this->validated($request);
        [$options, $variants] = $this->pullVariantPayload($validated);
        $this->validateVariantIntegrity($options, $variants, $product);

        if ($validated['name'] !== $product->name) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $product->id);
        }

        DB::transaction(function () use ($product, $validated, $options, $variants) {
            $product->update($validated);
            $this->syncVariants($product, $options, $variants);
        });

        return $product->load('category', 'variants');
    }

    public function destroy(Product $product)
    {
        // Review rows go with the product via DB cascade (no model events),
        // so their photo files have to be removed here.
        Storage::disk('public')->delete(
            $product->reviews()->pluck('photos')->flatten()->filter()->all()
        );

        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'original_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'string'],
            'options' => ['sometimes', 'nullable', 'array', 'max:3'],
            'options.*.name' => ['required', 'string', 'max:50'],
            'options.*.values' => ['required', 'array', 'min:1'],
            'options.*.values.*' => ['required', 'string', 'max:50'],
            'variants' => ['sometimes', 'nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.option_values' => ['required', 'array'],
            'variants.*.option_values.*' => ['required', 'string', 'max:50'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['required', 'integer', 'min:0'],
            'variants.*.image' => ['nullable', 'string'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_flash_sale' => ['sometimes', 'boolean'],
            'flash_sale_goal' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_featured'] = $validated['is_featured'] ?? false;
        $validated['is_flash_sale'] = $validated['is_flash_sale'] ?? false;
        $validated['is_active'] = $validated['is_active'] ?? true;

        return $validated;
    }

    /**
     * Split the variant part out of the validated payload; options and
     * variants are persisted via syncVariants, never mass-assigned.
     *
     * @return array{0: array, 1: array}
     */
    private function pullVariantPayload(array &$validated): array
    {
        $options = array_values(array_map(fn ($option) => [
            'name' => trim($option['name']),
            'values' => array_values(array_map('trim', $option['values'])),
        ], $validated['options'] ?? []));

        $variants = array_values($validated['variants'] ?? []);

        unset($validated['options'], $validated['variants']);

        return [$options, $variants];
    }

    private function validateVariantIntegrity(array $options, array $variants, ?Product $product): void
    {
        $names = array_map(fn ($option) => Str::lower($option['name']), $options);

        if (count($names) !== count(array_unique($names))) {
            throw ValidationException::withMessages(['options' => 'Option names must be unique.']);
        }

        foreach ($options as $option) {
            $values = array_map(fn ($value) => Str::lower($value), $option['values']);

            if (count($values) !== count(array_unique($values))) {
                throw ValidationException::withMessages([
                    'options' => "Values for \"{$option['name']}\" must be unique.",
                ]);
            }
        }

        if (empty($options)) {
            if (! empty($variants)) {
                throw ValidationException::withMessages([
                    'variants' => 'Variants require at least one option (e.g. Color or Size).',
                ]);
            }

            return;
        }

        if (empty($variants)) {
            throw ValidationException::withMessages([
                'variants' => 'Add at least one variant for the defined options.',
            ]);
        }

        $optionValues = [];
        foreach ($options as $option) {
            $optionValues[$option['name']] = array_map(fn ($value) => Str::lower($value), $option['values']);
        }

        $existingIds = $product ? $product->variants()->pluck('id')->all() : [];
        $expectedNames = array_keys($optionValues);
        sort($expectedNames);
        $seenKeys = [];

        foreach ($variants as $index => $variant) {
            $line = $index + 1;
            $values = $variant['option_values'];

            $actualNames = array_keys($values);
            sort($actualNames);

            if ($actualNames !== $expectedNames) {
                throw ValidationException::withMessages([
                    'variants' => "Variant #{$line} must choose exactly one value for every option.",
                ]);
            }

            foreach ($values as $name => $value) {
                if (! in_array(Str::lower(trim($value)), $optionValues[$name], true)) {
                    throw ValidationException::withMessages([
                        'variants' => "Variant #{$line} uses \"{$value}\", which is not a listed {$name} value.",
                    ]);
                }
            }

            $key = ProductVariant::keyFor($values);

            if (isset($seenKeys[$key])) {
                throw ValidationException::withMessages([
                    'variants' => "Variant #{$line} duplicates another combination.",
                ]);
            }
            $seenKeys[$key] = true;

            if (! empty($variant['id']) && ! in_array((int) $variant['id'], $existingIds, true)) {
                throw ValidationException::withMessages([
                    'variants' => "Variant #{$line} does not belong to this product.",
                ]);
            }
        }
    }

    /**
     * Sync submitted variants: rows with an id are updated, missing ids are
     * deleted, the rest are created. Product stock becomes the variant sum so
     * every stock consumer (listings, badges, checkout gates) keeps working.
     */
    private function syncVariants(Product $product, array $options, array $variants): void
    {
        if (empty($options)) {
            $product->variants()->delete();
            $product->options = null;
            $product->save();

            return;
        }

        $keptIds = array_values(array_filter(array_map(fn ($variant) => $variant['id'] ?? null, $variants)));
        $product->variants()->whereNotIn('id', $keptIds)->delete();

        try {
            foreach ($variants as $variant) {
                $attributes = [
                    'option_values' => $variant['option_values'],
                    'price' => $variant['price'] ?? null,
                    'stock_quantity' => $variant['stock_quantity'],
                    'image' => $variant['image'] ?? null,
                ];

                if (! empty($variant['id'])) {
                    $product->variants()->whereKey($variant['id'])->first()?->update($attributes);
                } else {
                    $product->variants()->create($attributes);
                }
            }
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // Mid-loop collision with a kept row still holding its old key
            // (e.g. two rows swapping combinations) — a clean 422 beats a 500.
            throw ValidationException::withMessages([
                'variants' => 'Variant combinations conflict — regenerate the combinations and save again.',
            ]);
        }

        $product->options = $options;
        $product->stock_quantity = (int) $product->variants()->sum('stock_quantity');
        $product->save();
        $product->unsetRelation('variants');
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Product::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-".$i++;
        }

        return $slug;
    }
}
