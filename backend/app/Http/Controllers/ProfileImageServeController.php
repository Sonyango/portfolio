<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProfileImageServeController extends Controller
{
    public function show(Request $request)
    {
        $path = Setting::get('profile_image');

        if (!$path || empty($path || $path === 'null')) {
            abort(404);
        }

        // Strip full URL if stored as one
        if (str_starts_with($path, 'http')) {
            $path = preg_replace('#^.*/storage/#', '', $path);
        }

        if (!Storage::disk('public')->exists($path)){
            abort(404);
        }

        $file = Storage::disk('public')->get($path);

        // Detect mime type with fallback for fake storage in tests
        //$mimeType = Storage::mimeType($path);
        $mimeType = Storage::disk('public')->mimeType($path);

        if (empty($mimeType) || $mimeType === 'application/octet-stream') {
            // Drive from extension as fallback
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mimeType = match($extension) {
                'jpg', 'jpeg'   => 'image/jpeg',
                'png'           => 'image/png',
                'webp'          => 'image/webp',
                'gif'           => 'image/gif',
                default         => 'image/jpeg',
            };
        }

        return response($file, 200)
            ->header('Content-Type',    $mimeType)
            ->header('Cache-Control',   'public, max-age=3600')
            ->header('X-Content-Type-Options',  'nosniff')
            ->header('Content-Disposition', 'inline')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }
}
