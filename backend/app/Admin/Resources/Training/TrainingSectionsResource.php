<?php

namespace App\Admin\Resources\Training;

use App\Admin\Resources\Home\BlockSectionsResource;

class TrainingSectionsResource extends BlockSectionsResource
{
    public function key(): string { return 'training-sections'; }

    public function label(): string { return 'Training sections'; }

    public function area(): string { return 'training'; }
}
