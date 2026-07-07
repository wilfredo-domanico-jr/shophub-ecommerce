<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $nonCancelled = Order::query()->where('status', '!=', 'cancelled');

        $totalSales = (clone $nonCancelled)->sum('total');
        $ordersCount = Order::count();
        $productsCount = Product::count();
        $customersCount = Order::distinct('customer_email')->count('customer_email');

        $since = Carbon::now()->subDays(6)->startOfDay();

        $salesByDay = (clone $nonCancelled)
            ->where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($row) => $row->date);

        $salesSeries = collect(range(0, 6))->map(function ($i) use ($since, $salesByDay) {
            $date = $since->copy()->addDays($i)->format('Y-m-d');

            return [
                'date' => $date,
                'label' => Carbon::parse($date)->format('D'),
                'total' => (float) ($salesByDay->get($date)->total ?? 0),
            ];
        });

        $recentOrders = Order::query()->latest()->take(8)->get([
            'id', 'order_number', 'customer_name', 'status', 'total', 'created_at',
        ]);

        return response()->json([
            'total_sales' => (float) $totalSales,
            'orders_count' => $ordersCount,
            'products_count' => $productsCount,
            'customers_count' => $customersCount,
            'sales_series' => $salesSeries,
            'recent_orders' => $recentOrders,
        ]);
    }
}
