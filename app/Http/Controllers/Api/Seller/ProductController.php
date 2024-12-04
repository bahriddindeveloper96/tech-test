<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->where('seller_id', auth()->id())
            ->with(['category', 'variants', 'translations']);

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('active', $request->status === 'active');
        }

        // Sort products
        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'sales':
                    $query->withCount('orderItems')
                          ->orderByDesc('order_items_count');
                    break;
                case 'latest':
                    $query->latest();
                    break;
                default:
                    $query->latest();
            }
        } else {
            $query->latest();
        }

        return response()->json([
            'products' => $query->paginate($request->per_page ?? 10)
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'translations' => 'required|array',
            'translations.uz' => 'required|array',
            'translations.uz.name' => 'required|string|max:255',
            'translations.uz.description' => 'required|string',
            'translations.ru' => 'required|array',
            'translations.ru.name' => 'required|string|max:255',
            'translations.ru.description' => 'required|string',
            'translations.en' => 'required|array',
            'translations.en.name' => 'required|string|max:255',
            'translations.en.description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'images' => 'required|array|min:1',
            'images.*' => 'required|string',
            'attributes' => 'array',
            'variants' => 'array',
            'variants.*.attribute_values' => 'required|array',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0'
        ]);

        try {
            DB::beginTransaction();

            // Generate unique slug
            $slug = Str::slug($request->input('translations.en.name'));
            $originalSlug = $slug;
            $count = 1;
            
            while (Product::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }

            // Create product
            $product = Product::create([
                'category_id' => $request->input('category_id'),
                'seller_id' => auth()->id(),
                'slug' => $slug,
                'price' => $request->input('price'),
                'images' => $request->input('images'),
                'active' => false, // New products need admin approval
                'attributes' => $request->input('attributes', [])
            ]);

            // Create translations
            foreach ($request->translations as $locale => $translation) {
                $product->translations()->create([
                    'locale' => $locale,
                    'name' => $translation['name'],
                    'description' => $translation['description']
                ]);
            }

            // Create variants if provided, otherwise create default variant
            if ($request->has('variants')) {
                foreach ($request->variants as $variantData) {
                    $product->variants()->create([
                        'attribute_values' => $variantData['attribute_values'],
                        'price' => $variantData['price'],
                        'stock' => $variantData['stock'],
                        'sku' => strtoupper(Str::slug($request->input('translations.en.name'))) . '-' . strtoupper(Str::random(4)),
                        'active' => false
                    ]);
                }
            } else {
                $product->variants()->create([
                    'name' => 'Default',
                    'price' => $request->input('price'),
                    'stock' => 0,
                    'active' => false,
                    'sku' => strtoupper(Str::slug($request->input('translations.en.name'))) . '-' . strtoupper(Str::random(4)),
                    'attribute_values' => []
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Product created successfully and waiting for approval',
                'data' => $product->load(['translations', 'variants'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error creating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $product = Product::where('seller_id', auth()->id())
                ->with(['translations', 'variants', 'category'])
                ->findOrFail($id);

            return response()->json([
                'message' => 'Product retrieved successfully',
                'data' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Product not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $product = Product::where('seller_id', auth()->id())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'category_id' => 'exists:categories,id',
            'images' => 'array',
            'translations' => 'array',
            'translations.*.locale' => 'required|in:en,ru,uz',
            'translations.*.name' => 'required|string',
            'translations.*.description' => 'required|string',
            'attributes' => 'array',
            'variants' => 'array',
            'variants.*.attribute_values' => 'required|array',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update product
            $product->update($request->only([
                'category_id', 'images'
            ]));

            // Update translations
            if ($request->has('translations')) {
                foreach ($request->translations as $translation) {
                    ProductTranslation::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'locale' => $translation['locale']
                        ],
                        [
                            'name' => $translation['name'],
                            'description' => $translation['description']
                        ]
                    );
                }
            }

            // Update attributes
            if ($request->has('attributes')) {
                $product->attributes = $request->attributes;
                $product->save();
            }

            // Update variants
            if ($request->has('variants')) {
                // Delete old variants
                $product->variants()->delete();
                
                // Create new variants
                foreach ($request->variants as $variantData) {
                    $product->variants()->create([
                        'attribute_values' => $variantData['attribute_values'],
                        'price' => $variantData['price'],
                        'stock' => $variantData['stock'],
                        'sku' => strtoupper(Str::slug($product->translations->where('locale', 'en')->first()->name)) . '-' . strtoupper(Str::random(4)),
                        'active' => $product->active
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product->load(['translations', 'category', 'variants'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating product: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::where('seller_id', auth()->id())->findOrFail($id);
            
            DB::beginTransaction();
            
            // Delete translations
            $product->translations()->delete();
            
            // Delete variants
            $product->variants()->delete();
            
            // Delete product
            $product->delete();
            
            DB::commit();
            
            return response()->json([
                'message' => 'Product deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error deleting product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCategories()
    {
        try {
            $categories = Category::with('translations')->get();
            
            return response()->json([
                'message' => 'Categories retrieved successfully',
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAttributes()
    {
        try {
            $attributes = Attribute::with(['group', 'values'])->get();
            
            return response()->json([
                'message' => 'Attributes retrieved successfully',
                'data' => $attributes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving attributes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStatistics()
    {
        try {
            $totalProducts = Product::where('seller_id', auth()->id())->count();
            $activeProducts = Product::where('seller_id', auth()->id())->where('active', true)->count();
            $totalSales = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.seller_id', auth()->id())
                ->sum('order_items.quantity');
            $totalRevenue = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.seller_id', auth()->id())
                ->sum(DB::raw('order_items.quantity * order_items.price'));
            
            return response()->json([
                'message' => 'Statistics retrieved successfully',
                'data' => [
                    'total_products' => $totalProducts,
                    'active_products' => $activeProducts,
                    'total_sales' => $totalSales,
                    'total_revenue' => $totalRevenue
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