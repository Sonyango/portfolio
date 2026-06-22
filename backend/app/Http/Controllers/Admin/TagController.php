<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index()
    {
        return TagResource::collection(tag::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:100|unique:tags,name',
            'slug'  => 'nullable|string|unique:tags,slug',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        return new TagResource(Tag::create($data));
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();
        return response()->json(['message' => 'Tag deleted.']);
    }
}
