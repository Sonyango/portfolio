<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::published()
            ->latestFirst()
            ->with(['user', 'categories', 'tags'])
            ->when($request->category, fn($q) =>
                $q->whereHas('categories', fn($q) =>
                    $q->where('slug', $request->tag)
                )
            )
            -> paginate(9);

        return PostResource::collection($posts);
    }

    public function show(string $slug)
    {
        $post = Post::published()
            ->with(['user', 'categories', 'tags'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new PostResource($post);
    }
}
