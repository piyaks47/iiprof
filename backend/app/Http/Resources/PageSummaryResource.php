<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Page */
class PageSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'path' => $this->path(),
            'excerpt' => $this->excerpt,
            'featured_image_url' => $this->featured_image_url,
            'banner' => [
                // The page's own image, else the featured image of its menu item (or of the nearest item above it).
                'image_url' => $this->featured_image_url ?? \App\Models\MenuItem::imageForPath($this->path()),
                'title' => $this->banner_title ?: $this->title,
                'text' => $this->banner_text,
                'button_text' => $this->banner_button_text,
                'button_url' => $this->banner_button_url,
            ],
            'published_at' => $this->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
