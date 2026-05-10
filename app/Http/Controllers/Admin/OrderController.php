<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $storeOrders = Order::query()
            ->withCount('items')
            ->whereDoesntHave('posSale')
            ->latest()
            ->paginate(12, ['*'], 'store_page');

        $manualOrders = Order::query()
            ->with(['posSale'])
            ->withCount('items')
            ->whereHas('posSale')
            ->latest()
            ->paginate(12, ['*'], 'manual_page');

        $stats = [
            'store_count' => Order::whereDoesntHave('posSale')->count(),
            'manual_count' => Order::whereHas('posSale')->count(),
            'store_pending' => Order::whereDoesntHave('posSale')->whereIn('status', ['new', 'preparing', 'shipped'])->count(),
            'manual_today' => Order::whereHas('posSale')->whereDate('created_at', today())->count(),
        ];

        return view('admin.orders.index', compact('storeOrders', 'manualOrders', 'stats'));
    }

    public function show(Order $order)
    {
        $order->load('items');

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:new,preparing,shipped,completed,cancelled',
        ]);

        $order->update(['status' => $request->string('status')->value()]);

        return back()->with('success', 'تم تحديث حالة الطلب');
    }
}
