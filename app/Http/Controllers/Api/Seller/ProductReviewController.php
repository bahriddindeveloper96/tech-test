<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductReviewController extends Controller
{
    /**
     * Display a listing of reviews for seller's products
     */
    public function index(Request $request)
    {
        $query = ProductReview::query()
            ->whereHas('product', function($q) {
                $q->where('seller_id', auth()->id());
            })
            ->with(['product.translations', 'user', 'translations']);

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by rating
        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        // Filter by approval status
        if ($request->has('is_approved')) {
            $query->where('is_approved', $request->is_approved);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Sort reviews
        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'rating_high':
                    $query->orderByDesc('rating');
                    break;
                case 'rating_low':
                    $query->orderBy('rating');
                    break;
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
            'reviews' => $query->paginate($request->per_page ?? 10)
        ]);
    }

    /**
     * Display specific review details
     */
    public function show($id)
    {
        $review = ProductReview::whereHas('product', function($q) {
                $q->where('seller_id', auth()->id());
            })
            ->with(['product.translations', 'user', 'translations'])
            ->findOrFail($id);

        return response()->json([
            'review' => $review
        ]);
    }

    /**
     * Reply to a review
     */
    public function reply(Request $request, $id)
    {
        $request->validate([
            'reply' => 'required|string|max:1000',
        ]);

        $review = ProductReview::whereHas('product', function($q) {
                $q->where('seller_id', auth()->id());
            })
            ->findOrFail($id);

        $review->seller_reply = $request->reply;
        $review->replied_at = now();
        $review->save();

        return response()->json([
            'message' => 'Reply added successfully',
            'review' => $review->fresh()->load(['product.translations', 'user', 'translations'])
        ]);
    }

    /**
     * Report inappropriate review
     */
    public function report(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $review = ProductReview::whereHas('product', function($q) {
                $q->where('seller_id', auth()->id());
            })
            ->findOrFail($id);

        $review->is_reported = true;
        $review->report_reason = $request->reason;
        $review->reported_at = now();
        $review->save();

        return response()->json([
            'message' => 'Review reported successfully',
            'review' => $review->fresh()
        ]);
    }

    /**
     * Get review statistics for seller's products
     */
    public function getStatistics(Request $request)
    {
        try {
            $query = ProductReview::query()
                ->whereHas('product', function($q) {
                    $q->where('seller_id', auth()->id());
                });

            // Filter by date range
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Total reviews
            $totalReviews = $query->count();
            
            // Average rating
            $averageRating = $query->avg('rating');

            // Rating distribution
            $ratingDistribution = $query->select('rating', DB::raw('count(*) as count'))
                ->groupBy('rating')
                ->get()
                ->pluck('count', 'rating');

            // Products with most reviews
            $topReviewedProducts = Product::where('seller_id', auth()->id())
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->orderByDesc('reviews_count')
                ->limit(5)
                ->get();

            // Recent reviews needing attention (reported or low rating)
            $attentionNeeded = $query->where(function($q) {
                    $q->where('rating', '<=', 2)
                        ->orWhere('is_reported', true)
                        ->whereNull('seller_reply');
                })
                ->with(['product.translations', 'user'])
                ->latest()
                ->limit(5)
                ->get();

            return response()->json([
                'statistics' => [
                    'total_reviews' => $totalReviews,
                    'average_rating' => round($averageRating, 2),
                    'rating_distribution' => $ratingDistribution,
                    'top_reviewed_products' => $topReviewedProducts,
                    'attention_needed' => $attentionNeeded
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
