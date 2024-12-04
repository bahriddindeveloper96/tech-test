<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductAttributeController extends Controller
{
    /**
     * Get all attributes with their values
     */
    public function getAttributes()
    {
        try {
            $attributes = Attribute::with([
                'translations',
                'group.translations',
                'values.translations'
            ])->get();
            
            return response()->json([
                'message' => __('messages.attributes.retrieved'),
                'data' => $attributes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('messages.attributes.error_retrieving'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attributes for specific product
     */
    public function getProductAttributes($productId)
    {
        try {
            $product = Product::where('seller_id', auth()->id())
                ->with([
                    'attributes.translations',
                    'attributes.group.translations',
                    'attributes.values.translations'
                ])
                ->findOrFail($productId);

            return response()->json([
                'message' => __('messages.product_attributes.retrieved'),
                'data' => $product->attributes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('messages.product_attributes.error_retrieving'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product attributes
     */
    public function updateProductAttributes(Request $request, $productId)
    {
        $request->validate([
            'attributes' => 'required|array',
            'attributes.*.attribute_id' => 'required|exists:attributes,id',
            'attributes.*.values' => 'required|array',
            'attributes.*.values.*' => 'required|exists:attribute_values,id'
        ]);

        try {
            $product = Product::where('seller_id', auth()->id())->findOrFail($productId);

            DB::beginTransaction();

            // Clear existing attributes
            $product->attributes()->detach();

            // Attach new attributes with values
            foreach ($request->attributes as $attr) {
                foreach ($attr['values'] as $valueId) {
                    $product->attributes()->attach($attr['attribute_id'], [
                        'value_id' => $valueId
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => __('messages.product_attributes.updated'),
                'data' => $product->load([
                    'attributes.translations',
                    'attributes.values.translations'
                ])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => __('messages.product_attributes.error_updating'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attribute combinations for variants
     */
    public function getAttributeCombinations(Request $request)
    {
        $request->validate([
            'attributes' => 'required|array',
            'attributes.*' => 'required|exists:attributes,id'
        ]);

        try {
            $combinations = [];
            $attributes = Attribute::with(['translations', 'values.translations'])
                ->whereIn('id', $request->attributes)
                ->get();

            // Generate all possible combinations
            $values = $attributes->map(function ($attribute) {
                return $attribute->values->map(function($value) {
                    return [
                        'id' => $value->id,
                        'value' => $value->value,
                        'attribute' => [
                            'id' => $value->attribute->id,
                            'name' => $value->attribute->name
                        ]
                    ];
                })->toArray();
            })->toArray();

            $combinations = $this->generateCombinations($values);

            return response()->json([
                'message' => __('messages.attribute_combinations.generated'),
                'data' => $combinations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => __('messages.attribute_combinations.error_generating'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper function to generate combinations
     */
    private function generateCombinations($arrays, $i = 0)
    {
        if (!isset($arrays[$i])) {
            return array();
        }
        if ($i == count($arrays) - 1) {
            return $arrays[$i];
        }

        $tmp = $this->generateCombinations($arrays, $i + 1);
        $result = array();

        foreach ($arrays[$i] as $v) {
            foreach ($tmp as $t) {
                $result[] = is_array($t) ? array_merge(array($v), $t) : array($v, $t);
            }
        }

        return $result;
    }
}
