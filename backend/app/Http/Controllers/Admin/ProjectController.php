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
        $data = $request-validate([
            'title'         => 'required|string|max:255',
            'slug'          => 'nullable|string|unique:projects,slug',
            'description'   => 'required|string',
            'content'       => 'nullable|string',
            'tech_stack'    => 'nullable|array',
            'live_url'      => 'nullable|url',
            'github_url'    => 'nullable|url',
            'category'      => 'nullable|string|max:100',
            'featured'      => 'boolean',
            'order'         => 'integer',
            'published'     => 'boolean',
            'thumbnail'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['title']);

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
            'live_url'      => 'nullable|url',
            'github_url'    => 'nullable|url',
            'category'      => 'nullable|string|max:100',
            'featured'      => 'boolean',
            'order'         => 'integer',
            'published'     => 'boolean',
            'thumbnail'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('thumbnail')) {
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
