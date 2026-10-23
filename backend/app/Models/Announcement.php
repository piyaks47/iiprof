<?php

namespace App\Models;

use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;

/** Scrolling notice in the top bar of the website. */
class Announcement extends Model
{
    use Orderable;

    protected $fillable = ['text', 'url', 'sort_order', 'is_active'];
}
