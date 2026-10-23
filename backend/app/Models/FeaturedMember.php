<?php

namespace App\Models;

use App\Models\Concerns\HasImages;
use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;

/** A member shown in the homepage member directory (not a login account). */
class FeaturedMember extends Model
{
    use HasImages, Orderable;

    /** category => tab label */
    public const CATEGORIES = [
        'patrons' => 'Patrons',
        'fellows' => 'Fellows',
        'individual' => 'Individual members',
        'life' => 'Life members',
    ];

    protected string $sortGroup = 'category';

    protected $fillable = ['category', 'name', 'designation', 'organisation', 'city', 'photo', 'url', 'sort_order', 'is_active'];

    protected $attributes = ['category' => 'individual'];

    public function imageAttributes(): array
    {
        return ['photo'];
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'name' => $this->name,
            'designation' => $this->designation,
            'organisation' => $this->organisation,
            'city' => $this->city,
            'photo_url' => $this->imageUrl('photo'),
            'url' => $this->url,
        ];
    }
}
