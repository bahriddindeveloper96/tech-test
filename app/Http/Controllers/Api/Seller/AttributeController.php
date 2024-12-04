<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function groups()
    {
        $groups = AttributeGroup::with('attributes')->get();
        return response()->json($groups);
    }

    public function attributes(AttributeGroup $group)
    {
        return response()->json($group->attributes);
    }

    public function storeAttribute(Request $request, AttributeGroup $group)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,number,select,boolean,date',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'position' => 'integer',
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string',
            'translations.*.name' => 'required|string'
        ]);

        $attribute = $group->attributes()->create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'is_required' => $validated['is_required'] ?? false,
            'is_filterable' => $validated['is_filterable'] ?? false,
            'position' => $validated['position'] ?? 0
        ]);

        foreach ($request->translations as $translation) {
            $attribute->translations()->create([
                'locale' => $translation['locale'],
                'name' => $translation['name']
            ]);
        }

        return response()->json($attribute->load('translations'));
    }

    public function updateAttribute(Request $request, AttributeGroup $group, Attribute $attribute)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'type' => 'in:text,number,select,boolean,date',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'position' => 'integer',
            'translations' => 'array',
            'translations.*.locale' => 'required_with:translations|string',
            'translations.*.name' => 'required_with:translations|string'
        ]);

        $attribute->update($validated);

        if ($request->has('translations')) {
            foreach ($request->translations as $translation) {
                $attribute->translations()->updateOrCreate(
                    ['locale' => $translation['locale']],
                    ['name' => $translation['name']]
                );
            }
        }

        return response()->json($attribute->load('translations'));
    }

    public function deleteAttribute(AttributeGroup $group, Attribute $attribute)
    {
        $attribute->delete();
        return response()->json(['message' => 'Attribute deleted successfully']);
    }
}
