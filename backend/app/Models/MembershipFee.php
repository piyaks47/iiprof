<?php

namespace App\Models;

use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;

/** One row of the membership fee table (amounts as shown, e.g. "1500", "Free", "On request"). */
class MembershipFee extends Model
{
    use Orderable;

    protected $fillable = ['grade', 'code', 'annual_fee', 'lifetime_fee', 'note', 'button_url', 'sort_order', 'is_active'];

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'grade' => $this->grade,
            'code' => $this->code,
            'annual_fee' => $this->annual_fee,
            'lifetime_fee' => $this->lifetime_fee,
            'note' => $this->note,
            'button_url' => $this->button_url,
        ];
    }
}
