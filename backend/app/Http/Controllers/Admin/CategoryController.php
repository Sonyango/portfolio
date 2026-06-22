<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return CategoryResource::collection(Category::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'slug'          => 'nullable|string|unique:categories,slug',
            'description'   => 'nullable|string',
            'parent_id'     => 'nullable|exists:categories,id',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        return new CategoryResource(Category::create($data));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'          => 'sometimes|string|max:100',
            'slug'          => 'sometimes|string|unique:categories,slug,' . $category->id,
            'description'   => 'nullable|string',
            'parent_id'     => 'nullable|exists:categories,id',
        ]);

        $category->update($data);
        return new CategoryResource($category);
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(['message' => 'Category deleted']);
    }
}
