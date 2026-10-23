<?php

namespace App\Models;

use App\Models\Concerns\HasImages;
use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;

/** A person on a management page (/management/{group}). */
class TeamMember extends Model
{
    use HasImages, Orderable;

    /** group key => page title (URL: /management/{key}). */
    public const GROUPS = [
        'director-council' => 'Director Council',
        'governing-body' => 'Governing Body',
        'managing-committee' => 'Managing Committee',
        'board-of-studies' => 'Board of Studies',
        'advisory-council' => 'Advisory Council',
    ];

    protected string $sortGroup = 'group';

    protected $fillable = ['group', 'name', 'role', 'bio', 'photo', 'sort_order', 'is_active'];

    public function imageAttributes(): array
    {
        return ['photo'];
    }
}
