<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;

class FlashSaleController extends Controller
{
    /**
     * The current-or-next flash sale event for the homepage countdown.
     * Wrapped as {sale: ...} so "nothing scheduled" is an unambiguous
     * {sale: null} (a bare null body would serialize as {}).
     */
    public function current()
    {
        $sale = FlashSale::upcomingOrLive()->first();

        return response()->json([
            'sale' => $sale ? [
                'title' => $sale->title,
                'starts_at' => $sale->starts_at,
                'ends_at' => $sale->ends_at,
                'is_live' => $sale->isLive(),
            ] : null,
        ]);
    }
}
