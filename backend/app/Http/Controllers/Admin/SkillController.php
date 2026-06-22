<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SkillResource;
use App\Models\Skill;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    public function index()
    {
        return SkillResource::collection(Skill::ordered()->get());
    }
    
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'category'      => 'nullable|string|max:100',
            'proficiency'   => 'required|integer|min:0|max:100',
            'icon'          => 'nullable|string|max:100',
            'order'         => 'integer',
        ]);

        return new SkillResource(Skill::create($data));
    }

    public function update(Request $request, Skill $skill)
    {
        $data = $request->validate([
            'name'          => 'sometimes|string|max:100',
            'category'      => 'nullable|string|max:100',
            'proficiency'   => 'sometimes|integer|min:0|max:100',
            'icon'          => 'nullable|string|max:100',
            'order'         => 'integer',
        ]);

        $skill->update($data);
        return new SkillResource($skill);
    }

    public function destroy(Skill $skill)
    {
        $skill->delete();
        return response()->json(['message' => 'Skill deleted.']);
    }
}
