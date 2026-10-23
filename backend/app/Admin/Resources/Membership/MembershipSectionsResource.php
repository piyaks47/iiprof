<?php

namespace App\Admin\Resources\Membership;

use App\Admin\Resources\Home\BlockSectionsResource;

class MembershipSectionsResource extends BlockSectionsResource
{
    public function key(): string { return 'membership-sections'; }

    public function label(): string { return 'Membership sections'; }

    public function area(): string { return 'membership'; }
}
