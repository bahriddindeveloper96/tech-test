<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductStockController extends Controller
{
    /**
     * Get stock info for a product
     */
    public function getStock($productId)
    {
        try {
            $product = Product::where('seller_id', auth()->id())
                ->with([
                    'translations',
                    'variants' => function($q) {
                        $q->select('id', 'product_id', 'sku', 'stock', 'attribute_values');
                    },
                    'variants.translations'
                ])
                ->findOrFail($productId);

            // Calculate total stock
            $totalStock = $product->variants->sum('stock');

            return response()->json([
                'message' => __('messages.stock.retrieved'),
                'data' => [
                    'total_stock' => $totalStock,
                    'variants' => $product->variants
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('messages.stock.error_retrieving'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update stock for a product variant
     */
    public function updateStock(Request $request, $productId, $variantId)
    {
        $request->validate([
            'stock' => 'required|integer|min:0',
            'reason' => 'required|string|in:purchase,return,adjustment,sale,damage',
            'note' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            $product = Product::where('seller_id', auth()->id())->findOrFail($productId);
            $variant = $product->variants()->findOrFail($variantId);

            // Calculate stock change
            $oldStock = $variant->stock;
            $newStock = $request->stock;
            $change = $newStock - $oldStock;

            // Update variant stock
            $variant->stock = $newStock;
            $variant->save();

            // Record stock movement
            $movement = StockMovement::create([
                'product_id' => $productId,
                'variant_id' => $variantId,
                'previous_stock' => $oldStock,
                'new_stock' => $newStock,
                'change' => $change,
                'reason' => $request->reason,
                'created_by' => auth()->id()
            ]);

            // Create translations for note
            foreach (config('app.available_locales') as $locale) {
                $movement->translations()->create([
                    'locale' => $locale,
                    'note' => $request->note
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => __('messages.stock.updated'),
                'data' => [
                    'variant' => $variant->load('translations'),
                    'stock_change' => $change,
                    'movement' => $movement->load('translations')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => __('messages.stock.error_updating'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock movement history
     */
    public function getStockHistory(Request $request, $productId)
    {
        try {
            $product = Product::where('seller_id', auth()->id())->findOrFail($productId);

            $query = StockMovement::where('product_id', $productId)
                ->with([
                    'translations',
                    'variant.translations',
                    'product.translations',
                    'creator'
                ]);

            // Filter by variant
            if ($request->has('variant_id')) {
                $query->where('variant_id', $request->variant_id);
            }

            // Filter by reason
            if ($request->has('reason')) {
                $query->where('reason', $request->reason);
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Sort movements
            if ($request->has('sort')) {
                switch ($request->sort) {
                    case 'oldest':
                        $query->oldest();
                        break;
                    default:
                        $query->latest();
                }
            } else {
                $query->latest();
            }

            return response()->json([
                'message' => __('messages.stock_history.retrieved'),
                'data' => $query->paginate($request->per_page ?? 10)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => __('messages.stock_history.error_retrieving'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get low stock alerts
     */
    public function getLowStockAlerts(Request $request)
    {
        try {
            $query = ProductVariant::whereHas('product', function($q) {
                    $q->where('seller_id', auth()->id());
                })
                ->with([
                    'translations',
                    'product.translations'
                ])
                ->where('stock', '<=', $request->threshold ?? 5)
                ->orderBy('stock');

            return response()->json([
                'message' => __('messages.low_stock.retrieved'),
                'data' => $query->get()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => __('messages.low_stock.error_retrieving'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock statistics
     */
    public function getStockStatistics()
    {
        try {
            // Get all products for this seller
            $products = Product::where('seller_id', auth()->id())
                ->with([
                    'translations',
                    'variants.translations'
                ])
                ->get();

            // Calculate statistics
            $totalProducts = $products->count();
            $totalVariants = $products->sum(function($product) {
                return $product->variants->count();
            });
            $totalStock = $products->sum(function($product) {
                return $product->variants->sum('stock');
            });
            $lowStockCount = $products->sum(function($product) {
                return $product->variants->where('stock', '<=', 5)->count();
            });
            $outOfStockCount = $products->sum(function($product) {
                return $product->variants->where('stock', 0)->count();
            });

            // Get recent stock movements
            $recentMovements = StockMovement::whereIn('product_id', $products->pluck('id'))
                ->with([
                    'translations',
                    'product.translations',
                    'variant.translations'
                ])
                ->latest()
                ->limit(5)
                ->get();

            return response()->json([
                'message' => __('messages.stock_statistics.retrieved'),
                'data' => [
                    'total_products' => $totalProducts,
                    'total_variants' => $totalVariants,
                    'total_stock' => $totalStock,
                    'low_stock_count' => $lowStockCount,
                    'out_of_stock_count' => $outOfStockCount,
                    'recent_movements' => $recentMovements
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => __('messages.stock_statistics.error_retrieving'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
