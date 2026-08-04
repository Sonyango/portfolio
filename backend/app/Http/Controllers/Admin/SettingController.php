<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index()
    {

        $settings = Setting::all()->mapWithKeys(function ($setting) {
            $value = $setting->value ?? '';

            // Convert profile_image path to full URL for admin display
            if ($setting->key === 'profile_image' && $value && $value !== 'null') {
                $value = str_starts_with($value, 'http')
                    ? $value
                    : asset('storage/' . $value);
            }

            return [$setting->key => $value];
        });
        return response()->json(['data' => $settings]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'settings'          => 'required|array',
            'settings.*.key'    => 'required|string',
            'settings.*.value'   => 'nullable|string',
            'settings.*.group'  => 'nullable|string',
        ]);

        // foreach ($data['settings'] as $setting) {
        //     Setting::set(
        //         $setting['key'],
        //         $setting['value'] ?? null,
        //         $setting['group'] ?? 'general'
        //     );
        // }

        foreach ($data['settings'] as $setting) {
            $key    = $setting['key'];
            $value  = ($setting['value'] === 'null' || $setting['value'] === null)
                        ? ''
                        : $setting['value'];
            $group  = $setting['group'] ?? 'general';

            // Bypass any model issues
            $exists = DB::table('settings')->where('key', $key)->exists();

            if ($exists) {
                DB::table('settings')
                    ->where('key', $key)
                    ->update([
                        'value'         => $value,
                        'group'         => $group,
                        'updated_at'    => now(),
                    ]);
            } else {
                DB::table('settings')->insert([
                    'key'           => $key,
                    'value'         => $value,
                    'group'         => $group,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }

        return response()->json(['message' => 'Settings updated successfully.']);
    }
}
