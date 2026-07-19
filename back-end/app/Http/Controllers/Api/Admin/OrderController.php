<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderStatusUpdatedMail;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()->withCount('items')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        return $query->paginate($request->integer('per_page', 20));
    }

    public function show(Order $order)
    {
        return $order->load('items.product');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,processing,shipped,delivered,cancelled'],
        ]);

        // Deliberate simplification: cancelling does NOT restock items,
        // roll back sold_count, or release the voucher redemption — admins
        // adjust stock manually for the rare cancellation.
        $order->update(['status' => $validated['status']]);
        $order->load('items');

        Mail::to($order->customer_email)->queue(new OrderStatusUpdatedMail($order));

        return $order;
    }
}
