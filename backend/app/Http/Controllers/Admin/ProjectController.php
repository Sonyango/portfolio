<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with('images')->ordered()->get();
        return ProjectResource::collection($projects);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'slug'          => 'nullable|string|unique:projects,slug',
            'description'   => 'required|string',
            'content'       => 'nullable|string',
            'tech_stack'    => 'nullable|array',
            'tech_stack.*'  => 'string|max:100',
            'live_url'      => 'nullable|url',
            'github_url'    => 'nullable|url',
            'category'      => 'nullable|string|max:100',
            'featured'      => 'nullable',
            'order'         => 'nullable|integer',
            'published'     => 'nullable',
            'thumbnail'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Generate slug if not provided
        $data['slug'] = $data['slug']
            ? Str::slug($data['slug'])
            : Str::slug($data['title']);

        // Ensure slug is unique
        $originalSlug = $data['slug'];
        $counter      = 1;
        while (Project::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $counter++;
        }

        // Handle boolean fields from FormData strings
        $data['featured'] = filter_var(
            $request->input('featured', false),
            FILTER_VALIDATE_BOOLEAN
        );
        $data['published'] = filter_var(
            $request->input('published', true),
            FILTER_VALIDATE_BOOLEAN
        );
        $data['order']  = (int) $request->input('order', 0);

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')
                ->store('projects/thumbnails', 'public');
        }

        $project = Project::create($data);
        return new ProjectResource($project->load('images'));
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'title'         => 'sometimes|string|max:255',
            'slug'          => 'sometimes|string|unique:projects,slug,' . $project->id,
            'description'   => 'sometimes|string',
            'content'       => 'nullable|string',
            'tech_stack'    => 'nullable|array',
            'tech_stack.*'  => 'string|max:100',
            'live_url'      => 'nullable|url',
            'github_url'    => 'nullable|url',
            'category'      => 'nullable|string|max:100',
            'featured'      => 'nullable',
            'order'         => 'nullable|integer',
            'published'     => 'nullable',
            'thumbnail'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // handle boolean fields from FormData strings
        if ($request->has('featured')) {
            $data['featured'] = filter_var(
                $request->input('featured'),
                FILTER_VALIDATE_BOOLEAN
            );
        }

        if ($request->has('published')) {
            $data['published'] = filter_var(
                $request->input('published'),
                FILTER_VALIDATE_BOOLEAN
            );
        }

        if ($request->has('order')) {
            $data['order'] = (int) $request->input('order');
        }

        // Slug normalization
        if (isset($data['slug'])) {
            $data['slug'] = Str::slug($data['slug']);
        }

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail
            if ($project->thumbnail) {
                Storage::disk('public')->delete($project->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')
                ->store('projects/thumbnails', 'public');
        }

        $project->update($data);

        return new ProjectResource($project->load('images'));
    }

    public function destroy(Project $project)
    {
        if ($project->thumbnail) {
            Storage::disk('public')->delete($project->thumbnail);
        }
        $project->delete();

        return response()->json(['message' => 'Project deleted.']);
    }
}
