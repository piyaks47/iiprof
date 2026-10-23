<?php

namespace App\Models;

use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FooterLink extends Model
{
    use Orderable;

    protected string $sortGroup = 'footer_column_id';

    protected $fillable = ['footer_column_id', 'label', 'url', 'new_tab', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['new_tab' => 'boolean'];
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(FooterColumn::class, 'footer_column_id');
    }
}
