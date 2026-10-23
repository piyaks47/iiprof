<?php

namespace App\Models;

use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FooterColumn extends Model
{
    use Orderable;

    protected $fillable = ['title', 'sort_order', 'is_active'];

    public function links(): HasMany
    {
        return $this->hasMany(FooterLink::class)->ordered();
    }
}
