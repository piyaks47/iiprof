<?php

namespace App\Models;

use App\Models\Concerns\HasImages;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** A seminar: banner on the homepage (image, title, link) and a page at /seminars/<slug> with its description. */
class Seminar extends Model
{
    use HasImages, HasSlug, Orderable;

    protected $fillable = ['title', 'slug', 'summary', 'description', 'image', 'link', 'button_text', 'starts_on', 'location', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['starts_on' => 'date'];
    }

    public function path(): string
    {
        return '/seminars/'.$this->slug;
    }

    public function descriptionHtml(): string
    {
        return $this->description ? (string) Str::markdown($this->description, ['html_input' => 'strip', 'allow_unsafe_links' => false]) : '';
    }

    public function toSummary(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'path' => $this->path(),
            'summary' => $this->summary ?: Str::limit(trim(strip_tags($this->descriptionHtml())), 200),
            'image_url' => $this->imageUrl(),
            'link' => $this->link,
            'button_text' => $this->button_text,
            'starts_on' => $this->starts_on?->toDateString(),
            'location' => $this->location,
        ];
    }

    public function toDetail(): array
    {
        return $this->toSummary() + ['description' => $this->description, 'description_html' => $this->descriptionHtml()];
    }
}
