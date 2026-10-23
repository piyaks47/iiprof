<?php

namespace App\Admin\Resources\Training;

use App\Admin\Field;
use App\Admin\Resources\Home\HomeItemResource;
use Illuminate\Database\Eloquent\Model;

class FacultyResource extends HomeItemResource
{
    public function section(): string { return 'faculty'; }

    public function key(): string { return 'faculty'; }

    public function label(): string { return 'Trainers & faculty'; }

    public function singular(): string { return 'trainer'; }

    public function area(): string { return 'training'; }

    public function icon(): string { return 'identification'; }

    public function description(): ?string { return 'Trainers shown in the faculty block (photo, name, designation and a short profile).'; }

    public function fields(?Model $record): array
    {
        return [
            Field::text('title', 'Name')->required()->max(160),
            Field::text('subtitle', 'Designation')->max(255),
            Field::textarea('description', 'Short profile')->rows(4)->max(800),
            Field::image('image', 'Photo')->hint('Portrait, at least 600 × 750 px.'),
            Field::link('button_url', 'Profile link')->hint('Optional.'),
        ];
    }

    public function emptyText(): string { return 'No trainers yet — the faculty block stays hidden until you add one.'; }
}
