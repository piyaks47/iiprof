<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Message sent from the website's "Enquire" form. */
class Enquiry extends Model
{
    public const STATUSES = ['new' => 'New', 'in_progress' => 'In progress', 'closed' => 'Closed'];

    protected $fillable = ['name', 'email', 'phone', 'subject', 'message', 'status', 'notes', 'ip_address'];

    protected $attributes = ['status' => 'new'];

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', 'new');
    }
}
