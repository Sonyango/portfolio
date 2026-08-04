<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        // $settings = Setting::all()->pluck('value', 'key');
        // return response()->json(['data' => $settings]);

        $settings = Setting::all()->mapWithKeys(function ($setting) {
            $value = $setting->value ?? '';

            // Convert profile_image path to full url
            if ($setting->key === 'profile_image' && $value) {
                $value = str_starts_with($value, 'http') ? $value : asset('storage/' . $value);
            }
            return [$setting->key => $value];
        });
        return response()->json(['data' => $settings]);
    }
}
