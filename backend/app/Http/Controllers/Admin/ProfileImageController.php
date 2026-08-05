<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class ProfileImageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'profile_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Delete old image if exists
        $existing = Setting::get('profile_image');
        if ($existing && !str_starts_with($existing, 'http')) {
            Storage::disk('public')->delete($existing);
        }

        // Store new image
        $path = $request->file('profile_image')->store('profile', 'public');

        // Save only the path (not URL) to settngs
        Setting::set('profile_image', $path, 'general');

        return response()->json([
            'message' => 'Profile image uploaded successfully.',
            'url'   => url('/api/profile-image'),  //Protected route
            'path' => $path,
        ]);
    }

    public function destroy()
    {
        $existing = Setting::get('profile_image');
        if ($existing && !str_starts_with($existing, 'http')) {
            Storage::disk('public')->delete($existing);
        }

        Setting::where('key', 'profile_image')->update(['value' => '']);
        return response()->json([
            'message' => 'Profile image removed successfully.'
        ]);
    }
}
