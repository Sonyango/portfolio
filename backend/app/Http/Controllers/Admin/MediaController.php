<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index()
    {
        $media = Media::latest()->get();
        return MediaResource::collection($media);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file'          => 'required|file|max:5120',
            'collection'    => 'required|string|max:100',
        ]);

        $file           = $request->file('file');
        $path           = $file->store('media/' . date('Y/m/d'), 'public');
        $collection     = $request->input('collection', 'general');

        $media = Media::create([
            'file_name'         => $file->getClientOriginalName(),
            'file_path'         => $path,
            'mime_type'         => $file->getMimeType(),
            'file_size'         => $file->getSize(),
            'disk'              => 'public',
            'collection'        => $collection,
            'order'             => 0,
        ]);

        return new MediaResource($media);
    }

    public function destroy(Media $media)
    {
        Storage::disk('public')->delete($media->file_path);
        $media->delete();

        return response() ->json(['message' => 'File deleted successfully.'], 200);
    }
}
