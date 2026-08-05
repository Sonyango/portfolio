<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {

        $settings = Setting::all()->mapWithKeys(function ($setting) {
            $value = $setting->value ?? '';

            // Return protected route URL for profile image
            if ($setting->key === 'profile_image' && $value && $value !== 'null')
                {
                    $value = url('/api/profile-image');
                }
            return [$setting->key => $value];
        });
        return response()->json(['data' => $settings]);
    }
}
