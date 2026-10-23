<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $query = Media::latest();

        // Filter by mime type group
        if ($request->has('type')) {
            if ($request->type === 'image') {
                $query->where('mime_type', 'like', 'image/%');
            } elseif ($request->type === 'other') {
                $query->where('mime_type', 'not like', 'image/%');
            }
        }

        // Search by file name
        if ($request->has('search') && $request->search) {
            $query->where('file_name', 'like', '%' . $request->search . '%');
        }

        $media = $query->get();

        return response()->json([
            'data' => MediaResource::collection($media),
        ]);
    }

    // File upload
    public function store(Request $request)
    {
        $request->validate([
            'file'          => 'required|file|max:5120|mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx',
            'collection'    => 'nullable|string|max:100',
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

        return response()->json([
            'message' => 'File uploaded successfully.',
            'data' => new MediaResource($media),
        ], 201);
    }

    // Delete file
    public function destroy(Media $media)
    {
        // Remove from disk
        if (Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }
        $media->delete();

        return response() ->json(['message' => 'File deleted successfully.'], 200);
    }
}
