<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Access level of a role to an admin area. See App\Support\Access. */
class RolePermission extends Model
{
    protected $fillable = ['role', 'area', 'level'];

    protected function casts(): array
    {
        return ['level' => 'integer'];
    }
}
