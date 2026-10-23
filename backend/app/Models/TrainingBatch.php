<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A scheduled run of a training programme (dates, place, format, registration link). */
class TrainingBatch extends Model
{
    /** format => label */
    public const FORMATS = [
        'classroom' => 'Classroom',
        'online' => 'Online (live)',
        'hybrid' => 'Hybrid',
        'in-house' => 'In-company',
    ];

    protected $fillable = [
        'training_programme_id', 'batch_code', 'format', 'location', 'starts_on', 'ends_on',
        'schedule', 'seats', 'register_url', 'is_active',
    ];

    protected $attributes = ['format' => 'classroom', 'is_active' => true];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'seats' => 'integer', 'is_active' => 'boolean'];
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(TrainingProgramme::class, 'training_programme_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('is_active'), true);
    }

    /** Active batches that have not finished yet, soonest first. */
    public function scopeUpcoming(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->active()
            ->where(fn (Builder $q) => $q->whereDate('ends_on', '>=', $today)->orWhere(fn (Builder $q) => $q->whereNull('ends_on')->whereDate('starts_on', '>=', $today)))
            ->orderBy('starts_on')->orderBy('id');
    }

    public function isUpcoming(): bool
    {
        return ($this->ends_on ?? $this->starts_on)?->gte(now()->startOfDay()) ?? false;
    }

    public function formatLabel(): string
    {
        return self::FORMATS[$this->format] ?? (string) $this->format;
    }

    /** "12–14 Dec 2026", "28 Nov – 2 Dec 2026", "5 Jan 2027". */
    public function dateRange(): string
    {
        $start = $this->starts_on;
        $end = $this->ends_on;
        if (! $start) {
            return '';
        }
        if (! $end || $end->isSameDay($start)) {
            return $start->format('j M Y');
        }
        if ($start->isSameMonth($end)) {
            return $start->format('j').'–'.$end->format('j M Y');
        }
        if ($start->isSameYear($end)) {
            return $start->format('j M').' – '.$end->format('j M Y');
        }

        return $start->format('j M Y').' – '.$end->format('j M Y');
    }

    public function toApi(bool $withProgramme = true): array
    {
        return [
            'id' => $this->id,
            'batch_code' => $this->batch_code,
            'format' => $this->format,
            'format_label' => $this->formatLabel(),
            'location' => $this->location,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'dates' => $this->dateRange(),
            'schedule' => $this->schedule,
            'seats' => $this->seats,
            'register_url' => $this->register_url,
        ] + ($withProgramme && $this->programme ? ['programme' => [
            'id' => $this->programme->id, 'title' => $this->programme->title, 'slug' => $this->programme->slug, 'path' => $this->programme->path(),
        ]] : []);
    }
}
