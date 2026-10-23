<?php

namespace App\Models;

use App\Models\Concerns\HasImages;
use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasImages, Orderable;

    protected $fillable = [
        'title', 'slug', 'summary', 'description', 'image', 'starts_on', 'location',
        'button_text', 'button_url', 'media_url', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['starts_on' => 'date'];
    }

    protected static function booted(): void
    {
        static::saving(function (Event $event) {
            $base = Str::slug($event->slug ?: $event->title) ?: 'event';
            $slug = $base;
            for ($i = 2; static::query()->where('slug', $slug)->when($event->id, fn ($q) => $q->whereKeyNot($event->id))->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }
            $event->slug = $slug;
        });
    }

    public function descriptionHtml(): string
    {
        return $this->description
            ? (string) Str::markdown($this->description, ['html_input' => 'strip', 'allow_unsafe_links' => false])
            : '';
    }

    public function path(): string
    {
        return '/events/'.$this->slug;
    }
}
