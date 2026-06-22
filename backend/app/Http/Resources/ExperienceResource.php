<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExperienceResource extends JsonResource
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
            'company'       => $this->company,
            'role'          => $this->role,
            'description'   => $this->description,
            'start_date'    => $this->start_date?->format('M Y'),
            'end_date'      => $this->current
                                ? 'Present'
                                : $this->end_date?->format('M Y'),
            'current'       => $this->current,
            'location'      => $this->location,
            'order'         => $this->order,
        ];
    }
}
