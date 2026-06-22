<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'file_name'         => $this->file_name,
            'file_path'         => $this->file_path,
            'url'               => asset('storage/' . $this->file_path),
            'mime_type'         => $this->mime_type,
            'file_size'         => $this->file_size,
            'file_size_human'   => $this->formatFileSize($this->file_size),
            'disk'              => $this->disk,
            'collection'        => $this->collection,
            'order'             => $this->order,
            'mediable_type'     => $this->mediable_type,
            'mediable_id'       => $this->mediable_id,
            'created_at'        => $this->created_at?->toDateString(),
        ];
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . 'MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . 'KB';
        }
        return $bytes . ' B';
    }
}
