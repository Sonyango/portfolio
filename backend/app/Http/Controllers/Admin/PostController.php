<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['user', 'categories', 'tags'])
            ->latest()
            ->paginate(15);

        return PostResource::collection($posts);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'slug'          => 'nullable|string|unique:posts,slug',
            'content'       => 'required|string',
            'excerpt'       => 'nullable|string|max:500',
            'status'        => 'required|in:draft,published,scheduled',
            'published_at'  => 'nullable|date',
            'categories'    => 'nullable|array',
            'categories.*'  => 'exists:categories,id',
            'tags'          => 'nullable|array',
            'tags.*'        => 'exists:tags,id',
            'thumbnail'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data['slug']       = $data['slug'] ?? Str::slug($data['title']);
        $data['user_id']    = auth()->id();

        // Auto-set published_at when status is published
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        // For scheduled posts, published_at is required
        if ($data['status'] === 'scheduled' && empty($data['published_at'])) {
            return response()->json([
                'message' => 'Publish date is required for scheduled posts.',
                'error' => ['published_at' => ['Publish date is required for scheduled posts.']]
            ], 422);
        }

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')
                ->store('posts/thumbnails', 'public');
        }

        $post = Post::create($data);

        if (!empty($data['categories'])) {
            $post->categories()->sync($data['categories']);
        }

        if (!empty($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }

        return new PostResource($post->load(['user', 'categories', 'tags']));
    }

    public function update(request $request, Post $post)
    {
        $data = $request->validate([
            'title'         => 'sometimes|string|max:255',
            'slug'          => 'sometimes|string|unique:posts,slug,' . $post->id,
            'content'       => 'sometimes|string',
            'excerpt'       => 'nullable|string|max:500',
            'status'        => 'sometimes|in:draft,published,scheduled',
            'published_at'  => 'nullable|date',
            'categories'    => 'nullable|array',
            'categories.*'  => 'exists:categories,id',
            'tags'          => 'nullable|array',
            'tags.*'        => 'exists:tags,id',
            'thumbnail'     => 'nullable|image|mimes:jpg,jpeg,png,,webp|max:2048',
        ]);

        // Auto-set published_at when status is published
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        // For sheduled posts, published_at is required
        if ($data['status'] === 'scheduled' && empty($data['published_at'])) {
            return response()->json([
                'message' => 'Publish datet is required for scheduled posts.',
                'errors' => ['published_at' => ['Publish date is required for scheduled posts.']]
            ], 422);
        }

        if ($request->hasFile('thumbnail')) {
            if ($post->thumbnail) {
                Storage::disk('public')->delete($post->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')
                ->store('posts/thumbnails', 'public');
        }

        $post->update($data);

        if (isset($data['categories'])) {
            $post->categories()->sync($data['categories']);
        }

        if (isset($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }

        return new PostResource($post->load(['user', 'categories', 'tags']));
    }

    public function destroy(Post $post)
    {
        if ($post->thumbnail) {
            Storage::disk('public')->delete($post->thumbnail);
        }
        $post->delete();

        return response()->json(['message' => 'Post deleted.']);
    }
}
