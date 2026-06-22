<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SkillResource;
use App\Models\Skill;

class SkillController extends Controller
{
    public function index()
    {
        $skills = Skill::ordered()->get();

        //Group by category
        $grouped = $skills->groupBy('category')->map(fn($items) =>
            SkillResource::collection($items)
        );

        return response()->json(['data' => $grouped]);
    }
}
