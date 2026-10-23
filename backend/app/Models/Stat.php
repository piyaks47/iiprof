<?php

namespace App\Models;

use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;

/** A key figure on the homepage, e.g. "18" / "Professional communities". */
class Stat extends Model
{
    use Orderable;

    protected $fillable = ['value', 'label', 'sort_order', 'is_active'];
}
