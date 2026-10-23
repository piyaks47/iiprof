<?php

namespace App\Models;

use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;

class Highlight extends Model
{
    use Orderable;

    protected $fillable = ['title', 'description', 'icon', 'sort_order', 'is_active'];

    protected $attributes = ['icon' => 'light-bulb'];
}
