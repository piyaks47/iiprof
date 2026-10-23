<?php

namespace App\Models;

use App\Models\Concerns\HasImages;
use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** An item of one of the newer homepage blocks; `section` is the key of its HomeSection (pillars, faq …). */
class HomeItem extends Model
{
    use HasImages, Orderable;

    protected string $sortGroup = 'section';

    protected $fillable = [
        'section', 'title', 'subtitle', 'value', 'description', 'icon', 'image',
        'button_text', 'button_url', 'sort_order', 'is_active',
    ];

    public function scopeIn(Builder $query, string $section): Builder
    {
        return $query->where('section', $section);
    }

    public function descriptionHtml(): string
    {
        return $this->description
            ? (string) Str::markdown($this->description, ['html_input' => 'strip', 'allow_unsafe_links' => false])
            : '';
    }

    /** Shape used by the API (the same for every block). */
    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'value' => $this->value,
            'description' => $this->description,
            'description_html' => $this->descriptionHtml(),
            'icon' => $this->icon,
            'image_url' => $this->imageUrl(),
            'button_text' => $this->button_text,
            'button_url' => $this->button_url,
        ];
    }
}
