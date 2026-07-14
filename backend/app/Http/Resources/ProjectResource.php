<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProjectImageResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'slug'          => $this->slug,
            'description'   => $this->description,
            'content'       => $this->content,
            'tech_stack'    => $this->tech_stack ?? [],
            'live_url'      => $this->live_url,
            'github_url'    => $this->github_url,
            'thumbnail'     => $this->thumbnail
                                ? asset('storage/' . $this->thumbnail)
                                : null,
            'category'      => $this->category,
            'featured'      => $this->featured,
            'order'         => $this->order,
            'published'     => $this->published,
            'images'        => ProjectImageResource::collection(
                                    $this->whenLoaded('images')
                            ),
            'created_at'    => $this->created_at?->toDateString(),
        ];
    }
}
