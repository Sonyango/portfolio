<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::published()
            ->ordered()
            ->with('images')
            ->when($request->category, fn($q) =>
                $q->where('category', $request->category)
            )->get();
        return ProjectResource::collection($projects);
    }

    public function show(string $slug)
    {
        $project = Project::published()
            ->with('images')
            ->where('slug', $slug)
            ->firstOrFail();

        return new ProjectResource($project);
    }
}
