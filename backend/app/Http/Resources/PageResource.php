<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** @mixin \App\Models\Page */
class PageResource extends PageSummaryResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            // Markdown for native renderers (React Native), HTML for the web.
            'content' => $this->content,
            'content_html' => $this->content_html,
            'has_content' => $this->hasContent(),
            'meta' => [
                'title' => $this->meta_title ?: $this->title,
                'description' => $this->meta_description ?: $this->excerpt,
            ],
        ];
    }
}
