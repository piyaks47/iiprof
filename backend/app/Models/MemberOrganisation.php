<?php

namespace App\Models;

use App\Models\Concerns\HasImages;
use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** A corporate or institutional member (company, university, college, school …) shown with its logo. */
class MemberOrganisation extends Model
{
    use HasImages, Orderable;

    public const KINDS = ['corporate' => 'Corporate members', 'institutional' => 'Institutional members'];

    /** Suggested categories (free text is allowed). */
    public const INSTITUTION_TYPES = ['University', 'College', 'School', 'Institute', 'Polytechnic / ITI', 'Training centre', 'Research centre'];

    protected string $sortGroup = 'kind';

    protected $fillable = ['kind', 'name', 'category', 'city', 'logo', 'website', 'description', 'member_since', 'grade', 'is_featured', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean'];
    }

    public function imageAttributes(): array
    {
        return ['logo'];
    }

    public function scopeKind(Builder $query, string $kind): Builder
    {
        return $query->where('kind', $kind);
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'city' => $this->city,
            'logo_url' => $this->imageUrl('logo'),
            'website' => $this->website,
            'description' => $this->description,
            'member_since' => $this->member_since,
            'grade' => $this->grade,
            'is_featured' => $this->is_featured,
        ];
    }
}
