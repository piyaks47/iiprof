<?php

namespace App\Models;

use App\Models\Concerns\HasImages;
use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model
{
    use HasImages, Orderable;

    protected $fillable = ['title', 'image', 'sort_order', 'is_active'];
}
