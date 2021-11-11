<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Store;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Store $store)
    {
        return response()->json([
            'data' => $store->categories
        ], 200);
    }

    public function store(Request $request, Store $store)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'parent_category_uuid'=> 'nullable|string',
        ]);


        if ($store->categories->where('name', $validated['name'])->first()) {
            return response()->json([
                'message' => 'The given data was invalid',
                'errors' => [
                    "name" => "Category name already exist"
                ]
            ]);
        }
        
        $category = ['name' => $validated['name']];

        if ($validated['parent_category_uuid']) {
            $parent_id = Category::where('uuid', $validated['parent_category_uuid'])->first()->id;
            $category = array_merge($category, ['parent_id' => $parent_id]);
        }

        $created = Category::create(array_merge($category, [
            'uuid' => (string) Str::uuid(),
            'store_id' => $store->id
        ]));
        return response()->json([
            'data'=> $created
        ], 200);
        
    }

    public function show(Store $store, Category $category)
    {
        return response()->json([
            'data'=> $category
        ]);
    }

    public function update(Request $request, Store $store, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'parent_category_uuid'=> 'nullable|string',
        ]);

        $newCategory = ['name' => $validated['name']];

        if (array_key_exists('parent_category_uuid', $validated)) {
            $parent_id = Category::where('uuid', $validated['parent_category_uuid'])->first()->id;
            $newCategory = array_merge($category, ['parent_id' => $parent_id]);
        
        }

        $category->update($newCategory);

        return response()->json([
            'data'=> $category
        ], 200);
    }

    public function destroy(Store $store, Category $category)
    {
        $isDeleted = Category::destroy($category->id);
        return response()->json([
            'message' => $isDeleted,
            'data' => $category,
        ], 200);
    }
}
