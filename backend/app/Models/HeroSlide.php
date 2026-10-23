<?php

namespace App\Models;

use App\Models\Concerns\HasImages;
use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    use HasImages, Orderable;

    protected $fillable = ['eyebrow', 'title', 'subtitle', 'image', 'button_text', 'button_url', 'sort_order', 'is_active'];
}
