<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()
            ->whereHas('items.product', function($q) {
                $q->where('seller_id', auth()->id());
            })
            ->with(['items' => function($q) {
                $q->whereHas('product', function($query) {
                    $query->where('seller_id', auth()->id());
                })->with(['product.translations']);
            }, 'user', 'address', 'paymentMethod', 'deliveryMethod']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search by order ID or customer name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('user', function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // Sort orders
        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'latest':
                    $query->latest();
                    break;
                case 'oldest':
                    $query->oldest();
                    break;
                case 'total_high':
                    $query->orderByDesc('total_amount');
                    break;
                case 'total_low':
                    $query->orderBy('total_amount');
                    break;
                default:
                    $query->latest();
            }
        } else {
            $query->latest();
        }

        $orders = $query->paginate($request->per_page ?? 10);

        // Calculate seller's portion for each order
        foreach ($orders as $order) {
            $sellerTotal = 0;
            foreach ($order->items as $item) {
                if ($item->product && $item->product->seller_id == auth()->id()) {
                    $sellerTotal += $item->price * $item->quantity;
                }
            }
            $order->seller_total = $sellerTotal;
        }

        return response()->json([
            'orders' => $orders
        ]);
    }

    public function show($id)
    {
        $order = Order::with(['items' => function($q) {
                $q->whereHas('product', function($query) {
                    $query->where('seller_id', auth()->id());
                })->with(['product.translations']);
            }, 'user', 'address', 'paymentMethod', 'deliveryMethod'])
            ->findOrFail($id);

        // Check if order contains any products from this seller
        if ($order->items->isEmpty()) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        // Calculate seller's portion
        $sellerTotal = 0;
        foreach ($order->items as $item) {
            if ($item->product && $item->product->seller_id == auth()->id()) {
                $sellerTotal += $item->price * $item->quantity;
            }
        }
        $order->seller_total = $sellerTotal;

        return response()->json([
            'order' => $order
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:processing,shipped,delivered,cancelled'
        ]);

        $order = Order::findOrFail($id);

        // Check if order contains any products from this seller
        $hasSellerProducts = $order->items()->whereHas('product', function($q) {
            $q->where('seller_id', auth()->id());
        })->exists();

        if (!$hasSellerProducts) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        // Update status only for seller's items
        $order->items()
            ->whereHas('product', function($q) {
                $q->where('seller_id', auth()->id());
            })
            ->update(['status' => $request->status]);

        // Check if all items have same status to update main order status
        $statuses = $order->items->pluck('status')->unique();
        if ($statuses->count() === 1) {
            $order->status = $statuses->first();
            $order->save();
        }

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order->fresh()->load(['items.product', 'user', 'address'])
        ]);
    }

    public function getStatistics(Request $request)
    {
        try {
            $query = OrderItem::query()
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.seller_id', auth()->id());

            // Filter by date range
            if ($request->has('from_date')) {
                $query->whereDate('order_items.created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('order_items.created_at', '<=', $request->to_date);
            }

            $totalOrders = $query->select('order_id')->distinct()->count();
            $totalSales = $query->sum(DB::raw('order_items.price * order_items.quantity'));
            $totalItems = $query->sum('order_items.quantity');

            // Get status counts
            $statusCounts = $query->select('order_items.status', DB::raw('count(*) as count'))
                ->groupBy('order_items.status')
                ->get()
                ->pluck('count', 'status');

            // Get top selling products
            $topProducts = $query->select(
                    'products.id',
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                    DB::raw('SUM(order_items.price * order_items.quantity) as total_sales')
                )
                ->groupBy('products.id')
                ->orderByDesc('total_quantity')
                ->limit(5)
                ->get();

            return response()->json([
                'statistics' => [
                    'total_orders' => $totalOrders,
                    'total_sales' => $totalSales,
                    'total_items' => $totalItems,
                    'status_counts' => $statusCounts,
                    'top_products' => $topProducts
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}