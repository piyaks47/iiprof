<?php

namespace App\Models;

use App\Models\Concerns\HasImages;
use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** A course or training programme; each one has its own page at /training/<slug>. */
class TrainingProgramme extends Model
{
    use HasImages, Orderable;

    /** type => label (tabs on the homepage) */
    public const TYPES = [
        'open' => 'Open programmes',
        'in-house' => 'In-company training',
        'online' => 'Online learning',
    ];

    protected $fillable = [
        'title', 'slug', 'type', 'tagline', 'summary', 'description', 'audience', 'outcomes', 'modules',
        'certificate', 'duration', 'format', 'fee', 'image', 'button_text', 'button_url', 'is_featured',
        'sort_order', 'is_active',
    ];

    protected $attributes = ['type' => 'open'];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (TrainingProgramme $programme) {
            $base = Str::slug($programme->slug ?: $programme->title) ?: 'programme';
            $slug = $base;
            for ($i = 2; static::query()->where('slug', $slug)->when($programme->id, fn ($q) => $q->whereKeyNot($programme->id))->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }
            $programme->slug = $slug;
        });
    }

    public function batches(): HasMany
    {
        return $this->hasMany(TrainingBatch::class);
    }

    public function path(): string
    {
        return '/training/'.$this->slug;
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? Str::headline((string) $this->type);
    }

    public static function markdown(?string $text): string
    {
        return $text ? (string) Str::markdown($text, ['html_input' => 'strip', 'allow_unsafe_links' => false]) : '';
    }

    /** Card data (homepage, list page). */
    public function toSummary(): array
    {
        $next = $this->relationLoaded('batches')
            ? $this->batches->first(fn (TrainingBatch $b) => $b->is_active && $b->isUpcoming())
            : $this->batches()->upcoming()->first();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'path' => $this->path(),
            'type' => $this->type,
            'type_label' => $this->typeLabel(),
            'tagline' => $this->tagline,
            'summary' => $this->summary,
            'duration' => $this->duration,
            'format' => $this->format,
            'fee' => $this->fee,
            'image_url' => $this->imageUrl(),
            'button_text' => $this->button_text,
            'button_url' => $this->button_url,
            'is_featured' => $this->is_featured,
            'next_batch' => $next?->toApi(withProgramme: false),
        ];
    }

    /** Programme page. */
    public function toDetail(): array
    {
        return $this->toSummary() + [
            'description' => $this->description,
            'description_html' => self::markdown($this->description),
            'audience' => $this->audience,
            'audience_html' => self::markdown($this->audience),
            'outcomes' => $this->outcomes,
            'outcomes_html' => self::markdown($this->outcomes),
            'modules' => self::listItems($this->modules),
            'certificate' => $this->certificate,
            'batches' => $this->batches()->upcoming()->get()->map(fn (TrainingBatch $b) => $b->toApi(withProgramme: false))->values()->all(),
        ];
    }

    /** "- one\n- two" or one per line → ["one", "two"]. */
    public static function listItems(?string $text): array
    {
        return collect(preg_split('/\R/', (string) $text) ?: [])
            ->map(fn ($line) => trim(preg_replace('/^\s*(?:[-*•]|\d+[.)])\s*/u', '', $line) ?? ''))
            ->filter()
            ->values()
            ->all();
    }
}
