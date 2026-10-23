<?php

namespace App\Admin\Resources\Training;

use App\Admin\Field;
use App\Admin\Resources\Home\HomeItemResource;
use Illuminate\Database\Eloquent\Model;

class TrainingBenefitResource extends HomeItemResource
{
    public function section(): string { return 'training_benefits'; }

    public function key(): string { return 'training-benefits'; }

    public function label(): string { return 'Why train with IIP'; }

    public function singular(): string { return 'benefit'; }

    public function area(): string { return 'training'; }

    public function icon(): string { return 'star'; }

    public function description(): ?string { return 'Benefits of training at IIP, shown on a dark band.'; }

    public function fields(?Model $record): array
    {
        return [
            Field::text('title')->required()->max(160),
            Field::textarea('description')->rows(2)->max(400),
            Field::icon('icon')->required()->default('check'),
        ];
    }

    public function emptyText(): string { return 'No benefits yet — the block is hidden until you add one.'; }
}
