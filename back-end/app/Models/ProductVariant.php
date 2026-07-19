<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'option_values',
        'variant_key',
        'price',
        'stock_quantity',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'option_values' => 'array',
            'price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ProductVariant $variant) {
            $variant->variant_key = static::keyFor($variant->option_values ?? []);
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Deterministic key for a combination, e.g. "color=red|size=m".
     * Sorted by option name (not display order) so reordering the
     * product's options never changes existing keys.
     */
    public static function keyFor(array $optionValues): string
    {
        $pairs = [];

        foreach ($optionValues as $name => $value) {
            $pairs[Str::lower(trim((string) $name))] = Str::lower(trim((string) $value));
        }

        ksort($pairs);

        return implode('|', array_map(
            fn ($name, $value) => "$name=$value",
            array_keys($pairs),
            array_values($pairs),
        ));
    }

    /**
     * Human label following the product's option display order, e.g. "Red / M".
     */
    public function labelFor(Product $product): string
    {
        $values = $this->option_values ?? [];
        $ordered = [];

        foreach ($product->options ?? [] as $option) {
            if (isset($values[$option['name']])) {
                $ordered[] = $values[$option['name']];
            }
        }

        return implode(' / ', $ordered ?: array_values($values));
    }
}
