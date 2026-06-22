<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return response()->json(['data' => $settings]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'settings'          => 'required|array',
            'settings.*.key'    => 'required|string',
            'setting.*.value'   => 'nullable|string',
            'settings.*.group'  => 'nullable|string',
        ]);

        foreach ($data['settings'] as $setting) {
            Setting::set(
                $setting['key'],
                $setting['value'] ?? null,
                $setting['group'] ?? 'general'
            );
        }

        return response()->json(['message' => 'Settings updated.']);
    }
}
