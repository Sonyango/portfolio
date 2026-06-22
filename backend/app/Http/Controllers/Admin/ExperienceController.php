<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExperienceResource;
use App\Models\Experience;
use Illuminate\Http\Request;

class ExperienceController extends Controller
{
    public function index()
    {
        return ExperienceResource::collection(Experience::ordered()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company'           => 'required|string|max:150',
            'role'              => 'required|string|max:150',
            'description'       => 'nullable|string',
            'start_date'        => 'required|date',
            'end_date'          => 'nullable|date|after:start_date',
            'current'           => 'boolean',
            'location'          => 'nullable|string|max:150',
            'order'             => 'integer',
        ]);

        return new ExperienceResource((Experience::create($data)));
    }

    public function update(Request $request, Experience $experience)
    {
        $data = $request->validate([
            'company'       => 'sometimes|string|max:150',
            'role'          => 'sometimes|string|max:150',
            'description'   => 'nullable|string',
            'start_date'    => 'sometimes|date',
            'end_date'      => 'nullable|date',
            'current'       => 'boolean',
            'location'      => 'nullable|string|max:150',
            'order'         => 'integer',
        ]);

        $experience->update($data);
        return new ExperienceResource($experience);
    }

    public function destroy(Experience $experience)
    {
        $experience->delete();
        return response()->json(['message' => 'Experience deleted.']);
    }
}
