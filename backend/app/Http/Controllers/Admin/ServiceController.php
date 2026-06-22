<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        return ServiceResource::collection(Service::ordered()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:200',
            'description'   => 'required|string',
            'icon'          => 'nullable|string|max:100',
            'price_range'   => 'nullable|string|max:100',
            'order'         => 'integer',
            'published'     => 'boolean',
        ]);

        return new ServiceResource(Service::create($data));
    }

    public function update(Request $request, Service $service)
    {
        $data = $request->validate([
            'title'         => 'sometimes|string|max:200',
            'description'   => 'sometimes|string',
            'icon'          => 'nullable|string|max:100',
            'price_range'   => 'nullable|string|max:100',
            'order'         => 'integer',
            'published'     => 'boolean',
        ]);

        $service->update($data);
        return new ServiceResource($service);
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return response()->json(['message' => 'Service deleted']);
    }
}
